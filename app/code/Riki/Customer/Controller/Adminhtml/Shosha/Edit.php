<?php
namespace Riki\Customer\Controller\Adminhtml\Shosha;

use Magento\Backend\App\Action;

class Edit extends Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Riki\Customer\Model\Shosha
     */
    protected $_model;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Customer\Model\Shosha $model
     */

    /**
     * Edit Shosha
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Riki\Customer\Model\Shosha $model
    ) {

        $this->_resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->_model = $model;
        parent::__construct($context);
    }

    /**
     * Init actions
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Riki_Customer::shosha')
            ->addBreadcrumb(__('Shosha'), __('Shosha'))
            ->addBreadcrumb(__('Manage Shosha'), __('Manage Shosha'));
        return $resultPage;
    }
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        $model = $this->_model;

        // If you have got an id, it's edition
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This shosha business code does not exist.'));
                /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->_getSession()->getFormData(true);

        $orderid = $this->getRequest()->getParam('orderid');
        if($orderid){
            $data['order_id'] = $orderid;
        }

        $customerid = $this->getRequest()->getParam('customerid');
        if($customerid){
            $data['customer_id'] = $customerid;
        }

        if (!empty($data)) {
            $model->setData($data);
        }
        $resultPage = $this->_initAction();
        $this->_coreRegistry->register('shoshacustomer', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage->addBreadcrumb(
            $id ? __('Edit Shosha Business Code') : __('Add New Shosha Business Code'),
            $id ? __('Edit Shosha Business Code') : __('Add New Shosha Business Code')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Shosha Business Code'));
        $resultPage->getConfig()->getTitle()
            ->prepend($model->getId() ? __('Edit Shosha Business Code') : __('Add New Shosha Business Code'));

        return $resultPage;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::shoshacustomer_save');
    }


}