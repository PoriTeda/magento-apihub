<?php

namespace Riki\SubscriptionMachine\Controller\Adminhtml\Customer;

use Magento\Framework\Controller\ResultFactory;

class Index extends \Riki\SubscriptionMachine\Controller\Adminhtml\Action
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_SubscriptionMachine::machine_customer');
        $resultPage->addBreadcrumb(__('Machine Customer'), __('Machine Customer'));
        $resultPage->getConfig()->getTitle()->prepend(__('Machine Customer'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Machine Customer'));

        return $resultPage;
    }
}
