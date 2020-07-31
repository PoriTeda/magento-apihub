<?php
namespace Riki\Subscription\Controller\Adminhtml\Landing\Page;

/**
 * Class Edit
 * @package Riki\Subscription\Controller\Adminhtml\Landing\Page
 */
class Edit extends AbstractPage
{
    /**
     * Implement edit action
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('landing_page_id');
        $model = $this->landingPageFactory->create();
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This page no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $data = $this->_session->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->registry->register('landing_page', $model);
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->initResultPage();
        $resultPage->addBreadcrumb(
            $id ? $model->getLandingPageId() : __('New Landing Page'),
            $id ? $model->getLandingPageId() : __('New Landing Page')
        );

        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getLandingPageName() : __('New Landing Page')
        );
        return $resultPage;
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
