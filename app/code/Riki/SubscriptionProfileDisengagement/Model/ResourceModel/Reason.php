<?php
namespace Riki\SubscriptionProfileDisengagement\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Reason extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('subscription_disengagement_reason', 'id');
    }
}
