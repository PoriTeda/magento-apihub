<?php
namespace Riki\CedynaInvoice\Block;

use Riki\CedynaInvoice\Model\Source\Config\DataType;

/**
 * Class View
 * @package Riki\CedynaInvoice\Block
 */
class View extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;
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
     * View constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Riki\CedynaInvoice\Model\ResourceModel\InvoiceFactory $invoiceFactory
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\CedynaInvoice\Helper\Data $helperData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Riki\CedynaInvoice\Model\ResourceModel\InvoiceFactory $invoiceFactory,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Framework\Registry $registry,
        \Riki\CedynaInvoice\Helper\Data $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->currentCustomer = $currentCustomer;
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
        try {
            return $this->currentCustomer->getCustomer();
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
        $targetMonth = $this->registry->registry('target_month');
        if ($this->getCustomer()) {
            $customerId = $this->getCustomer()->getId();
            $invoiceFactory = $this->resourceInvoiceFactory->create();
            return $invoiceFactory->getMonthlyInvoicesByCustomer($customerId, $targetMonth);
        }
        return false;
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
        return $this->priceHelper->currency($price, true, false);
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
        $targetMonth = $this->registry->registry('target_month');
        $path = 'cedyna_invoice/invoice/downloadlist/';
        return $this->getUrl($path, ['target' => $targetMonth]);
    }

    /**
     * Url to download CSV invoice summary
     * @return string
     */
    public function getDownloadInvoiceSummaryUrl()
    {
        $targetMonth = $this->registry->registry('target_month');
        $path = 'cedyna_invoice/invoice/downloadsummary/';
        return $this->getUrl($path, ['target' => $targetMonth]);
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
        return $this->formatDate($invoice['order_created_date']);
    }

    /**
     * @param $invoice
     * @return string
     */
    public function getShipmentDate($invoice)
    {
        $shipmentDate = $this->helperData->getShipmentDate($invoice);
        if ($shipmentDate) {
            return $this->formatDate($shipmentDate);
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
