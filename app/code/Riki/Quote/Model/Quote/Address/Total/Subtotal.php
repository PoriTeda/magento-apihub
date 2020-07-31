<?php

namespace Riki\Quote\Model\Quote\Address\Total;

use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Item as AddressItem;
use Magento\Quote\Model\Quote\Item;

class Subtotal extends \Magento\Quote\Model\Quote\Address\Total\Subtotal
{
    /**
     * @var \Riki\Subscription\Logger\LoggerOrder
     */
    protected $loggerOrder;

    /**
     * @param \Magento\Quote\Model\QuoteValidator $quoteValidator
     * @param \Riki\Subscription\Logger\LoggerOrder $loggerOrder
     */
    public function __construct(
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        \Riki\Subscription\Logger\LoggerOrder $loggerOrder
    ) {
        $this->loggerOrder = $loggerOrder;
        parent::__construct($quoteValidator);
    }

    /**
     * Collect address subtotal
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param Address\Total $total
     * @return $this
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        \Magento\Quote\Model\Quote\Address\Total\AbstractTotal::collect($quote, $shippingAssignment, $total);

        $total->setTotalQty(0);

        $baseVirtualAmount = $virtualAmount = 0;

        $address = $shippingAssignment->getShipping()->getAddress();
        /**
         * Process address items
         */
        $items = $shippingAssignment->getItems();
        foreach ($items as $item) {
            if ($this->_initItem($address, $item) && $item->getQty() > 0) {
                /**
                 * Separately calculate subtotal only for virtual products
                 */
                if ($item->getProduct()->isVirtual()) {
                    $virtualAmount += $item->getRowTotal();
                    $baseVirtualAmount += $item->getBaseRowTotal();
                }
            } else {
                $this->_removeItem($address, $item);
            }

            /// fix
            $total->setTotalQty($total->getTotalQty() + $item->getQty());
            ///
        }

        $total->setBaseVirtualAmount($baseVirtualAmount);
        $total->setVirtualAmount($virtualAmount);

        /**
         * Initialize grand totals
         */
        $this->quoteValidator->validateQuoteAmount($quote, $total->getSubtotal());
        $this->quoteValidator->validateQuoteAmount($quote, $total->getBaseSubtotal());
        return $this;
    }

    /**
     * Address item initialization
     *
     * @param Address $address
     * @param AddressItem|Item $item
     * @return bool
     */
    protected function _initItem($address, $item)
    {
        if ($item instanceof AddressItem) {
            $quoteItem = $item->getAddress()->getQuote()->getItemById($item->getQuoteItemId());
        } else {
            $quoteItem = $item;
        }

        $quote = $quoteItem->getQuote();

        $product = $quoteItem->getProduct();
        $product->setCustomerGroupId($quote->getCustomerGroupId());

        $product->setRikiCourseId($quote->getData('riki_course_id'));
        $product->setRikiFrequencyId($quote->getData('riki_frequency_id'));
        $product->setNDelivery($quote->getData('n_delivery'));

        /**
         * Quote super mode flag mean what we work with quote without restriction
         */
        if ($item->getQuote()->getIsSuperMode()) {
            if (!$product) {
                return false;
            }
        } else {
            if (!$product || !$product->isVisibleInCatalog()) {
                return false;
            }
        }

        $quoteItem->setConvertedPrice(null);

        // If quote item is variable fee
        // Set variable fee to product price
        try {
            $additionalData = json_decode(
                $quoteItem->getData('additional_data') ?: '{}',
                true
            );
            if (isset($additionalData['is_variable_fee']) && $additionalData['is_variable_fee'] &&
                isset($additionalData['variable_fee'])
            ) {
                $product->setPrice($additionalData['variable_fee']);
            }
        } catch (\Zend_Json_Exception $e) {
            $this->loggerOrder->info((string)$quoteItem->getData('additional_data'));
        }

        $originalPrice = $product->getPrice();

        if ($quoteItem->getParentItem() && $quoteItem->isChildrenCalculated()) {
            $finalPrice = $quoteItem->getParentItem()->getProduct()->getPriceModel()->getChildFinalPrice(
                $quoteItem->getParentItem()->getProduct(),
                $quoteItem->getParentItem()->getQty(),
                $product,
                $quoteItem->getQty()
            );
            $this->_calculateRowTotal($item, $finalPrice, $originalPrice);
        } elseif (!$quoteItem->getParentItem()) {
            $finalPrice = $product->getFinalPrice($quoteItem->getQty());
            $item->setRulePrice($product->getRulePrice());
            $item->setData(
                'stock_point_applied_discount_rate',
                $product->getData('stock_point_applied_discount_rate')
            );
            $item->setData(
                'stock_point_applied_discount_amount',
                $product->getData('stock_point_applied_discount_amount')
            );
            $this->_calculateRowTotal($item, $finalPrice, $originalPrice);
            $this->_addAmount($item->getRowTotal());
            $this->_addBaseAmount($item->getBaseRowTotal());
            $address->setTotalQty($address->getTotalQty() + $item->getQty());
        }
        return true;
    }
}
