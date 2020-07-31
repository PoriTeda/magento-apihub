<?php
namespace Riki\Prize\Plugin\Sales\Model\AdminOrder;

class Create
{

    /**
     * remove winner prize item for reorder action
     *
     * @param \Magento\Sales\Model\AdminOrder\Create $subject
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param null $qty
     * @return array
     */
    public function beforeInitFromOrderItem(
        \Magento\Sales\Model\AdminOrder\Create $subject,
        \Magento\Sales\Model\Order\Item $orderItem,
        $qty = null
    ) {

        if($orderItem->getData('prize_id')) {
            $orderItem->setId(null);
        }

        return [$orderItem, $qty];
    }
}
