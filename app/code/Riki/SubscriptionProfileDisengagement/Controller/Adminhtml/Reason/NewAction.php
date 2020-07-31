<?php

namespace Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Reason;

class NewAction extends \Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Reason
{
    public function execute()
    {
        $this->_forward('edit');
    }
}
