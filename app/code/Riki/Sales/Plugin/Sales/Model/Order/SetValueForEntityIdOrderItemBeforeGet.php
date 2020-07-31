<?php

namespace Riki\Sales\Plugin\Sales\Model\Order;

class SetValueForEntityIdOrderItemBeforeGet
{
    /**
     * Extend getEntityId()
     *
     * When the function save() in \Magento\Sales\Model\Order\ItemRepository called
     * It will  a key is entity_id of order item to array registry.
     * But entity_id always does not exist.
     * This code to set value for entity_id of order item equal with item_id.
     *
     * @param \Magento\Sales\Model\Order\Item $subject
     * @return array
     * @see \Magento\Sales\Model\Order\ItemRepository::save()
     */
    public function beforeGetEntityId(\Magento\Sales\Model\Order\Item $subject)
    {
        $subject->setEntityId($subject->getItemId());
        return [];
    }
}
