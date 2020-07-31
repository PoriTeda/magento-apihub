<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Model\Plugin\Quote;

/**
 * Class \Magento\GoogleTagManager\Model\Plugin\Quote\SetGoogleAnalyticsOnCartAdd
 *
 * Intercepts data during update cart and checked need triggered the add_to_cart event.
 */
class SetGoogleAnalyticsOnCartAdd
{
    /**
     * @var \Magento\GoogleTagManager\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\GoogleTagManager\Helper\Data $helper,
        \Magento\Framework\Registry $registry
    ) {
        $this->helper = $helper;
        $this->registry = $registry;
    }

    /**
     * Parses the product Qty data after update cart event.
     * In cases when product qty is increased the product data sets to registry.
     *
     * @param \Magento\Quote\Model\Quote $subject
     * @param \Closure $proceed
     * @param $itemId
     * @param $buyRequest
     * @param $params
     * @return \Magento\Quote\Model\Quote\Item
     */
    public function aroundUpdateItem(
        \Magento\Quote\Model\Quote $subject,
        \Closure $proceed,
        $itemId,
        $buyRequest,
        $params = null
    ) {
        $item = $subject->getItemById($itemId);
        $qty = $item ? $item->getQty() : 0;
        $result = $proceed($itemId, $buyRequest, $params);

        if ($qty > $result->getQty()) {
            return $result;
        }

        $this->setItemForTriggerAddEvent($this->helper, $this->registry, $result, $qty);
        return $result;
    }

    /**
     * Sets item data to registry for triggering add event.
     *
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param $qty
     * @return void
     */
    private function setItemForTriggerAddEvent(
        \Magento\GoogleTagManager\Helper\Data $helper,
        \Magento\Framework\Registry $registry,
        \Magento\Quote\Model\Quote\Item $item,
        $qty
    ) {
        if ($helper->isTagManagerAvailable()) {
            $namespace = 'GoogleTagManager_products_addtocart';
            $registry->unregister($namespace);
            $registry->register($namespace, [[
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'price' => $item->getPrice(),
                'qty' => $item->getQty() - $qty,
            ]]);
        }
    }
}
