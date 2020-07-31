<?php
namespace Riki\FairAndSeasonalGift\Model\ResourceModel;
class FairConnection extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_fair_connection','id');
    }
}
