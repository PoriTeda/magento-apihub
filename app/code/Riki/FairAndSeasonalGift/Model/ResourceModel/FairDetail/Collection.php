<?php
namespace Riki\FairAndSeasonalGift\Model\ResourceModel\FairDetail;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Riki\FairAndSeasonalGift\Model\FairDetail','Riki\FairAndSeasonalGift\Model\ResourceModel\FairDetail');
    }
}
