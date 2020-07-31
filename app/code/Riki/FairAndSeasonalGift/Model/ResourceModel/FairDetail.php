<?php
namespace Riki\FairAndSeasonalGift\Model\ResourceModel;
class FairDetail extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_fair_details','id');
    }

    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        if( $object->getIsRecommend() == 1 ){
            $where = ['fair_id = ?' => $object->getFairId(), 'id != ?' => $object->getId()];
            $this->getConnection()->update($this->getTable('riki_fair_details'), ['is_recommend' => 0], $where);
        }
        return parent::_afterSave($object);
    }
}
