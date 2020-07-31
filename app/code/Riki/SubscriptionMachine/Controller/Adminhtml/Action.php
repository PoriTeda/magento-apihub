<?php

namespace Riki\SubscriptionMachine\Controller\Adminhtml;

abstract class Action extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Riki_SubscriptionMachine::machine';

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}
