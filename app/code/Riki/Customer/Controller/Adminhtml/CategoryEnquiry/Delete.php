<?php
namespace Riki\Customer\Controller\Adminhtml\CategoryEnquiry;
use Magento\Backend\App\Action;

class Delete extends Action{

    protected $_model;
    /**
     * @param Action\Context $context
     * @param \Riki\Customer\Model\CategoryEnquiry $model
     */
    public function __construct(
        Action\Context $context,
        \Riki\Customer\Model\CategoryEnquiry $model
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
        return $this->_authorization->isAllowed('Riki_Customer::category_delete');
    }
    /*
     * Delete action
     * @return \Magento\Framework\Controller\ResultInterface
     *
     * */
    public function execute()
    {
        // TODO: Implement execute() method.
        $id = $this->getRequest()->getParam('id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if($id){
            try{
                $model = $this->_model;
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('category deleted'));
                return $resultRedirect->setPath('*/*/');
            }catch (\Exception $e){
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*.*/edit',['id' => $id]);
            }
        }
        $this->messageManager->addError(__('category does not exist'));
        return $resultRedirect->setPath('*/*/');
    }
}