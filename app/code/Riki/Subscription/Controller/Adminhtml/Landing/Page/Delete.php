<?php

namespace Riki\Subscription\Controller\Adminhtml\Landing\Page;

use Exception;

/**
 * Class Delete
 *
 * @package Riki\Subscription\Controller\Adminhtml\Landing\Page
 */
class Delete extends AbstractPage
{

    /**
     * Implement the action name
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('landing_page_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $model = $this->landingPageFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('The page has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->logger->critical($e);
                return $resultRedirect->setPath('*/*/edit', ['landing_page_id' => $id]);
            }
        }
        $this->messageManager->addError(__('This page no longer exists.'));
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Check ACL permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE_DELETE);
    }
}
