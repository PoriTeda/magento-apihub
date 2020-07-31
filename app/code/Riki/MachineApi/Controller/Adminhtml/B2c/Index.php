<?php
namespace Riki\MachineApi\Controller\Adminhtml\B2c;

use Magento\Framework\Controller\ResultFactory;

class Index extends \Riki\MachineApi\Controller\Adminhtml\Action
{
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_MachineApi::machine_b2c_skus');
        $resultPage->addBreadcrumb(__('B2C Machine SKUs'), __('B2C Machine SKUs'));
        $resultPage->getConfig()->getTitle()->prepend(__('B2C Machine SKUs'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage B2C Machine SKUs'));
        return $resultPage;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::machine_b2c_skus');
    }
}
