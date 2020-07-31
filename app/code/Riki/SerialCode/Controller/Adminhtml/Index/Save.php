<?php

namespace Riki\SerialCode\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Riki\SerialCode\Model\SerialCodeFactory;

class Save extends  \Magento\Backend\App\Action
{
    protected $_serialCodeFactory;

    /**
     * Save constructor.
     *
     * @param Action\Context $context
     * @param SerialCodeFactory $serialCodeFactory
     */
    public function __construct(
        Action\Context $context,
        \Riki\SerialCode\Model\SerialCodeFactory $serialCodeFactory
    )
    {
        $this->_serialCodeFactory = $serialCodeFactory;
        parent::__construct($context);
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


    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $id = $this->getRequest()->getParam('id');
            /** @var \Riki\SerialCode\Model\SerialCode $model */
            $model = $this->_serialCodeFactory->create();
            $model->load($id);
            if (!$model->getId() && $id) {
                $this->messageManager->addError(__('This serial code no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
            $model->setData($data);
            if ($data['campaign_limit'] == '') {
                $model->setData('campaign_limit', null);
            }
            if ($data['point_expiration_period'] == '') {
                $model->setData('point_expiration_period', null);
            }
            try {
                if ($model->getId()) {
                    //update one record
                    $model->save();
                    $msg = __('You saved the serial code.');
                } else {
                    //generate multiple serial code
                    $model->generateSerialCode();
                    $msg = __('You generated %1 serial code.', $model->getData('number_of_generate'));
                }
                $this->messageManager->addSuccess($msg);
                $this->_session->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_session->setFormData($data);
                return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            }
            return $resultRedirect->setPath('*/*/');
        }
    }

}