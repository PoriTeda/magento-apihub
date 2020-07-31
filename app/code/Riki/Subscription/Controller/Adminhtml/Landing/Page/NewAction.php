<?php
namespace Riki\Subscription\Controller\Adminhtml\Landing\Page;

/**
 * Class NewAction
 * @package Riki\Subscription\Controller\Adminhtml\Landing\Page
 */
class NewAction extends AbstractPage
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
