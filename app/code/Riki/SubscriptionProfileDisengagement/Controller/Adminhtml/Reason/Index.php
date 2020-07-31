<?php
namespace Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Reason;

class Index extends \Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Reason
{

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('SubscriptionProfileDisengagement::reason');
        $resultPage->getConfig()->getTitle()->prepend(__('Subscription Profile Disengagement Reason'));
        return $resultPage;
    }

    /**
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionProfileDisengagement::reason');
    }

}