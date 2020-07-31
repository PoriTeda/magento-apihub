<?php
namespace Riki\FairAndSeasonalGift\Model\ResourceModel\FairConnection;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Riki\FairAndSeasonalGift\Model\FairConnection','Riki\FairAndSeasonalGift\Model\ResourceModel\FairConnection');
    }
}
