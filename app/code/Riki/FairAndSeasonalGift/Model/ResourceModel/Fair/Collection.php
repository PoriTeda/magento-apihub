<?php
namespace Riki\FairAndSeasonalGift\Model\ResourceModel\Fair;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'fair_id';
    protected function _construct()
    {
        $this->_init('Riki\FairAndSeasonalGift\Model\Fair','Riki\FairAndSeasonalGift\Model\ResourceModel\Fair');
    }
}
