<?php

namespace Riki\Customer\Block\Adminhtml\Edit\Tab\View;

/**
 * Class PersonalInfo
 * @package Riki\Customer\Block\Adminhtml\Edit\Tab\View
 */
class PersonalInfo extends \Magento\Customer\Block\Adminhtml\Edit\Tab\View\PersonalInfo
{
    /**
     * @var \Riki\CedynaInvoice\Helper\Data
     */
    protected $cedynaInvoiceHelperData;
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
     * PersonalInfo constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Customer\Api\AccountManagementInterface $accountManagement
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Customer\Model\Logger $customerLogger
     * @param \Riki\CedynaInvoice\Helper\Data $cedynaInvoiceHelperData
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Riki\CedynaInvoice\Model\ResourceModel\InvoiceFactory $invoiceFactory
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Customer\Helper\Address $addressHelper,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Customer\Model\Logger $customerLogger,
        \Riki\CedynaInvoice\Helper\Data $cedynaInvoiceHelperData,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Riki\CedynaInvoice\Model\ResourceModel\InvoiceFactory $invoiceFactory,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $accountManagement,
            $groupRepository,
            $customerDataFactory,
            $addressHelper,
            $dateTime,
            $registry,
            $addressMapper,
            $dataObjectHelper,
            $customerLogger,
            $data
        );
        $this->currentCustomer = $currentCustomer;
        $this->resourceInvoiceFactory = $invoiceFactory;
        $this->priceHelper = $priceHelper;
        $this->cedynaInvoiceHelperData = $cedynaInvoiceHelperData;
    }

    protected function _prepareLayout()
    {
        $this->setTemplate('Riki_Customer::tab/view/personal_info.phtml');
        return parent::_prepareLayout();
    }


    /**
     * Check Cedyna Customer
     * @return bool
     */
    public function canCedynaInvoice()
    {
        return $this->cedynaInvoiceHelperData->canCedynaInvoice($this->getCustomer());
    }
    /**
     * Get customer business code
     * @return mixed|string
     */
    public function getCustomerBusinessCode()
    {
        return $this->cedynaInvoiceHelperData->getCustomerBusinessCode($this->getCustomer());
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
     * Format invoice date phrase
     * @param $invoice
     * @return \Magento\Framework\Phrase
     */
    public function formatInvoiceDate($invoice)
    {
        return $this->cedynaInvoiceHelperData->formatInvoiceDate($invoice);
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
        $latestInvoice = array_shift($invoices);
        return $this->formatPrice($latestInvoice['total']);
    }
    /**
     * Format custom date
     * @param $date
     * @return false|string
     */
    public function formatCustomDate($date)
    {
        return $this->dateTime->gmDate('Y/m/d', strtotime($date));
    }
    /**
     * @param $invoice
     * @return string
     */
    public function getDetailInvoiceUrl($invoice)
    {
        $customerId = $this->getCustomer()->getId();
        $targetMonth = $invoice['target_month'];
        $params = ['id'=>$customerId, 'target'=>$targetMonth];
        return $this->getUrl('cedyna_invoice/customer/index/', $params);
    }
}