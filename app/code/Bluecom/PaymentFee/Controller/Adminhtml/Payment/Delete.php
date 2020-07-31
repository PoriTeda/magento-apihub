<?php

namespace Bluecom\PaymentFee\Controller\Adminhtml\Payment;

use Magento\Backend\App\Action\Context;

class Delete extends \Magento\Backend\App\Action
{

    /**
     * Edit constructor.
     *
     * @param Context                                    $context           context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory result page factory
     * @param \Magento\Framework\Registry                $registry          registry
     */
    public function __construct(
        Context $context,
        \Bluecom\PaymentFee\Model\PaymentFeeFactory $paymentFeeFactory
    ) {
        $this->paymentFeeFactory = $paymentFeeFactory;
        parent::__construct($context);
    }

    /**
     * Delete action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        // check if we know what should be deleted
        $entityId = $this->getRequest()->getParam('entity_id');
        /**
         * Redirect
         *
         * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect redirect
         */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($entityId) {
            try {
                //Init model and delete
                $model = $this->paymentFeeFactory->create();
                $model->load($entityId);
                $model->delete();
                //Display success message
                $this->messageManager->addSuccess(__('The brand has been deleted.'));

                //Go to grid
                return $resultRedirect->setPath('*/*/');

            } catch (\Exception $e) {
                //Display error message
                $this->messageManager->addError($e->getMessage());
                //Go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $entityId]);
            }
        }

        //Display error message
        $this->messageManager->addError(__('We can\'t find a brand to delete.'));
        //Go to grid
        return $resultRedirect->setPath('*/*');
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