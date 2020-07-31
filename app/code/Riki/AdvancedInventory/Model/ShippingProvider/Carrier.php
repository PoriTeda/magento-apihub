<?php
namespace Riki\AdvancedInventory\Model\ShippingProvider;

class Carrier extends \Riki\ShippingProvider\Model\Carrier
{
    /**
     * @var array
     */
    protected $oosFee;

    /**
     * @var \Riki\AdvancedInventory\Observer\OosCapture
     */
    protected $oosCaptureObserver;


    /**
     * Carrier constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\AdvancedInventory\Observer\OosCapture $oosCaptureObserver
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param \Magento\Backend\Model\Session\Quote $checkoutBackendSession
     * @param \Magento\Backend\App\Area\FrontNameResolver $area
     * @param \Magento\Framework\App\State $appState
     * @param \Riki\DeliveryType\Model\Delitype $delitype
     * @param \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper
     * @param \Magento\Framework\HTTP\Header $httpHeader
     * @param \Riki\Sales\Helper\Admin $salesAdmin
     * @param array $data
     */
    public function __construct(
        \Riki\AdvancedInventory\Observer\OosCapture $oosCaptureObserver,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Backend\Model\Session\Quote $checkoutBackendSession,
        \Magento\Backend\App\Area\FrontNameResolver $area,
        \Magento\Framework\App\State $appState,
        \Riki\DeliveryType\Model\Delitype $delitype,
        \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper,
        \Magento\Framework\HTTP\Header $httpHeader,
        \Riki\Sales\Helper\Admin $salesAdmin,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        array $data = []
    ) {
        $this->oosCaptureObserver = $oosCaptureObserver;
        parent::__construct(
            $registry,
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $rateResultFactory,
            $rateMethodFactory,
            $checkoutSession,
            $checkoutBackendSession,
            $area,
            $appState,
            $delitype,
            $deliveryTypeAdminHelper,
            $httpHeader,
            $salesAdmin,
            $serializer,
            $data
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     *
     * @return number
     */
    public function calculateFeeForEachAddress($request)
    {
        $quoteId = 0;
        $origAllItems = $request->getAllItems();
        foreach ($origAllItems as $item) {
            $quoteId = $item->getQuoteId();
            break;
        }

        $outOfStocks = $this->oosCaptureObserver->getOutOfStocks($quoteId);
        if (!$outOfStocks) {
            return parent::calculateFeeForEachAddress($request);
        }

        $calculatedFeeForEachAddress = array();
        $total = 0;

        if (!$request->getFreeShipping() && !$this->_isAdminFreeShipping()) {

            $allItems = $request->getAllItems();
            // push the oos quote item
            foreach ($outOfStocks as $key => $outOfStock) {
                if ($outOfStock->getIsFree()) {
                    continue;
                }
                $oosQuoteItem = $outOfStock->initNewQuoteItem();
                if (!$oosQuoteItem instanceof \Magento\Quote\Model\Quote\Item) {
                    continue;
                }

                array_push($allItems, $oosQuoteItem);
            }
            $request->setAllItems($allItems);

            $groupedItemByAddressId = $this->groupItemsByAddresses($request);

            $request->setAllItems($origAllItems);

            foreach ($groupedItemByAddressId as $addressId => $items) {
                $calculatedFeeForEachAddress[$addressId][] = $this->calculateShippingFee($items);
            }

            $total = $this->calculateTotalFeeForAddresses($calculatedFeeForEachAddress);
        }
        $this->setFeeToQuote($calculatedFeeForEachAddress);

        $request->setData('calculatedFeeForEachAddress', $calculatedFeeForEachAddress);
        return $total;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     *
     * @return array
     */
    public function calculateShippingFee($request)
    {
        $arrayFee = array();

        $freeShippingAmount = floatval(
            $this->getConfigData(self::XML_PATH_FREE_SHIPPING_CONDITION)
        );

        $feeTypes = $this->_delitype->getResourceCollection()
            ->addFieldToSelect(array('code', 'shipping_fee'));

        $arrayFeeTypes = array();
        foreach ($feeTypes as $feeType) {
            $feeCode = $feeType->getData('code');
            $feeShip = $feeType->getData('shipping_fee');
            $arrayFeeTypes[$feeCode] = $feeShip;
        }
        // TODO: Should delete this registry after all OOS items finished orders creating
        $oldDeliveryTypeData = $this->registry->registry('recalculate_oos_order_shipping_fee_with_old_tax');
        if (!empty($oldDeliveryTypeData)) {
            $arrayFeeTypes = $oldDeliveryTypeData;
        }
        $allItems = $request;

        $conditionFreeAllCoolNormalDm = array();
        $conditionFreeChilled = array();
        $conditionFreeCold = array();
        $conditionFreeCosmetic = array();
        $oosFee = [];

        /* @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($allItems as $item) {
            $deliveryType = $item->getData('delivery_type');
            if ($deliveryType) {
                $arrayFee[$deliveryType][] = $arrayFeeTypes[$deliveryType];

                $isFreeShippingItem = $this->_isFreeShippingItem($item);

                if ($isFreeShippingItem) {
                    array_push($arrayFee[$deliveryType], self::FREE_SHIPPING_FLAG);
                }

                $gwPriceIncludeTax = (float)$item->getData('gw_price') + (float)$item->getData('gw_tax_amount');
                $totalGwPriceIncludeTax = $gwPriceIncludeTax * $item->getData('tmp_qty') / ($item->getData('unit_qty') ?: 1); // gw apply only case
                $totalPriceInclTax = $item->getPriceInclTax() * $item->getData('tmp_qty');

                if ($this->_isCoolNormalDmType($deliveryType)) {
                    $conditionFreeAllCoolNormalDm[] = $totalPriceInclTax + $totalGwPriceIncludeTax;
                    if ($item->getOosUniqKey()) {
                        $oosFee[\Riki\DeliveryType\Model\Delitype::COOL_NORMAL_DM][] = $item;
                    }
                } elseif ($this->_isChilledType($deliveryType)) {
                    $conditionFreeChilled[] = $totalPriceInclTax + $totalGwPriceIncludeTax;
                    if ($item->getOosUniqKey()) {
                        $oosFee[\Riki\DeliveryType\Model\Delitype::CHILLED][] = $item;
                    }
                } elseif ($this->_isColdType($deliveryType)) {
                    $conditionFreeCold[] = $totalPriceInclTax + $totalGwPriceIncludeTax;
                    if ($item->getOosUniqKey()) {
                        $oosFee[\Riki\DeliveryType\Model\Delitype::COLD][] = $item;
                    }
                } elseif ($this->_isCosmeticType($deliveryType)) {
                    $conditionFreeCosmetic[] = $totalPriceInclTax + $totalGwPriceIncludeTax;
                    if ($item->getOosUniqKey()) {
                        $oosFee[\Riki\DeliveryType\Model\Delitype::COSMETIC][] = $item;
                    }
                }
            }
        }


        if ($arrayFee) {
            $arrayFee = $this->_calculateTotalFee($arrayFee);
        }
        if (array_sum($conditionFreeAllCoolNormalDm) >= $freeShippingAmount) {
            $arrayFee[\Riki\DeliveryType\Model\Delitype::COOL_NORMAL_DM] = 0;
        }

        if (array_sum($conditionFreeChilled) >= $freeShippingAmount) {
            $arrayFee[\Riki\DeliveryType\Model\Delitype::CHILLED] = 0;
        }

        if (array_sum($conditionFreeCold) >= $freeShippingAmount) {
            $arrayFee[\Riki\DeliveryType\Model\Delitype::COLD] = 0;
        }

        if (array_sum($conditionFreeCosmetic) >= $freeShippingAmount) {
            $arrayFee[\Riki\DeliveryType\Model\Delitype::COSMETIC] = 0;
        }

        $dTypeMap = [
            \Riki\DeliveryType\Model\Delitype::COOL_NORMAL_DM => $conditionFreeAllCoolNormalDm,
            \Riki\DeliveryType\Model\Delitype::CHILLED => $conditionFreeChilled,
            \Riki\DeliveryType\Model\Delitype::COLD => $conditionFreeCold,
            \Riki\DeliveryType\Model\Delitype::COSMETIC => $conditionFreeCosmetic,
        ];
        foreach ($oosFee as $dType => $oosQuoteItems) {
            if (!isset($arrayFee[$dType])) {
                continue;
            }

            /** @var \Magento\Quote\Model\Quote\Item $oosQuoteItem */
            foreach ($oosQuoteItems as $oosQuoteItem) {
                if ($arrayFee[$dType] == 0) {
                    $oosQuoteItem->setIsFreeShipping(1);
                    continue;
                }

                if (isset($dTypeMap[$dType]) && count($dTypeMap[$dType]) > 1) {
                    $oosQuoteItem->setIsFreeShipping(1);
                    continue;
                }

                if (isset($dTypeMap[$dType]) && count($dTypeMap[$dType]) == 1) {
                    unset($arrayFee[$dType]);
                    continue;
                }
            }
        }
        return $arrayFee;
    }
}