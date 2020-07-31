<?php
namespace Riki\Rma\Model\ResourceModel;

use Magento\Rma\Model\Rma\Source\Status;

class Item extends \Magento\Rma\Model\ResourceModel\Item
{
    /**
     * Gets rma items ids by order
     * Custom: should exclude processed_closed return
     *
     * @param  int $orderId
     * @return array
     */
    public function getReturnableItems($orderId)
    {
        $connection = $this->getConnection();
        $salesAdapter = $this->_resource->getConnection('sales');
        $shippedSelect = $salesAdapter->select()
            ->from(
                ['order_item' => $this->getTable('sales_order_item')],
                [
                    'order_item.item_id',
                    'order_item.qty_shipped'
                ]
            )->where('order_item.order_id = ?', $orderId);

        $orderItemsShipped = $salesAdapter->fetchPairs($shippedSelect);

        $requestedSelect = $connection->select()
            ->from(
                ['rma' => $this->getTable('magento_rma')],
                [
                    'rma_item.order_item_id',
                    new \Zend_Db_Expr('SUM(qty_requested)')
                ]
            )
            ->joinInner(
                ['rma_item' => $this->getTable('magento_rma_item_entity')],
                'rma.entity_id = rma_item.rma_entity_id',
                []
            )->where(
                'rma_item.order_item_id IN (?)',
                array_keys($orderItemsShipped)
            )->where(
                sprintf(
                    '%s NOT IN (?)',
                    $connection->getIfNullSql('rma.status', $connection->quote(Status::STATE_CLOSED))
                ),
                [Status::STATE_CLOSED]
            )->group('rma_item.order_item_id');
        $orderItemsRequested = $connection->fetchPairs($requestedSelect);
        $result = [];
        foreach ($orderItemsShipped as $itemId => $shipped) {
            $requested = 0;
            if (isset($orderItemsRequested[$itemId])) {
                $requested = $orderItemsRequested[$itemId];
            }

            $result[$itemId] = 0;
            if ($shipped > $requested) {
                $result[$itemId] = $shipped - $requested;
            }
        }

        return $result;
    }
}
