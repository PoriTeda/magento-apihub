<?php
namespace Riki\Subscription\Model\Frequency\ResourceModel;

class Frequency extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('subscription_frequency', 'frequency_id');
    }

}