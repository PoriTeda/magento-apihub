<?php
namespace Riki\CedynaInvoice\Block\Adminhtml;

use Riki\CedynaInvoice\Model\Source\Config\DataType;

/**
 * Class Customer
 * @package Riki\CedynaInvoice\Block\Adminhtml
 */
class Customer extends \Magento\Backend\Block\Widget
{
    protected $_template = 'customer_invoices.phtml';
    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $customerRepository;
    /**
     * @var \Riki\CedynaInvoice\Model\ResourceModel\InvoiceFactory
     */
    protected $resourceInvoiceFactory;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \Riki\CedynaInvoice\Helper\Data
     */
    protected $helperData;

    /**
     * Customer constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\CedynaInvoice\Model\ResourceModel\InvoiceFactory $invoiceFactory
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\CedynaInvoice\Helper\Data $helperData
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\CedynaInvoice\Model\ResourceModel\InvoiceFactory $invoiceFactory,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Framework\Registry $registry,
        \Riki\CedynaInvoice\Helper\Data $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerRepository = $customerRepository;
        $this->resourceInvoiceFactory = $invoiceFactory;
        $this->registry = $registry;
        $this->priceHelper = $priceHelper;
        $this->helperData = $helperData;
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomer()
    {
        $customerId = $this->getData('customerId');
        try {
            return $this->customerRepository->getById($customerId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Get customer business code
     * @return mixed|string
     */
    public function getCustomerBusinessCode()
    {
        return $this->helperData->getCustomerBusinessCode($this->getCustomer());
    }

    /**
     * Get all Cedyna invoices of customer
     * @return bool
     */
    public function getCustomerInvoices()
    {
        if ($this->getCustomer()) {
            $customerId = $this->getCustomer()->getId();
            $invoiceFactory = $this->resourceInvoiceFactory->create();
            return $invoiceFactory->getInvoicesByCustomer($customerId);
        }
        return false;
    }

    /**
     * Get all Cedyna invoices of customer
     * @return bool
     */
    public function getMonthlyInvoices()
    {
        $targetMonth = $this->getData('target');
        $customerId = $this->getData('customerId');
        $invoiceFactory = $this->resourceInvoiceFactory->create();
        return $invoiceFactory->getMonthlyInvoicesByCustomer($customerId, $targetMonth);
    }
    /**
     * Format invoice date phrase
     * @param $invoice
     * @return \Magento\Framework\Phrase
     */
    public function formatInvoiceDate($invoice)
    {
        return $this->helperData->formatInvoiceDate($invoice);
    }
    /**
     * @param null $price
     * @return float|null|string
     */
    public function formatPrice($price = null)
    {
        if ($price != null) {
            return $this->priceHelper->currency($price, true, false);
        }
        return null;
    }

    /**
     * @param $invoices
     * @return float|null|string
     */
    public function getTotalInvoice($invoices)
    {
        $total = 0;
        foreach ($invoices as $invoice) {
            $total += $invoice['total'];
        }
        return $this->formatPrice($total);
    }
    /**
     * Url to download CSV invoice list
     * @return string
     */
    public function getDownloadInvoiceListUrl()
    {
        $params = ['id'=> $this->getData('customerId'), 'target'=> $this->getData('target')];
        $path = 'cedyna_invoice/customer/downloadlist/';
        return $this->getUrl($path, $params);
    }

    /**
     * Url to download CSV invoice summary
     * @return string
     */
    public function getDownloadInvoiceSummaryUrl()
    {
        $params = ['id'=> $this->getData('customerId'), 'target'=> $this->getData('target')];
        $path = 'cedyna_invoice/customer/downloadsummary/';
        return $this->getUrl($path, $params);
    }

    /**
     * Format custom date
     * @param $date
     * @return false|string
     */
    public function formatCustomDate($date)
    {
        return date('Ymd', strtotime($date));
    }
    /**
     * @param $invoices
     * @return array
     */
    public function getInvoicesInformation($invoices)
    {
        return $this->helperData->buildInvoicesInformation($invoices);
    }

    /**
     * @param $invoice
     * @return false|string
     */
    public function getOrderCreatedDate($invoice)
    {
        return $this->formatCustomDate($invoice['order_created_date']);
    }

    /**
     * @param $invoice
     * @return string
     */
    public function getShipmentDate($invoice)
    {
        $shipmentDate = $this->helperData->getShipmentDate($invoice);
        if ($shipmentDate) {
            return $this->formatCustomDate($shipmentDate);
        }
        return '';
    }

    /**
     * @param $dataType
     * @return bool
     */
    public function highlightItemPrice($dataType)
    {
        if (DataType::DATA_TYPE_OPTION_SALES == $dataType) {
            return false;
        }
        return true;
    }
}
