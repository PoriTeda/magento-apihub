<?php

namespace Riki\SubscriptionMachine\Controller\Adminhtml\ConditionRule;

use Magento\Framework\Controller\ResultFactory;

class Index extends \Riki\SubscriptionMachine\Controller\Adminhtml\Action
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_SubscriptionMachine::machine_conditionRule');
        $resultPage->addBreadcrumb(__('Machine Condition Rule'), __('Machine Condition Rule'));
        $resultPage->getConfig()->getTitle()->prepend(__('Machine Condition Rule'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Machine Condition Rule'));

        return $resultPage;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionMachine::machine_conditionRule');
    }
}
