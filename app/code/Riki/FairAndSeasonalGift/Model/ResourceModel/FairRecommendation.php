<?php
namespace Riki\FairAndSeasonalGift\Model\ResourceModel;
class FairRecommendation extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_fair_recommendation','id');
    }
}
