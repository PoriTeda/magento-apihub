<?php

namespace Riki\Sales\Model\ResourceModel\Order;

class Shipment extends \Magento\Sales\Model\ResourceModel\Order\Shipment
{
    /**
     * @param int $orderItemId
     * @return string
     */
    public function getShippedOutDateByOrderItemId(int $orderItemId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->reset()
            ->from(['shipment' => $this->getTable('sales_shipment')], 'max(shipped_out_date)')
            ->join(
                ['shipment_item' => $this->getTable('sales_shipment_item')],
                'shipment.entity_id=shipment_item.parent_id',
                []
            )->where(
                'shipment_item.order_item_id = ?',
                $orderItemId
            )->where('shipped_out_date is not null');
        return $connection->fetchOne($select);
    }

    /**
     * @param int $orderId
     * @return string
     */
    public function getShippedOutDateByOrderId(int $orderId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['shipment' => $this->getTable('sales_shipment')], 'max(shipped_out_date)')
            ->where(
                'order_id = ?',
                $orderId
            )
            ->where('shipped_out_date is not null');
        return $connection->fetchOne($select);
    }
}
