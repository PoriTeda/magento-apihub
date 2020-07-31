<?php
namespace Riki\Subscription\Controller\Adminhtml\Multiple\Category\Campaign;

/**
 * Class NewAction
 * @package Riki\Subscription\Controller\Adminhtml\Multiple\Category\Campaign
 */
class NewAction extends AbstractCampaign
{
    /**
     * Implement New action
     */
    public function execute()
    {
        return $this->_forward('edit');
    }

    /**
     * Check ACL permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE_EDIT);
    }
}
