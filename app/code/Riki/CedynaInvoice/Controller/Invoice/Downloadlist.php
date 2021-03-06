<?php
namespace Riki\CedynaInvoice\Controller\Invoice;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Downloadlist
 * @package Riki\CedynaInvoice\Controller\Invoice
 */
class Downloadlist extends \Magento\Framework\App\Action\Action
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

        $fileName = 'Cedyna_Invoice_'.$consumerDbId.'_invoices.csv';
        $targetMonth = $this->_request->getParam('target');
        $invoiceFactory = $this->resourceInvoiceFactory->create();
        $invoices =  $invoiceFactory->getMonthlyInvoicesByCustomer($customerId, $targetMonth);
        $csvHeader = $this->helperData->encodeByLocale([
            __('INVOICE DETAIL SHIPMENT INCREMENT ID'),
            __('INVOICE DETAIL ORDER CREATED'),
            __('SHIPPED OUT DATE / RETURNED DATE'),
            __('INVOICE DETAIL PRODUCT LINE NAME'),
            __('INVOICE DETAIL UNIT PRICE'),
            __('INVOICE DETAIL QTY'),
            __('INVOICE DETAIL ROW TOTAL'),
            __('INVOICE DETAIL SHIPPING ADDRESS')
        ]);
        $invoicesRows[] = implode(',', $csvHeader);
        $invoicesContent = '';
        if ($invoices) {
            foreach ($invoices as $invoice) {
                if ($invoice['data_type'] ==
                    \Riki\CedynaInvoice\Model\Source\Config\DataType::DATA_TYPE_OPTION_SALES) {
                    $rowTotal = (int)$invoice['row_total'];
                } else {
                    $rowTotal = -1*(int)$invoice['row_total'];
                }
                $shipmentDate = $this->helperData->getShipmentDate($invoice);
                if ($shipmentDate) {
                    $shipmentDate = $this->helperData->formatDate($shipmentDate);
                }
                $tempRow = $this->helperData->encodeByLocale([
                    $invoice['increment_id'],
                    $this->helperData->formatDate($invoice['order_created_date']),
                    $shipmentDate,
                    $invoice['product_line_name'],
                    (int)$invoice['unit_price'],
                    (int)$invoice['qty'],
                    (int)$rowTotal,
                    $invoice['riki_nickname']
                ]);
                $invoicesRows[] = implode(',', $tempRow);
            }
            $invoicesContent = implode("\n", $invoicesRows);
        }
        return $this->fileFactory->create(
            $fileName,
            $invoicesContent,
            DirectoryList::VAR_DIR
        );
    }
}
