<?php
namespace Riki\Subscription\Model\Emulator\ResourceModel;

use Riki\Subscription\Model\Emulator\Config ;

class RewardQuote
    extends \Riki\Loyalty\Model\ResourceModel\RewardQuote
{

    /**
     * Main table and field initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Config::getRikiRewardQuoteTmpTableName(), 'id');
    }
}