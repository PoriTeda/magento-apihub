<?php
namespace Riki\Checkout\Observer;

class PrepareMultiShippingQty implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Prepare multi_shipping_qty for multi checkout to pass quantity validate
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $collection = $observer->getData('collection');
        if (!$collection instanceof \Magento\Quote\Model\ResourceModel\Quote\Item\Collection) {
            return;
        }

        $groups = [];
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($collection as $item) {
            $parentId = intval($item->getData('slip_parent_item_id'));
            $itemId = intval($item->getData('item_id'));
            $itemQty = floatval($item->getData('qty'));
            if ($parentId && isset($groups[$parentId])) {
                $groups[$parentId][$itemId] = $itemQty;
                continue;
            }

            $groups[$itemId][$itemId] = $itemQty;
        }

        foreach ($groups as $group) {
            $qty = array_sum($group);
            foreach ($group as $itemId => $itemQty) {
                $collection->getItemById($itemId)->setData('multi_shipping_qty', $qty);
            }
        }
    }

}