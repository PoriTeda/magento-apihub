<?php
namespace Riki\Subscription\Model\Version\ResourceModel;

class Version extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('subscription_profile_version', 'id');
    }


}