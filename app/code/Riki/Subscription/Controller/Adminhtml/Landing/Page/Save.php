<?php
namespace Riki\Subscription\Controller\Adminhtml\Landing\Page;

/**
 * Class Save
 * @package Riki\Subscription\Controller\Adminhtml\Landing\Page
 */
class Save extends AbstractPage
{
    /**
     * Implement Save action
     */
    public function execute()
    {
        $redirectBack = $this->getRequest()->getParam('back', false);
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if data sent
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $id = $this->getRequest()->getParam('landing_page_id');
            if (isset($data['name']) && strlen($data['name']) > 255) {
                $this->messageManager->addErrorMessage(__('Landing page name must be less or equal than 255 symbols.'));
                return $resultRedirect->setPath('*/*/');
            }
            $landingPageModel = $this->landingPageFactory->create();
            if ($id) {
                try {
                    $landingPageModel->load($id);
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
            if (!$landingPageModel->getLandingPageId() && $id) {
                $this->messageManager->addError(__('This landing page no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
            try {
                $landingPageModel->addData($data)->save();
                // display success message
                $this->messageManager->addSuccess(__('You saved the landing page.'));
                // clear previously saved data from session
                $this->_session->setFormData(false);
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // save data in session
                $this->_session->setFormData($data);
                $this->logger->critical($e);
            }
            return $redirectBack?
                $resultRedirect->setPath('*/*/edit', [
                    'landing_page_id' => $landingPageModel->getLandingPageId()
                ])
                : $resultRedirect->setPath('*/*/');
        } else {
            $this->messageManager->addError('No data to save');
            return $resultRedirect->setPath('*/*/');
        }
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
