<?php

namespace Riki\SubscriptionMachine\Controller\Adminhtml\ConditionRule;

use Magento\Backend\App\Action;

class NewAction extends \Riki\SubscriptionMachine\Controller\Adminhtml\Action
{
    /**
     * NewAction constructor.
     * @param Action\Context $context
     */
    public function __construct(
        Action\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        return $this->_forward('edit');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionMachine::machine_conditionRule_save');
    }
}
