<?php

namespace Riki\Subscription\Model\Emulator ;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;


class Order
    extends \Magento\Sales\Model\Order
{

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Sales\Api\InvoiceManagementInterface $invoiceManagement,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Magento\Sales\Model\ResourceModel\Order\Address\CollectionFactory $addressCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $historyCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory $memoCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory $trackCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollectionFactory,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productListFactory,
        \Riki\Subscription\Model\Emulator\ResourceModel\Order\Item\CollectionFactory $emulatorOrderItemCollectionFactory,
        \Riki\Subscription\Model\Emulator\ResourceModel\Order\Address\CollectionFactory $emulatorOrderAddressCollectionFactory,
        \Riki\Subscription\Model\Emulator\ResourceModel\Order\Payment\CollectionFactory $emulatorOrderPaymentCollectionFactory,
        \Riki\Subscription\Model\Emulator\ResourceModel\Order\Invoice\CollectionFactory $emulatorOrderInvoiceCollectionFactory,
        \Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment\CollectionFactory $emulatorOrderShipmentCollectionFactory,
        \Riki\Subscription\Model\Emulator\ResourceModel\Order\CollectionFactory $emulatorOrderCollectionFactory,
        \Riki\Subscription\Model\Emulator\Order\Status\HistoryFactory $emulatorOrderStatusHistoryFactory,
        \Riki\Subscription\Model\Emulator\ResourceModel\Order\Status\History\CollectionFactory $emulatorOrderStatusHistoryCollectionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $timezone, $storeManager, $orderConfig, $productRepository, $orderItemCollectionFactory, $productVisibility, $invoiceManagement, $currencyFactory, $eavConfig, $orderHistoryFactory, $addressCollectionFactory, $paymentCollectionFactory, $historyCollectionFactory, $invoiceCollectionFactory, $shipmentCollectionFactory, $memoCollectionFactory, $trackCollectionFactory, $salesOrderCollectionFactory, $priceCurrency, $productListFactory, $resource, $resourceCollection, $data);
        $this->_orderItemCollectionFactory = $emulatorOrderItemCollectionFactory;
        $this->_addressCollectionFactory = $emulatorOrderAddressCollectionFactory;
        $this->_paymentCollectionFactory = $emulatorOrderPaymentCollectionFactory;
        $this->_invoiceCollectionFactory = $emulatorOrderInvoiceCollectionFactory;
        $this->_shipmentCollectionFactory = $emulatorOrderShipmentCollectionFactory;
        $this->salesOrderCollectionFactory = $emulatorOrderCollectionFactory;
        $this->_orderHistoryFactory = $emulatorOrderStatusHistoryFactory;
        $this->_historyCollectionFactory = $emulatorOrderStatusHistoryCollectionFactory;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\ResourceModel\Order');
    }

    public function getAppliedTaxIsSaved(){
        return true; // prevent saving tax
    }

}