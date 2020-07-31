<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\DeliveryType\Plugin\Quote;

class DelitypeItem
{
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\ToOrderItem $subject
     * @param callable $proceed
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param array $additional
     * @return \Magento\Sales\Model\Order\Item
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $additional
    ) {
        /** @var $orderItem \Magento\Sales\Model\Order\Item */
        $orderItem = $proceed($item, $additional);
        /** @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $item;

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $quoteItem->getProduct();
        $orderItem->setDeliveryType($quoteItem->getData('delivery_type'));

        //convert delivery date and time from quote item to order item
        $orderItem->setData('delivery_date', $quoteItem->getData('delivery_date'));
        $orderItem->setData('delivery_time', $quoteItem->getData('delivery_time'));
        $orderItem->setData('delivery_timeslot_id', $quoteItem->getData('delivery_timeslot_id'));
        $orderItem->setData('delivery_timeslot_from', $quoteItem->getData('delivery_timeslot_from'));
        $orderItem->setData('delivery_timeslot_to', $quoteItem->getData('delivery_timeslot_to'));

        $unitCase = $quoteItem->getUnitCase();
        if(!$quoteItem->getUnitCase()){
            if($quoteItem->getProduct()){
                $unitCase = \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE;
                if ($item->getProduct()->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
                    $unitCase = \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE;
                }
            }
        }
        $orderItem->setUnitCase($unitCase);

        $unitQty  = $quoteItem->getUnitQty();
        if(!$quoteItem->getUnitQty()){
            if($quoteItem->getProduct()){
                $unitQty = $quoteItem->getProduct()->getUnitQty()?$quoteItem->getProduct()->getUnitQty():1;
            }
        }
        if($unitCase == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE){
            $unitQty = 1;
        }

        $orderItem->setUnitQty($unitQty);

        if($quoteItem->getData('visible_user_account') === false) {
            $orderItem->setData('visible_user_account',false);
        }
        if($quoteItem->getData('is_addition')){
            $orderItem->setData('is_addition',1);
        }
        return $orderItem;
    }
}
