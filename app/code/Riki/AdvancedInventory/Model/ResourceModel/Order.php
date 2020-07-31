<?php
namespace Riki\AdvancedInventory\Model\ResourceModel;

class Order extends \Magento\Sales\Model\ResourceModel\Order
{
    /**
     * @param $orderId
     * @param $assignation
     * @return $this
     */
    public function saveAssignation($orderId, $assignation)
    {
        $orderAssignationData = \Zend_Json::encode($assignation);

        $this->getConnection()->update(
            $this->getMainTable(),
            ['assignation'  =>  $orderAssignationData, 'assigned_to' =>  $assignation['place_ids']],
            ['entity_id = ?'    =>  $orderId]
        );

        $this->getConnection()->update(
            $this->getTable('sales_order_grid'),
            ['assigned_to' =>  $assignation['place_ids']],
            ['entity_id = ?'    =>  $orderId]
        );

        return $this;
    }
}
