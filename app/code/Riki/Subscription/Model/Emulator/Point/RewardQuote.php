<?php

namespace Riki\Subscription\Model\Emulator\Point;

class RewardQuote
    extends \Riki\Loyalty\Model\RewardQuote
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\ResourceModel\RewardQuote');
    }
}