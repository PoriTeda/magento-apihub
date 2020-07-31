<?php
namespace Riki\Subscription\Controller\Adminhtml\Multiple\Category\Campaign;

/**
 * Class Index
 * @package Riki\Subscription\Controller\Adminhtml\Multiple\Category\Campaign
 */
class Index extends AbstractCampaign
{

    /**
     * Implement Index action
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->initResultPage();
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Multiple Categories'));
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
