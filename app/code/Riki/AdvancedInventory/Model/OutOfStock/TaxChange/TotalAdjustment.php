<?php

namespace Riki\AdvancedInventory\Model\OutOfStock\TaxChange;

use Magento\Quote\Model\Quote\ItemFactory as QuoteItemFactory;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;

class TotalAdjustment
{
    const NA = 'N/A';

    const XML_PATH_DUMMY_PROMOTION_ID =
        'advancedinventory_outofstock/generate_order/tax_change_total_adjustment_sales_rule_id';

    /**
     * @var array
     */
    protected $oldPaymentFeeData = [
        'cashondelivery' => 324
    ];

    /**
     * @var array
     */
    protected $oldDeliveryTypeData = [
        'cool' => 650,
        'normal' => 450,
        'direct_mail' => 120,
        'cold' => 650,
        'chilled' => 950,
        'cosmetic' => 450,
    ];

    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    protected $quoteItemFactory;

    /**
     * Shipping carrier
     *
     * @var \Riki\ShippingProvider\Model\Carrier
     */
    protected $shippingFeeCalculator;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateRequestFactory
     */
    protected $rateRequestFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * TotalAdjustment constructor.
     * @param QuoteItemFactory $quoteItemFactory
     * @param \Riki\ShippingProvider\Model\Carrier $shippingFeeCalculator
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Quote\Model\Quote\Address\RateRequestFactory $rateRequestFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        QuoteItemFactory $quoteItemFactory,
        \Riki\ShippingProvider\Model\Carrier $shippingFeeCalculator,
        \Magento\Framework\Registry $registry,
        \Magento\Quote\Model\Quote\Address\RateRequestFactory $rateRequestFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->quoteItemFactory = $quoteItemFactory;
        $this->shippingFeeCalculator = $shippingFeeCalculator;
        $this->registry = $registry;
        $this->rateRequestFactory = $rateRequestFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $newQuote
     * @param \Riki\AdvancedInventory\Api\Data\OutOfStockInterface[] $outOfStockList
     * @param \Magento\Quote\Model\Quote\Address $shippingAddress
     * @return int
     */
    public function getDifferenceAmount($newQuote, $outOfStockList, $shippingAddress)
    {
        $grandTotal = $this->getGrandTotal($newQuote, $outOfStockList, $shippingAddress);
        if ($newQuote->getGrandTotal() - $grandTotal > 0) {
            return $newQuote->getGrandTotal() - $grandTotal;
        }
        return 0;
    }

    /**
     * @return mixed
     */
    private function getDummyPromotionId()
    {
        $dummyPromotionId = $this->scopeConfig->getValue(self::XML_PATH_DUMMY_PROMOTION_ID);
        return $dummyPromotionId;
    }

    /**
     * @param \Riki\AdvancedInventory\Api\Data\OutOfStockInterface[] $outOfStockList
     * @param string $field
     * @return array
     */
    private function initializeQuoteItems($outOfStockList, $field = 'additional_data')
    {
        $quoteItemList = [];
        /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
        foreach ($outOfStockList as $outOfStock) {
            $additionalData = $outOfStock->getData($field);
            if ($additionalData) {
                if (isset($additionalData['old_quote_item_data'])) {
                    $quoteItems = $this->getOldQuoteItems($additionalData['old_quote_item_data']);
                    foreach ($quoteItems as $quoteItem) {
                        $quoteItemList[] = $quoteItem;
                    }
                }
            }
        }
        return $quoteItemList;
    }

    /**
     * @param array $oldQuoteItemData
     * @return array
     */
    protected function getOldQuoteItems($oldQuoteItemData)
    {
        $quoteItems = [];
        foreach ($oldQuoteItemData as $quoteItem) {
            $quoteItemModel = $this->quoteItemFactory->create();
            $quoteItemModel->setData($quoteItem);
            $quoteItems[] = $quoteItemModel;
        }
        return $quoteItems;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $newQuote
     * @param \Riki\AdvancedInventory\Api\Data\OutOfStockInterface[] $outOfStockList
     * @param \Magento\Quote\Model\Quote\Address $shippingAddress
     * @return float|int
     */
    protected function getGrandTotal($newQuote, $outOfStockList, $shippingAddress)
    {
        $grandTotal = 0;
        $grandTotal += $this->getGrandTotalExclPaymentFee($outOfStockList, $shippingAddress);
        $usedPointAmount = 0;
        $amountFromPoints = $shippingAddress->getAmountFromPoints();
        if ($amountFromPoints >= $grandTotal) {
            $usedPointAmount = $grandTotal;
        } elseif ($amountFromPoints > 0) {
            $usedPointAmount = $amountFromPoints;
        }
        if ($usedPointAmount > 0) {
            $grandTotal -= $usedPointAmount;
        }
        if ($shippingAddress->getFee() > 0) {
            $paymentMethod = $newQuote->getPayment()->getMethod();
            if (isset($this->oldPaymentFeeData[$paymentMethod])) {
                $grandTotal += $this->oldPaymentFeeData[$paymentMethod];
            }
        }
        return $grandTotal;
    }

    /**
     * @param * @param \Riki\AdvancedInventory\Api\Data\OutOfStockInterface[] $outOfStockList
     * @param \Magento\Quote\Model\Quote\Address  $shippingAddress
     * @return float|int|number
     */
    protected function getGrandTotalExclPaymentFee($outOfStockList, $shippingAddress)
    {
        $quoteItemList = $this->initializeQuoteItems($outOfStockList);
        $grandTotalExclPaymentFee = 0;
        foreach ($quoteItemList as $quoteItem) {
            $subtotalInclTax = $discountAmount = $gwAmount = 0;
            if ($quoteItem->getParentItemId()) {
                continue;
            }
            $subtotalInclTax += floatval($quoteItem->getRowTotalInclTax());
            $discountAmount -= floatval($quoteItem->getDiscountAmount());
            $qty = $this->getQuoteItemFinalQty($quoteItem);
            $gwAmount += ($quoteItem->getGwPrice() + $quoteItem->getGwTaxAmount()) * $qty;
            $grandTotalExclPaymentFee += ($subtotalInclTax + $discountAmount + $gwAmount);
        }
        $shippingFee = $this->getOldShippingFee($quoteItemList, $shippingAddress);
        if ($shippingFee > 0) {
            $grandTotalExclPaymentFee += $shippingFee;
        }
        return $grandTotalExclPaymentFee;
    }

    /**
     * @param array $quoteItemList
     * @param \Magento\Quote\Model\Quote\Address $shippingAddress
     * @return number
     */
    protected function getOldShippingFee($quoteItemList, $shippingAddress)
    {
        $calculatedFeeForEachAddress = [];
        $shippingFee = 0;
        $request = $this->rateRequestFactory->create();
        $request->setAllItems($quoteItemList);
        $request->setFreeShipping($shippingAddress->getFreeShipping());
        if (!$request->getFreeShipping()) {
            $this->registry->register('recalculate_oos_order_shipping_fee_with_old_tax', $this->oldDeliveryTypeData);
            $groupedItemByAddressId = $this->shippingFeeCalculator->groupItemsByAddresses($request);
            foreach ($groupedItemByAddressId as $addressId => $items) {
                $calculatedFeeForEachAddress[$addressId][] = $this->shippingFeeCalculator->calculateShippingFee($items);
            }
            $shippingFee = $this->shippingFeeCalculator->calculateTotalFeeForAddresses($calculatedFeeForEachAddress);
            $this->registry->unregister('recalculate_oos_order_shipping_fee_with_old_tax');
        }
        return $shippingFee;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $newQuote
     * @param \Magento\Quote\Model\Quote\Address $shippingAddress
     * @param \Riki\AdvancedInventory\Api\Data\OutOfStockInterface[] $outOfStockList
     */
    public function applyAdjustment($newQuote, $shippingAddress, $outOfStockList)
    {
        if ($this->allowAdjustOosOrder($outOfStockList)) {
            $differenceAmount = $this->getDifferenceAmount(
                $newQuote,
                $outOfStockList,
                $shippingAddress
            );
            if ($differenceAmount > 0) {
                $shippingAddress->setGrandTotal($shippingAddress->getGrandTotal() - $differenceAmount);
                $shippingAddress->setBaseGrandTotal($shippingAddress->getBaseGrandTotal() - $differenceAmount);
                $shippingAddress->setSubtotalWithDiscount(
                    $shippingAddress->getSubtotalWithDiscount() - $differenceAmount
                );
                $shippingAddress->setBaseSubtotalWithDiscount(
                    $shippingAddress->getBaseSubtotalWithDiscount() - $differenceAmount
                );
                $shippingAddress->setDiscountAmount($shippingAddress->getDiscountAmount() - $differenceAmount);
                $shippingAddress->setBaseDiscountAmount(
                    $shippingAddress->getBaseDiscountAmount() - $differenceAmount
                );

                $newQuote->setGrandTotal($shippingAddress->getGrandTotal());
                $newQuote->setBaseGrandTotal($shippingAddress->getBaseGrandTotal());
                $newQuote->setSubtotalWithDiscount($shippingAddress->getSubtotalWithDiscount());
                $newQuote->setBaseSubtotalWithDiscount($shippingAddress->getBaseSubtotalWithDiscount());
                $newQuote->setDiscountAmount($shippingAddress->getDiscountAmount());
                $newQuote->setBaseDiscountAmount($shippingAddress->getBaseDiscountAmount());
            }
            if ($differenceAmount > 0 || $shippingAddress->getHasAjustPointAmount()) {
                $this->applyDummyPromotion($newQuote);
            }
        }
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $newQuote
     */
    protected function applyDummyPromotion($newQuote)
    {
        $ruleId = (int)$this->getDummyPromotionId() ? (int)$this->getDummyPromotionId() : '';
        $quoteRule = $newQuote->getAppliedRuleIds();
        if ($quoteRule === null) {
            $newQuote->setAppliedRuleIds($ruleId);
        } else {
            $appliedRuleIds = explode(',', $quoteRule);
            $appliedRuleIds[] = $ruleId;
            $newQuote->setAppliedRuleIds(implode(',', $appliedRuleIds));
        }
    }

    /**
     * @param \Riki\AdvancedInventory\Api\Data\OutOfStockInterface[] $outOfStockList
     * @return bool
     */
    public function allowAdjustOosOrder($outOfStockList)
    {
        /**
         * What if there is one of "out of stock item" which it allow_adjust_oos_order = false?
         * => This case will not happen since all OOS items belong to only one order
         */
        foreach ($outOfStockList as $outOfStock) {
            if ($additionalData = $outOfStock->getAdditionalData()) {
                if (isset($additionalData['allow_adjust_oos_order']) && $additionalData['allow_adjust_oos_order']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return float|int
     */
    public function getQuoteItemFinalQty($quoteItem)
    {
        $unitQty = $quoteItem->getUnitQty() ? $quoteItem->getUnitQty() : 1;
        $finalQty = $quoteItem->getQty();
        if ($quoteItem->getUnitCase() == CaseDisplay::PROFILE_UNIT_CASE) {
            $finalQty = $quoteItem->getQty() / $unitQty;
        }
        return $finalQty;
    }

    /**
     * @param int $amountFromPoints
     * @param \Riki\AdvancedInventory\Api\Data\OutOfStockInterface[] $outOfStockList
     * @param \Magento\Quote\Model\Quote\Address $shippingAddress
     * @return array
     */
    public function getUsePointAdjustData($amountFromPoints, $outOfStockList, $shippingAddress)
    {
        $realUsePoint = 0;
        $pointDiscountAmount = 0;
        if ($this->allowAdjustOosOrder($outOfStockList)) {
            $oldGrandTotalExclPaymentFee = $this->getGrandTotalExclPaymentFee($outOfStockList, $shippingAddress);
            if ($shippingAddress->getGrandTotal() > $oldGrandTotalExclPaymentFee) {
                $pointDiscountAmount = $shippingAddress->getGrandTotal() - $oldGrandTotalExclPaymentFee;
                if ($amountFromPoints >= $oldGrandTotalExclPaymentFee) {
                    $realUsePoint = $oldGrandTotalExclPaymentFee;
                } else {
                    $realUsePoint = $amountFromPoints;
                }
            }
        }
        return [$realUsePoint, $pointDiscountAmount];
    }
}
