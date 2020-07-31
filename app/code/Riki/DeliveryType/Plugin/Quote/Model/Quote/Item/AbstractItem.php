<?php
namespace Riki\DeliveryType\Plugin\Quote\Model\Quote\Item;

class AbstractItem
{
    /**
     * Set delivery type from bundle for bundle item
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $subject
     * @param $result
     * @return mixed
     */
    public function afterSetParentItem(
        \Magento\Quote\Model\Quote\Item\AbstractItem $subject,
        $result
    )
    {
        if ($parentItem = $subject->getParentItem()) {
            $subject->setData('delivery_type', $parentItem->getData('delivery_type'));
        }

        return $result;
    }
}
