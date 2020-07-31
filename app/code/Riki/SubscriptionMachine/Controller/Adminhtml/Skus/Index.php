<?php

namespace Riki\SubscriptionMachine\Controller\Adminhtml\Skus;

use Magento\Framework\Controller\ResultFactory;

class Index extends \Riki\SubscriptionMachine\Controller\Adminhtml\Action
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_SubscriptionMachine::machine_skus');
        $resultPage->addBreadcrumb(__('Machine SKUs'), __('Machine SKUs'));
        $resultPage->getConfig()->getTitle()->prepend(__('Machine SKUs'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Machine SKUs'));

        return $resultPage;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionMachine::machine_skus');
    }
}
