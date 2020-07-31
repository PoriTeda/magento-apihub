<?php
namespace Riki\FairAndSeasonalGift\Model\ResourceModel\FairRecommendation;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Riki\FairAndSeasonalGift\Model\FairRecommendation','Riki\FairAndSeasonalGift\Model\ResourceModel\FairRecommendation');
    }
}
