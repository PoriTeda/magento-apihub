<?php
namespace Riki\Customer\Controller\Adminhtml\EnquiryHeader;
use Magento\Backend\App\Action;

class Delete extends Action{
    /**
     * @var \Riki\Customer\Model\EnquiryHeader
     */
    protected $_model;
    /**
     * @param Action\Context $context
     * @param \Riki\Customer\Model\EnquiryHeader $model
     */
    public function __construct(
        Action\Context $context,
        \Riki\Customer\Model\EnquiryHeader $model
    )
    {
        $this->_model = $model;
        parent::__construct($context);
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::enquiryheader_delete');
    }

    /*
     * Delete action
     * @return \Magento\Framework\Controller\ResultInterface
     *
     * */

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if($id){
            try{
                $model = $this->_model;
                $model->load($id);
                $model->delete();

                $this->messageManager->addSuccess(__('Enquiry deleted'));

                //redirect link order,customer,enquiry
                return $this->checkReturnRedirectLink($resultRedirect);
            }catch (\Exception $e){

                $this->messageManager->addError($e->getMessage());

                //redirect link edit if has error
                return $this->checkReturnRedirectLink($resultRedirect,'edit');
            }
        }
        $this->messageManager->addError(__('Enquiry does not exist'));

        //redirect link management
        return $this->checkReturnRedirectLink($resultRedirect);
    }

    /**
     * Check return redirect link
     *
     * @param $resultRedirect
     * @param null $linkDefault
     * @return mixed
     */
    public function checkReturnRedirectLink($resultRedirect,$linkDefault=null){
        $id = $this->getRequest()->getParam('id');
        $orderId = $this->getRequest()->getParam('view_order_id');
        $viewCustomerId = $this->getRequest()->getParam('view_customer_id');

        if($orderId !=null){
            return $resultRedirect->setPath('sales/order/view',['order_id' => $orderId]);
        } else if($viewCustomerId !=null) {
            return $resultRedirect->setPath('customer/index/edit',['id' => $viewCustomerId]);
        }else{
            //redirect link edit if has error
            if($linkDefault=='edit'){
                return $resultRedirect->setPath('*.*/edit',['id' => $id]);
            }
        }
        return $resultRedirect->setPath('*/*/');
    }


}