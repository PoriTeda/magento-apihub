<?php
namespace Riki\Rule\Model\ResourceModel;

class CumulatedGift extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_cumulated_gift', 'id');
    }

    /**
     * Insert multiple data
     *
     * @param array $data array
     *
     * @return $this
     * @throws \Exception
     */
    public function multiplyBunchInsert($data)
    {
        $this->getConnection()->beginTransaction();

        try {

            $this->getConnection()->insertMultiple(
                $this->getTable('riki_cumulated_gift'),
                $data
            );
            $this->getConnection()->commit();

        } catch (\Exception $e) {
            $this->getConnection()->rollback();
            throw $e;
        }

        return $this;
    }

    public function removeCumulativeGiftData($orderNumber)
    {
        $connection = $this->getConnection();
        $where = [
            'order_number = ?' => $orderNumber,
        ];
        return $connection->delete($this->getMainTable(), $where);
    }

    public function getNotAttachedIds($consumerId)
    {
        $connection = $this->getConnection();
        $sql = $connection->select()
            ->from($this->getMainTable(), ['id'])
            ->where('consumer_db_id = ?', $consumerId)
            ->where('status = ?', 'Not attached');
        return $connection->fetchCol($sql);
    }

    public function updateGiftStatus($updateIds, $orderNumber, $status = 'Attached')
    {
        //$updateIds = array_slice($notAttachedIds, 0, $qty);
        $connection = $this->getConnection();
        $connection->update(
            $this->getMainTable(),
            ['status' => $status, 'order_number' => $orderNumber],
            ['id IN (?)' => $updateIds]
        );
    }

}