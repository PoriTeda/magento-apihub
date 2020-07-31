<?php

namespace Bluecom\PaymentFee\Controller\Adminhtml\Payment;

use \Magento\Backend\App\Action\Context;

class Save extends \Magento\Backend\App\Action
{

    /* @var \Bluecom\PaymentFee\Model\PaymentFee */
    protected $paymentFeeFactory;

    /* @var \Magento\Backend\Model\Session */
    protected $session;

    public function __construct(
        \Bluecom\PaymentFee\Model\PaymentFeeFactory $paymentFeeFactory,
        Context $context
    )
    {
        $this->session = $context->getSession();
        $this->paymentFeeFactory = $paymentFeeFactory;
        parent::__construct($context);
    }

    /**
     * Save payment
     *
     * @return $this
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /**
         * Redirect
         *
         * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect result redirect
         */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $model = $this->paymentFeeFactory->create();
            $entityId = $this->getRequest()->getParam('entity_id');
            if ($entityId) {
                $model->load($entityId);
            }
            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccess(__('Your payment fee saved.'));
                $this->session->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('paymentfee/*/edit', ['entity_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the post.'));
            }
            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('paymentfee/*/edit', ['entity_id' => $this->getRequest()->getParam('entity_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Set allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bluecom_PaymentFee::index');
    }
}