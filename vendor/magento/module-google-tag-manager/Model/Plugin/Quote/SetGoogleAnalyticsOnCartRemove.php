<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Model\Plugin\Quote;

/**
 * Class \Magento\GoogleTagManager\Model\Plugin\Quote\SetGoogleAnalyticsOnCartRemove
 *
 * Intercepts data during update cart and checked need triggered the remove_to_cart event.
 */
class SetGoogleAnalyticsOnCartRemove
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
     * Calls the method that sets item data to registry for triggering remove event.
     *
     * @param \Magento\Quote\Model\Quote $subject
     * @param \Magento\Quote\Model\Quote\Item $result
     * @return \Magento\Quote\Model\Quote\Item $result
     */
    public function afterRemoveItem(\Magento\Quote\Model\Quote $subject, $result, $itemId)
    {
        $item = $subject->getItemById($itemId);
        if ($item) {
            $this->setItemForTriggerRemoveEvent($this->helper, $this->registry, $item, $item->getQty());
        }

        return $result;
    }

    /**
     * Parses the product Qty data after update cart event.
     * In cases when product qty is decreased the product data sets to registry.
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

        if ($qty < $result->getQty()) {
            return $result;
        }

        $this->setItemForTriggerRemoveEvent($this->helper, $this->registry, $result, $qty - $result->getQty());
        return $result;
    }

    /**
     * Sets item data to registry for triggering remove event.
     *
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param $qty
     * @return \Magento\Quote\Model\Quote\Item
     */
    private function setItemForTriggerRemoveEvent(
        \Magento\GoogleTagManager\Helper\Data $helper,
        \Magento\Framework\Registry $registry,
        \Magento\Quote\Model\Quote\item $item,
        $qty
    ) {
        if (!$helper->isTagManagerAvailable()) {
            return $item;
        }

        $namespace = 'GoogleTagManager_products_to_remove';
        $registry->unregister($namespace);
        $registry->register($namespace, [[
            'sku'   => $item->getSku(),
            'name'  => $item->getName(),
            'price' => $item->getPrice(),
            'qty'   => $qty,
        ]]);
    }
}
