<?php

namespace Riki\CsvOrderMultiple\Controller\Adminhtml\Import;

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
     * @return bool
     */

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_CsvOrderMultiple::import_order_csv');
    }

    /**
     * @return $this|\Magento\Backend\Model\View\Result\Page|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Importing'));
        $resultPage->getConfig()->getTitle()->prepend(__('Upload Multiple Orders CSV'));
        return $resultPage;
    }
}
