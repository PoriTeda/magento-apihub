<?php
namespace Riki\CedynaInvoice\Controller\Adminhtml\Invoice;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Riki\CedynaInvoice\Model\InvoiceFactory;
use Magento\Framework\App\RequestInterface;

/**
 * Class AbstractInvoice
 * @package Riki\CedynaInvoice\Controller\Adminhtml\Invoice
 */
abstract class AbstractInvoice extends Action
{
    /**
     * ACL name
     */
    const ADMIN_RESOURCE = 'Riki_CedynaInvoice::invoice';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var InvoiceFactory
     */
    protected $invoiceFactory;
    /**
     * @var \Riki\CedynaInvoice\Helper\Validator
     */
    protected $validator;
    /**
     * @var \Riki\CedynaInvoice\Helper\Data
     */
    protected $helperData;

    /**
     * AbstractInvoice constructor.
     * @param Action\Context $context
     * @param Registry $registry
     * @param ForwardFactory $resultForwardFactory
     * @param LoggerInterface $logger
     * @param InvoiceFactory $invoiceFactory
     * @param \Riki\CedynaInvoice\Helper\Validator $validator
     * @param \Riki\CedynaInvoice\Helper\Data $helperData
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        ForwardFactory $resultForwardFactory,
        LoggerInterface $logger,
        InvoiceFactory $invoiceFactory,
        \Riki\CedynaInvoice\Helper\Validator $validator,
        \Riki\CedynaInvoice\Helper\Data $helperData
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->invoiceFactory = $invoiceFactory;
        $this->validator = $validator;
        $this->helperData = $helperData;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function initResultPage()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(__('Cedyna Invoice management'));
        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
    /**
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->helperData->isEnable()) {
            $this->messageManager->addWarningMessage(__('Module Cedyna Invoice has been disabled.'));
            $this->_redirect('adminhtml/dashboard/index');
        }
        return parent::dispatch($request);
    }
}
