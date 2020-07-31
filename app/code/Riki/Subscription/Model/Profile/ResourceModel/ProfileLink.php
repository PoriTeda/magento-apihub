<?php

namespace Riki\Subscription\Model\Profile\ResourceModel;

class ProfileLink extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('subscription_profile_link', 'link_id');
    }
}