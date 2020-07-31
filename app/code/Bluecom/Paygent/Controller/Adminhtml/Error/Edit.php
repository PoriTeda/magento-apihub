<?php
namespace Bluecom\Paygent\Controller\Adminhtml\Error;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Controller\ResultFactory;

class Edit extends Action
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var \Bluecom\Paygent\Model\ErrorFactory
     */
    protected $_errorFactory;


    /**
     * View constructor.
     * @param Context $context
     * @param Registry $registry
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Bluecom\Paygent\Model\ErrorFactory $errorFactory
    ) {
        $this->registry = $registry;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->_errorFactory = $errorFactory;
        parent::__construct($context);
    }

    /**
     * Paygent error detail information page
     *
     * @return \Magento\Backend\Model\View\Result\Forward
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('error_id');
        $model = $this->_errorFactory->create();
        if ($id) {
            /** @var \Magento\Backend\Model\View\Result\ForwardFactory $model */
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This paygent error handling no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('*/*/');
                return $resultRedirect;
            }else{
                $data = $this->getRequest()->getPostValue();
                if(isset($data['data'])){
                    $model->load($id);
                    $model->setData('backend_message',555);
                    $model->setData($data['data']);
                    if($model->save()){
                        $this->messageManager->addSuccess(__('Paygent error handling have been saved successfully.'));
                    }
                }
            }
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->addBreadcrumb(__('View Paygent Error Handling'),__('View Paygent Error Handling'));
        $resultPage->getConfig()->getTitle()->prepend(__('View Paygent Error Handling'));

        return $resultPage;
    }

}