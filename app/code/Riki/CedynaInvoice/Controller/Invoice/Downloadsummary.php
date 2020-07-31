<?php
namespace Riki\CedynaInvoice\Controller\Invoice;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Downloadsummary
 * @package Riki\CedynaInvoice\Controller\Invoice
 */
class Downloadsummary extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;
    /**
     * @var \Riki\CedynaInvoice\Model\ResourceModel\InvoiceFactory
     */
    protected $resourceInvoiceFactory;
    /**
     * @var \Riki\CedynaInvoice\Helper\Data
     */
    protected $helperData;

    /**
     * Downloadlist constructor.
     * @param Session $session
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Riki\CedynaInvoice\Model\ResourceModel\InvoiceFactory $invoiceFactory
     * @param \Riki\CedynaInvoice\Helper\Data $helperData
     */
    public function __construct(
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Riki\CedynaInvoice\Model\ResourceModel\InvoiceFactory $invoiceFactory,
        \Riki\CedynaInvoice\Helper\Data $helperData
    ) {
        parent::__construct($context);
        $this->customerSession = $session;
        $this->registry = $registry;
        $this->fileFactory = $fileFactory;
        $this->resourceInvoiceFactory = $invoiceFactory;
        $this->helperData = $helperData;
    }

    /**
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->customerSession->isLoggedIn()) {
            $this->_redirect('customer/account/login');
        }
        if (!$this->helperData->isEnable()) {
            $this->_redirect('customer/account/');
        }
        $this->registry->register('current_customer', $this->customerSession->getCustomer());
        return parent::dispatch($request);
    }

    /**
     * Download csv file action
     */
    public function execute()
    {
        $customerId = $this->customerSession->getId();
        $consumerDbId = $this->customerSession->getData('consumer_db_id') ?
            $this->customerSession->getData('consumer_db_id') :
            $customerId;

        $fileName = 'Cedyna_Invoice_'.$consumerDbId.'_invoices_summary.csv';
        $targetMonth = $this->_request->getParam('target');
        $invoiceFactory = $this->resourceInvoiceFactory->create();
        $invoices =  $invoiceFactory->getMonthlyInvoicesByCustomer($customerId, $targetMonth);
        $invoicesContent = $this->helperData->buildInvoiceSummaryContent($invoices);
        return $this->fileFactory->create(
            $fileName,
            $invoicesContent,
            DirectoryList::VAR_DIR
        );
    }
}
