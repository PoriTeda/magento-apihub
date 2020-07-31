<?php

namespace Riki\ArReconciliation\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Edit extends \Magento\Backend\App\Action
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
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\Session $modelSession
     */
    protected $_modelSession;

    /**
     * @var \Riki\ArReconciliation\Model\ImportFactory
     */
    protected $_importFactory;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Riki\ArReconciliation\Model\ImportFactory $importFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->_modelSession = $context->getSession();
        $this->_importFactory = $importFactory;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_ArReconciliation::import_payment_csv');
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
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Riki_ArReconciliation::import_payment_csv_file');
        return $resultPage;
    }

    /**
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        /*  1. Get ID and create model */
        $id = $this->getRequest()->getParam('id');

        $model = $this->_importFactory->create();

        /* 2. Initial checking */
        if ($id)
        {
            $model->load($id);

            if (!$model->getId())
            {
                $this->messageManager->addError(__('This importing no longer exists.'));
                /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        /* 3. Set entered data if was error when we do save */

        $data = $this->_modelSession->getFormData(true);

        if (!empty($data))
        {
            $model->setData($data);
        }

        /* 4. Register model to use later in blocks */
        $this->_coreRegistry->register('arreconciliation_import', $model);

        /* 5. Build edit form */
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */

        $resultPage = $this->_initAction();

        $resultPage->addBreadcrumb(
            $id ? __('Collected Importing') : __('New Importing'),
            $id ? __('Collected Importing') : __('New Importing')
        );

        $resultPage->getConfig()->getTitle()->prepend(__('Importing'));

        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? $model->getTitle() : __('New Importing'));

        return $resultPage;
    }
}
