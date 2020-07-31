<?php
namespace Riki\Rma\Model\ResourceModel;

class Rma extends \Magento\Rma\Model\ResourceModel\Rma
{
    /**
     * @deprecated
     *
     * @param int $rmaId
     * @return array
     */
    public function lockRma($rmaId)
    {
        $rmaTable = $this->getTable('magento_rma');
        $select = $this->getConnection()->select()->from($rmaTable)
            ->where('entity_id=?', $rmaId)
            ->forUpdate(true);
        return $this->getConnection()->fetchRow($select);
    }

    /**
     * Lock records to update
     *
     * @param array $ids
     *
     * @return array
     */
    public function lockIdsForUpdate($ids)
    {
        if (empty($ids)) {
            return [];
        }

        $conn = $this->getConnection();
        $select = $conn->select()
            ->from($conn->getTableName('magento_rma'), 'entity_id')
            ->where('entity_id IN (?)', $ids)
            ->forUpdate(true);

        return $conn->fetchCol($select);
    }

    /**
     * Lock record to update
     *
     * @param int $id
     *
     * @return int
     */
    public function lockIdForUpdate($id)
    {
        $conn = $this->getConnection();
        $select = $conn->select()
            ->from($conn->getTableName('magento_rma'), 'entity_id')
            ->where('entity_id = ?', $id)
            ->forUpdate(true);

        return $conn->fetchOne($select);
    }

    /**
     * Get rma id which earned point will be canceled
     *
     * @param int $orderId
     *
     * @return int
     */
    public function getTriggerCancelPointRma($orderId)
    {
        $conn = $this->getConnection();
        $select = $conn->select()
            ->from($conn->getTableName('magento_rma'), 'entity_id')
            ->where('order_id = ?', $orderId)
            ->where('trigger_cancel_point = ?', 1)
            ->limit(1);

        return $conn->fetchOne($select);
    }

    /**
     * @param array $ids
     * @return string
     */
    public function getConcatRmaName(array $ids)
    {
        $conn = $this->getConnection();
        $select = $conn->select()
            ->from(
                $conn->getTableName('magento_rma'),
                ['names' => new \Zend_Db_Expr('GROUP_CONCAT(increment_id SEPARATOR \', \')')]
            )
            ->where('entity_id IN(?)', $ids);

        return $conn->fetchOne($select);
    }
}
