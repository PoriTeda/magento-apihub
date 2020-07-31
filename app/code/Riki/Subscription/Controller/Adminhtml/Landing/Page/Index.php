<?php
namespace Riki\Subscription\Controller\Adminhtml\Landing\Page;

/**
 * Class Index
 * @package Riki\Subscription\Controller\Adminhtml\Landing\Page
 */
class Index extends AbstractPage
{

    /**
     * Implement Index action
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->initResultPage();
        $resultPage->getConfig()->getTitle()->prepend(__('Landing Page Management'));
        return $resultPage;
    }

    /**
     * Check ACL permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE_VIEW);
    }
}
