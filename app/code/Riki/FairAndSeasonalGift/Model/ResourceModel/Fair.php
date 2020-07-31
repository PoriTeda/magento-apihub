<?php
namespace Riki\FairAndSeasonalGift\Model\ResourceModel;
class Fair extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_fair_management','fair_id');
    }

    protected function _afterDelete(\Magento\Framework\Model\AbstractModel $fair)
    {
        $connection = $this->getConnection();
        $connection->delete(
            $this->getTable('riki_fair_details'),
            ['fair_id=?' => $fair->getFairId()]
        );
        $connection->delete(
            $this->getTable('riki_fair_connection'),
            ['fair_id=?' => $fair->getFairId()]
        );
        $connection->delete(
            $this->getTable('riki_fair_connection'),
            ['fair_related_id=?' => $fair->getFairId()]
        );
        $connection->delete(
            $this->getTable('riki_fair_recommendation'),
            ['fair_id=?' => $fair->getFairId()]
        );
        $connection->delete(
            $this->getTable('riki_fair_recommendation'),
            ['recommended_fair_id=?' => $fair->getFairId()]
        );
        return parent::_afterDelete($fair);
    }

}
