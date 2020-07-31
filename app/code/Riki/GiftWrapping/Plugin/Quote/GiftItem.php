<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\GiftWrapping\Plugin\Quote;

class GiftItem
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

        //convert delivery date and time from quote item to order item
        if($quoteItem->getGiftCode()){
            $orderItem->setGiftCode($quoteItem->getGiftCode());
        }
        if($quoteItem->getSapCode()) {
            $orderItem->setSapCode($quoteItem->getSapCode());
        }
        if($quoteItem->getGiftWrapping()) {
            $orderItem->setGiftWrapping($quoteItem->getGiftWrapping());
        }

        return $orderItem;
    }
}
