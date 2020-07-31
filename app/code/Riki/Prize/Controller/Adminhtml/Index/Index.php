<?php

namespace Riki\Prize\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class Index extends \Riki\Prize\Controller\Adminhtml\Action
{
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_Prize::prize');
        $resultPage->addBreadcrumb(__('Prize'), __('Prize'));
        $resultPage->getConfig()->getTitle()->prepend(__('Prize'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Prize'));

        return $resultPage;
    }

}