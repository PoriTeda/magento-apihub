<?php
namespace Riki\CedynaInvoice\Block;

/**
 * Class Invoice
 * @package Riki\CedynaInvoice\Block
 */
class Invoice extends \Magento\Framework\View\Element\Template
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
     * @var \Riki\CedynaInvoice\Helper\Data
     */
    protected $helperData;

    /**
     * Invoice constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Riki\CedynaInvoice\Model\ResourceModel\InvoiceFactory $invoiceFactory
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Riki\CedynaInvoice\Helper\Data $helperData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Riki\CedynaInvoice\Model\ResourceModel\InvoiceFactory $invoiceFactory,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Riki\CedynaInvoice\Helper\Data $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->currentCustomer = $currentCustomer;
        $this->resourceInvoiceFactory = $invoiceFactory;
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
        $latestInvoice = array_shift($invoices);
        return $this->formatPrice($latestInvoice['total']);
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
}
