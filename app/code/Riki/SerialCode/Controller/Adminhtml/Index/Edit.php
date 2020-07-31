<?php

namespace Riki\SerialCode\Controller\Adminhtml\Index;

class Edit extends \Riki\SerialCode\Controller\Adminhtml\Index
{
    /**
     * @return $this|\Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        // 1. Get ID and create model
        $model = $this->_serialCodeFactory->create();
        $id = $this->_request->getParam('id');

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This serial code no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        // 3. Set entered data if was error when we do save
        $data = $this->_modelSession->getFormData(true);

        if (!empty($data)) {
            $model->setData($data);
        }

        // 4. Register model to use later in blocks
        $this->_coreRegistry->register('serial_code', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();

        // 5. Build edit form
        $resultPage->getConfig()->getTitle()->prepend(__('Serial codes'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? $model->getSerialCode() : __('Generate serial code'));
        return $resultPage;
    }

    /**
     * Returns result of current user permission check on resource and privilege
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SerialCode::serial_code_action_save');
    }

}