<?php

namespace Riki\CsvOrderMultiple\Cron;

use Magento\Framework\Exception\LocalizedException;
use Riki\AdvancedInventory\Model\Assignation;
use Riki\Sales\Model\Config\Source\OrderType;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Riki\CsvOrderMultiple\Api\Data\StatusInterface as StatusImport;

class ImportOrders
{
    const CSV_ORDER_IMPORT_FLAG = 'is_csv_import_order_flag';
    const CSV_ORDER_IMPORT_DISABLE_FRAUD = 'disable_riki_check_fraud_sales_order_place_before';
    const IMPORT_ASSIGNED_WAREHOUSE_ID_KEY = 'import_assigned_warehouse_id';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $loggerOrder;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var array
     */
    protected $dataOrderImport = [];

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quote;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Sales\Model\Service\OrderService
     */
    protected $orderService;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var array
     */
    protected $listDeliveryTIme = [];

    /**
     * @var array
     */
    protected $arrMessageSuccess = [];

    /**
     * @var array
     */
    protected $arrMessageErrors = [];

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $eventManager;

    /**
     * @var array
     */
    protected $listReplacementReason = [];
    /**
     * @var \Riki\CsvOrderMultiple\Model\CreateCustomer
     */
    protected $createCustomer;

    /**
     * @var string
     */
    protected $createdBy;

    /**
     * @var array
     */
    protected $listGiftWrapping = [];

    /**
     * @var array
     */
    protected $arrGiftCode = [];

    /**
     * @var \Riki\BackOrder\Helper\Admin
     */
    protected $helperBackOrder;

    /**
     * @var \Riki\Coupons\Helper\Coupon
     */
    protected $couponHelper;

    /**
     * @var array
     */
    protected $arrCustomer = [];

    /**
     * @var \Riki\Customer\Model\ShoshaFactory
     */
    protected $shoshaFactory;

    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    protected $addressFactory;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $directoryWrite;

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper
     */
    protected $fileHelper;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $dataObject;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    /**
     * ImportOrders constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Riki\CsvOrderMultiple\Logger\LoggerOrder $logger
     * @param \Magento\Quote\Model\QuoteFactory $quote
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Sales\Model\Service\OrderService $orderService
     * @param \Magento\Framework\Registry $registry
     * @param EventManager $eventManager
     * @param \Riki\CsvOrderMultiple\Model\CreateCustomer $createCustomer
     * @param \Riki\BackOrder\Helper\Admin $helperBackOrder
     * @param \Riki\Coupons\Helper\Coupon $couponHelper
     * @param \Riki\Customer\Model\ShoshaFactory $shoshaFactory
     * @param \Magento\Quote\Model\Quote\AddressFactory $addressFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileExportHelper
     * @param \Magento\Framework\DataObject $dataObject
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\CsvOrderMultiple\Logger\LoggerOrder $logger,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Magento\Framework\Registry $registry,
        EventManager $eventManager,
        \Riki\CsvOrderMultiple\Model\CreateCustomer $createCustomer,
        \Riki\BackOrder\Helper\Admin $helperBackOrder,
        \Riki\Coupons\Helper\Coupon $couponHelper,
        \Riki\Customer\Model\ShoshaFactory $shoshaFactory,
        \Magento\Quote\Model\Quote\AddressFactory $addressFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileExportHelper,
        \Magento\Framework\DataObject $dataObject,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        $this->loggerOrder = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->resourceConnection = $resourceConnection;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->registry = $registry;
        $this->eventManager = $eventManager;
        $this->createCustomer = $createCustomer;
        $this->helperBackOrder = $helperBackOrder;
        $this->couponHelper = $couponHelper;
        $this->shoshaFactory = $shoshaFactory;
        $this->addressFactory = $addressFactory;
        $this->fileHelper = $fileExportHelper;
        $this->directoryWrite = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->dataObject = $dataObject;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\Serialize\Serializer\Json::class
        );
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /**
         * Check cron state in order to avoid overlap.
         */
         $this->checkCronRun();

        /**
         * Load data need to import
         */
        $arrOrderImport = $this->getListDataImport();
        $this->getListDeliveryTimeSlot();
        $this->getListReplacementReason();
        $this->getListGiftWrapping();
        $this->setRegistryNeedToImport();

        if (is_array($arrOrderImport) && !empty($arrOrderImport)) {
            foreach ($arrOrderImport as $item) {
                $rowId = $item['entity_id'];
                $this->createdBy = $item['uploaded_by'];
                $dataJsonOrder = \Zend_Json_Decoder::decode($item['data_json_order']);

                /**
                 * Reset promotion
                 */
                $this->registry->unregister('reset_has_items');
                $this->registry->register('reset_has_items', 'reset_has_items');

                if (isset($dataJsonOrder['items'])) {
                    /**
                     * Process import order
                     */
                    try {

                        /**
                         * Update status processing
                         */
                        $this->updateStatusRecord($rowId, StatusImport::IMPORT_PROCESSING);

                        $orderIncrementId = $this->processOrderImport($rowId, $dataJsonOrder);

                        /**
                         * Process data after import order
                         */
                        $this->processAfterImportOrderCsv($orderIncrementId, $rowId, $dataJsonOrder);
                    } catch (\Exception $e) {
                        $this->arrMessageErrors[$rowId] = $e->getMessage();
                        /**
                         * Update status error
                         */
                        $this->updateStatusRecord($rowId, StatusImport::IMPORT_FAIL, $e->getMessage());
                        /**
                         * echo "[Entity_id $rowId]" . $e->getMessage() . "\n";
                         */
                    }
                } else {
                    /**
                     * Update status error
                     */
                    $errorMessage = __('Please check data before import');
                    $this->updateStatusRecord($rowId, StatusImport::IMPORT_FAIL, $errorMessage);
                }
            }
        }

        $this->deleteLockFolder();
    }

    /**
     * Set registry for import order
     */
    public function setRegistryNeedToImport()
    {
        /**
         * Disable Observer FraudOrderPlaceBefore.php
         */
        $this->registry->unregister(self::CSV_ORDER_IMPORT_DISABLE_FRAUD);
        $this->registry->register(self::CSV_ORDER_IMPORT_DISABLE_FRAUD, self::CSV_ORDER_IMPORT_DISABLE_FRAUD);

        /**
         * Flag reset promotion when place multiple order
         */
        $this->registry->unregister(self::CSV_ORDER_IMPORT_FLAG);
        $this->registry->register(self::CSV_ORDER_IMPORT_FLAG, self::CSV_ORDER_IMPORT_FLAG);
    }

    /**
     * Process after import order csv
     *
     * @param $orderIncrementId
     * @param $rowId
     * @param $dataJsonOrder
     */
    public function processAfterImportOrderCsv($orderIncrementId, $rowId, $dataJsonOrder)
    {
        if ($orderIncrementId) {
            $this->arrMessageSuccess[$orderIncrementId] = $rowId;

            /**
             * Update status success
             */
            $this->updateStatusRecord($rowId, StatusImport::IMPORT_SUCCESS);

            $messageSuccess = "[Entity_id $rowId - Order increment ID  $orderIncrementId] Import order successfully";
            $this->loggerOrder->logSuccess(
                $messageSuccess,
                [
                    'order_increment_id' => $orderIncrementId,
                    'entity_record_import' => $rowId,
                    'dataImport' => $dataJsonOrder
                ]
            );
            /**
             * echo $messageSuccess . "\n";
             */
        } else {
            if (isset($this->arrMessageErrors[$rowId])) {
                $messageSuccess = $this->arrMessageErrors[$rowId];

                /**
                 * Update status error
                 */
                $this->updateStatusRecord($rowId, StatusImport::IMPORT_FAIL, $messageSuccess);

                $this->loggerOrder->logError(
                    $this->arrMessageErrors[$rowId],
                    [
                        'entity_record_import' => $rowId,
                        'dataImport' => $dataJsonOrder
                    ]
                );

                /**
                 * echo "[Entity_id $rowId]" . $messageSuccess . "\n";
                 */
            }
        }
    }

    /**
     * Get List data Import
     *
     * @return array
     */
    public function getListDataImport()
    {
        $connection = $this->resourceConnection->getConnection('sales');
        $tableName = $connection->getTableName('riki_csv_order_import_history');
        $sql = $connection->select()
            ->from($tableName, ['entity_id', 'status', 'data_json_order', 'uploaded_by'])
            ->where("`status`=0")
            ->limit(500, 0);

        $listData = $connection->fetchAll($sql);
        if (is_array($listData)) {
            $this->dataOrderImport = $listData;
        }

        return $this->dataOrderImport;
    }

    /**
     * @param $rowId
     * @param $dataJsonOrder
     * @return mixed
     * @throws \Exception
     */
    public function processOrderImport($rowId, $dataJsonOrder)
    {
        try {
            $store = $this->getStore();
            $this->storeManager->setCurrentStore($store->getId());

            /**
             * Create quote
             * @var \Magento\Quote\Model\Quote $quote
             */
            $quote = $this->createNewQuoteImport($store, $dataJsonOrder);

            /**
             * set wherehouse
             */
            $quote = $this->setWhereHouse($quote, $dataJsonOrder);

            /**
             * Add customer
             */
            try {
                $customer = $this->getCustomerInformation($dataJsonOrder);
                if ($customer) {
                    $quote = $this->addCustomerToQuote($quote, $customer, $dataJsonOrder);
                } else {
                    $this->arrMessageErrors[$rowId] = __('Create customer error');
                    return false;
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->arrMessageErrors[$rowId] = $e->getMessage();
                return false;
            }

            $this->registry->unregister('rule_data');
            $this->registry->register('rule_data', $this->dataObject->setData(
                [
                    'store_id' => $store->getId(),
                    'website_id' => $store->getWebsiteId(),
                    'customer_group_id' => $customer->getGroupId()
                ]
            ));

            /**
             * Add item to quote
             */
            try {
                $result = $this->addItemToQuote($quote, $dataJsonOrder);
                $quote->save();
            } catch (\Exception $e) {
                $this->arrMessageErrors[$rowId] = $e->getMessage();
                return false;
            }

            if ($result instanceof \Magento\Quote\Model\Quote) {
                $quote = $this->processDeliveryTimeSlot($result, $dataJsonOrder);

                try {
                    $quote = $this->_setShippingMethodToQuote($quote);
                    $quote = $this->_setCouponToQuote($quote, $dataJsonOrder);
                    $quote = $this->_setPaymentToQuote($quote, $dataJsonOrder);

                    // calculate payment fee
                    $quote->setTotalsCollectedFlag(false)->collectTotals();

                    // todo: check why has to save the quote to save free gift
                    $quote->save();
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->arrMessageErrors[$rowId] = $e->getMessage();
                    if ($quote->getBaseGrandTotal() < 0.0001) {
                        $this->loggerOrder->info(
                            __('original_unique_id [%1] BaseGrandTotal is less than 0.0001, applied promotion ruleIds are %2',
                            $quote->getOriginalUniqueId(),
                            $quote->getAppliedRuleIds() ?: 'null'));
                    }
                    return false;
                }

                /**
                 * Place Order
                 */
                try {
                    $order = $this->placeOrder($quote);

                    try {
                        if ($order instanceof \Riki\Sales\Model\Order && !is_string($order)) {
                            $order->setData('delivery_time', $this->getValueOnCol($dataJsonOrder, 'delivery_time'));

                            $orderType = $this->getValueOnCol($dataJsonOrder, 'order_type');
                            $order = $this->setSubstitution($order, $orderType);
                            $order = $this->setFreeOfCharge($order, $orderType);
                            $order->setData('fraud_score', 50);
                            $order->setData('fraud_status', 'accept');
                            $orderComment = $this->getValueOnCol($dataJsonOrder, 'order_comment');
                            $order->addStatusHistoryComment($orderComment);

                            // Do not send email when import order
                            $order->setEmailSent(true);
                            $order->setSendEmail(true);
                            $order->setData('created_by', $this->createdBy);
                            $order->save();
                            return $order->getIncrementId();
                        } else {
                            $message = is_string($order) ? $order : __('Cannot place order.');
                            $this->arrMessageErrors[$rowId] = $message;
                            return false;
                        }
                    } catch (\Exception $e) {
                        $message = is_string($order) ? $order : $e->getMessage();
                        $this->arrMessageErrors[$rowId] = $message;
                        return false;
                    }
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->arrMessageErrors[$rowId] = $e->getMessage();
                    return false;
                }
            } else {
                $this->arrMessageErrors[$rowId] = $result;
                return false;
            }
        } catch (\Exception $e) {
            $this->arrMessageErrors[$rowId] = $e->getMessage();
            return false;
        }
    }

    /**
     * Create new quote import
     *
     * @param $store
     * @param $dataJsonOrder
     * @return \Magento\Quote\Model\Quote|null
     */
    public function createNewQuoteImport($store, $dataJsonOrder)
    {
        $quote = $this->createQuote($store);
        $quote->setData(self::CSV_ORDER_IMPORT_FLAG, true);
        $quote->setData('order_channel', $this->getValueOnCol($dataJsonOrder, 'order_channel'));
        $quote->setData('order_type', $this->getValueOnCol($dataJsonOrder, 'order_type'));
        $quote->setData('charge_type', $this->getValueOnCol($dataJsonOrder, 'order_type'));
        $quote->setData('campaign_id', $this->getValueOnCol($dataJsonOrder, 'campaign_id'));
        $quote->setData('siebel_enquiry_id', $this->getValueOnCol($dataJsonOrder, 'siebel_enquiry_id'));
        $quote->setData('free_samples_wbs', $this->getValueOnCol($dataJsonOrder, 'order_wbs'));
        $quote->setData('free_delivery_wbs', $this->getValueOnCol($dataJsonOrder, 'free_delivery_wbs'));
        $quote->setData('free_payment_wbs', $this->getValueOnCol($dataJsonOrder, 'free_payment_wbs'));
        $quote->setData('original_order_id', $this->getValueOnCol($dataJsonOrder, 'original_order_id'));
        $replacementReasonId = $this->getValueOnCol($dataJsonOrder, 'replacement_reason');
        if ($replacementReasonId != '' && isset($this->listReplacementReason[$replacementReasonId])) {
            $quote->setData('replacement_reason', $this->listReplacementReason[$replacementReasonId]);
        }
        $quote->setData('original_unique_id', $this->getValueOnCol($dataJsonOrder, 'original_unique_id'));

        /**
         * Assign warehouse if have
         */
        $assignedWarehouseId = $this->getValueOnCol($dataJsonOrder, 'assigned_warehouse_id');
        if ($assignedWarehouseId) {
            $quote->setData(self::IMPORT_ASSIGNED_WAREHOUSE_ID_KEY, $assignedWarehouseId);
            $quote->setData(Assignation::ASSIGNED_WAREHOUSE_ID, $assignedWarehouseId);
        }

        return $quote;
    }

    /**
     * Set substitution
     *
     * @param $order
     * @param $orderType
     * @return mixed
     */
    public function setSubstitution($order, $orderType)
    {
        if ($orderType == OrderType::ORDER_TYPE_REPLACEMENT) {
            $order->setData('substitution', 1);
        } else {
            $order->setData('substitution', 0);
        }
        return $order;
    }

    /**
     * Set free of charge
     *
     * @param $order
     * @param $orderType
     * @return mixed
     */
    public function setFreeOfCharge($order, $orderType)
    {
        if ($orderType == OrderType::ORDER_TYPE_FREE_SAMPLE) {
            $order->setData('free_of_charge', 1);
        } else {
            $order->setData('free_of_charge', 0);
        }
        return $order;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        return $this->storeManager->getDefaultStoreView();
    }

    /**
     * @param $store
     * @return \Magento\Quote\Model\Quote|null
     */
    public function createQuote($store)
    {
        $quote = null;
        /**
         * Create object of quote
         */
        $quote = $this->quote->create();
        $quote->setStore($store);
        $quote->setIsActive(0);
        return $quote;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $dataJsonOrder
     * @return \Magento\Quote\Model\Quote|string
     * @throws \Exception
     */
    public function addItemToQuote(\Magento\Quote\Model\Quote $quote, $dataJsonOrder = [])
    {
        try {
            $productItems = $dataJsonOrder['items'];
            $orderType = $this->getValueOnCol($dataJsonOrder, 'order_type');

            /**
             * Set shipping free
             */
            $freeDelivery = $this->getValueOnCol($dataJsonOrder, 'free_delivery');
            if ((int)$freeDelivery == 1) {
                $quote->setFreeShipping(true);
                $quote->setFreeShippingFlag(true);
            } else {
                $quote->setFreeShipping(false);
                $quote->setFreeShippingFlag(false);
            }

            /*
             * Add cart
             */
            $arrProduct = [];
            foreach ($productItems as $item) {
                if (!isset($arrProduct[$item['product_sku']])) {
                    $product = $this->productRepository->get($item['product_sku'], false, $this->getStore()->getId());
                    $arrProduct[$product->getSku()] = $product;
                } else {
                    $product = $arrProduct[$item['product_sku']];
                }

                $qty = (int)$item['qty'];

                if ($product) {
                    if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                        $requestInfo = $this->getRequestProductBundle($product, $qty);
                        $quote->addProduct($product, $requestInfo);
                    } else {
                        $quote->addProduct($product, $qty);
                    }

                    $this->checkCreditCartOnlyAndAllowSpot($product);

                    $this->arrGiftCode[$product->getSku()] = (isset($item['gift_code'])) ? $item['gift_code'] : '';
                }
            }

            /**
             * Validate back order after add item to quote
             */
            try {
                $this->helperBackOrder->getBackOrderStatusByQuote($quote);
            } catch (\Exception $e) {
                return $e->getMessage();
            }

            /**
             * Set attribute data
             */
            $quote = $this->setAttributeToQuoteItem($quote, $arrProduct, $orderType);

            $quote->setData('order_type', $this->getValueOnCol($dataJsonOrder, 'order_type'));

            return $quote;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Set attribute to quote item
     *
     * @param $quote
     * @param $arrProduct
     * @param $orderType
     * @return mixed
     * @throws LocalizedException
     */
    public function setAttributeToQuoteItem($quote, $arrProduct, $orderType)
    {
        foreach ($quote->getAllVisibleItems() as $item) {
            if (isset($arrProduct[$item->getSku()])) {
                $product = $arrProduct[$item->getSku()];

                if ($orderType == OrderType::ORDER_TYPE_REPLACEMENT) {
                    $item->setFocWbs($product->getData('booking_free_wbs'));
                    $item->setBookingAccount($product->getData('booking_machine_mt_account'));
                    $item->setBookingCenter($product->getData('booking_machine_mt_center'));
                    $item->setFreeOfCharge(1);
                    $item->setFocWbs($product->getData('booking_free_wbs'));
                } elseif ($orderType == OrderType::ORDER_TYPE_NORMAL) {
                    $item->setBookingWbs($product->getData('booking_item_wbs'));
                    $item->setBookingAccount($product->getData('booking_item_account'));
                    $item->setBookingCenter($product->getData('booking_profit_center'));
                    $item->setFreeOfCharge(0);
                    $item->setFocWbs($product->getData('booking_item_wbs'));
                } elseif ($orderType == OrderType::ORDER_TYPE_FREE_SAMPLE) {
                    $item->setFreeOfCharge(1);
                }

                /**
                 * Add gift wrapping
                 */
                $giftCode = isset($this->arrGiftCode[$item->getSku()]) ? $this->arrGiftCode[$item->getSku()] : '';
                if ($giftCode != '') {
                    $wrapping = $this->getGiftWrapping($giftCode);
                    if ($wrapping != false) {
                        $item->setGwId($wrapping['wrapping_id'])
                            ->setGiftCode($wrapping['gift_code'])
                            ->setSapCode($wrapping['sap_code'])
                            ->setGiftWrapping($wrapping['gift_name']);
                    } else {
                        throw new LocalizedException(__("The gift code $giftCode is not valid"));
                    }
                }
            }
        }
        return $quote;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @throws LocalizedException
     */
    public function checkCreditCartOnlyAndAllowSpot(\Magento\Catalog\Model\Product $product)
    {
        $productAllowCardOnly = $product->getData('credit_card_only');
        //check credit card not active or customer not select credit card
        if ($productAllowCardOnly && $productAllowCardOnly == 1) {
            $message = __('This product is only available for credit card payment');
            throw new \Magento\Framework\Exception\LocalizedException($message);
        }

        //check allow spot order
        if ($product->getAllowSpotOrder() != 1) {
            $message = 'I am sorry. Before you finish placing order, %1 has become out of stock. ';
            $message .= 'If you do not mind, please consider another product.';
            $messageError = __($message, $product->getName());
            throw new \Magento\Framework\Exception\LocalizedException($messageError);
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function _setShippingMethodToQuote(\Magento\Quote\Model\Quote $quote)
    {
        $quote->getShippingAddress()->setCollectShippingRates(true);

        $quote->getShippingAddress()->setShippingMethod('riki_shipping_riki_shipping');
        $quote->getShippingAddress()->setSaveInAddressBook(false);

        return $quote;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $arrData
     *
     * @return \Magento\Quote\Model\Quote
     * @throws LocalizedException
     */
    protected function _setPaymentToQuote(\Magento\Quote\Model\Quote $quote, $arrData)
    {
        $paymentMethod = $this->getValueOnCol($arrData, 'payment_method');
        $paymentData = [
            PaymentInterface::KEY_METHOD => $paymentMethod,
            'checks' => [
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_ZERO_TOTAL,
            ]
        ];

        $quote->getPayment()->setQuote($quote);

        $quote->getPayment()->importData($paymentData);

        $quote->setInventoryProcessed(false);

        if ($paymentMethod == 'cashondelivery') {
            $codFree = $this->getValueOnCol($arrData, 'cod_free_free');
            if ((int)$codFree == 1) {
                $quote->setCsvOrderCodFree(true);
            }
        }

        return $quote;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $data
     *
     * @return \Magento\Quote\Model\Quote
     * @throws LocalizedException
     */
    protected function _setCouponToQuote(\Magento\Quote\Model\Quote $quote, $data)
    {
        $couponCode = $this->getValueOnCol($data, 'coupon_code');
        if ($couponCode != '') {
            $quote->setCouponCode($couponCode);
            $this->checkCouponCodeValid($quote, $couponCode);
        }
        return $quote;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $customer
     * @param $dataImport
     * @return \Magento\Quote\Model\Quote
     */
    public function addCustomerToQuote(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        $dataImport
    ) {
        /**
         * Set billing address
         */
        $billing = $dataImport['billingAddress'];
        $newBillingAddress = $this->addressFactory->create();
        $newBillingAddress->setLastname($this->getValueOnCol($billing, 'bill_lastname'));
        $newBillingAddress->setFirstname($this->getValueOnCol($billing, 'bill_firstname'));
        $newBillingAddress->setCustomAttribute('firstnamekana', $this->getValueOnCol($billing, 'bill_lastname_kana'));
        $newBillingAddress->setCustomAttribute('lastnamekana', $this->getValueOnCol($billing, 'bill_firstname_kana'));
        $newBillingAddress->setCustomAttribute('riki_nickname', '本人');
        $newBillingAddress->setCountryId('JP');
        $newBillingAddress->setStreet([$this->getValueOnCol($billing, 'bill_address')]);
        $newBillingAddress->setTelephone($this->getValueOnCol($billing, 'bill_phonenumber'));
        $newBillingAddress->setPostcode($this->getValueOnCol($billing, 'bill_postcode'));
        $newBillingAddress->setCity('None');
        $newBillingAddress->setRegionId($this->getValueOnCol($billing, 'bill_region'));
        $newBillingAddress->setRegion($this->getValueOnCol($billing, 'bill_region'));

        /**
         * Set shipping address
         */
        $shipping = $dataImport['shippingAddress'];
        $newShippingAddress = $this->addressFactory->create();
        $newShippingAddress->setLastname($this->getValueOnCol($shipping, 'ship_lastname'));
        $newShippingAddress->setFirstname($this->getValueOnCol($shipping, 'ship_firstname'));
        $newShippingAddress->setCustomAttribute('firstnamekana', $this->getValueOnCol($shipping, 'ship_lastname_kana'));
        $newShippingAddress->setCustomAttribute('lastnamekana', $this->getValueOnCol($shipping, 'ship_firstname_kana'));
        $newShippingAddress->setCustomAttribute('riki_nickname', '本人');
        $newShippingAddress->setCountryId('JP');
        $newShippingAddress->setStreet([$this->getValueOnCol($shipping, 'ship_address')]);
        $newShippingAddress->setTelephone($this->getValueOnCol($shipping, 'ship_phonenumber'));
        $newShippingAddress->setPostcode($this->getValueOnCol($shipping, 'ship_zipcode'));
        $newShippingAddress->setCity('None');
        $newShippingAddress->setRegionId($this->getValueOnCol($shipping, 'ship_region'));
        $newShippingAddress->setRegion($this->getValueOnCol($shipping, 'ship_region'));

        /**
         * if region and street are same between "billing" and "shipping" addresses, riki_type_address= home for both
         * else , billing is "home" shipping is "shipping"
         */
        if (($newBillingAddress->getRegion() == $newShippingAddress->getRegion()) &&
            (trim($newBillingAddress->getStreetLine(1)) == trim($newShippingAddress->getStreetLine(1)))
        ) {
            $newBillingAddress->setCustomAttribute('riki_type_address', 'home');
            $newShippingAddress->setCustomAttribute('riki_type_address', 'home');
        } else {
            $newBillingAddress->setCustomAttribute('riki_type_address', 'home');
            $newShippingAddress->setCustomAttribute('riki_type_address', 'shipping');
        }

        /**
         * assign customer with new shipping,billing address
         */
        $quote->assignCustomerWithAddressChange($customer, $newBillingAddress, $newShippingAddress);

        return $quote;
    }

    /**
     * @param $email
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomerByEmail($email)
    {
        try {
            $customer = $this->customerRepository->get($email);
            $this->arrCustomer[$customer->getId()] = $customer;
            return $customer;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Framework\Model\AbstractExtensibleModel|\Magento\Sales\Api\Data\OrderInterface|null|object|string
     * @throws \Exception
     */
    public function placeOrder(\Magento\Quote\Model\Quote $quote)
    {
        try {
            $this->eventManager->dispatch('checkout_submit_before', ['quote' => $quote]);
            $order = $this->quoteManagement->submit($quote);
            if (null == $order) {
                throw new LocalizedException(__('Cannot place order.'));
            }
            $this->eventManager->dispatch('checkout_submit_all_after', ['order' => $order, 'quote' => $quote]);
            return $order;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param $rowData
     * @param $field
     * @return string
     */
    public function getValueOnCol($rowData, $field)
    {
        if (isset($rowData[$field])) {
            return $rowData[$field];
        }
        return '';
    }

    /**
     * Update data
     */
    public function updateRowAfterImport()
    {
        $connection = $this->resourceConnection->getConnection('sales');
        $tableName = $connection->getTableName('riki_csv_order_import_history');

        //update status
        if (is_array($this->arrMessageSuccess) && !empty($this->arrMessageSuccess)) {
            $connection->update(
                $tableName,
                ['status' => 1],
                ["entity_id IN (?)" => $this->arrMessageSuccess]
            );
        }

        //update error message
        if (is_array($this->arrMessageErrors) && !empty($this->arrMessageErrors)) {
            foreach ($this->arrMessageErrors as $entityId => $message) {
                $connection->update(
                    $tableName,
                    [
                        'status' => 2,
                        'error_description' => $message
                    ],
                    ["entity_id =(?)" => $entityId]
                );
            }
        }
    }

    /**
     * Get list riki time slot
     * @return array
     */
    public function getListDeliveryTimeSlot()
    {
        $connection = $this->resourceConnection->getConnection();
        $sql = $connection->select()->from([$connection->getTableName('riki_timeslots')]);

        $timeSlot = $connection->fetchAll($sql);
        if (is_array($timeSlot) && !empty($timeSlot)) {
            foreach ($timeSlot as $item) {
                $this->listDeliveryTIme[$item['appointed_time_slot']] = $item;
            }
        }
        return $this->listDeliveryTIme;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $dataJsonOrder
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processDeliveryTimeSlot(\Magento\Quote\Model\Quote $quote, $dataJsonOrder)
    {
        $deliveryTime = null;
        $deliveryTimeFrom = null;
        $deliveryTimeTo = null;
        $deliveryTimeSlotId = null;

        /**
         * Process delivery time
         */
        $deliveryDate = $this->getValueOnCol($dataJsonOrder, 'delivery_date');
        $deliveryTime = $this->getValueOnCol($dataJsonOrder, 'delivery_time');

        if (isset($this->listDeliveryTIme[$deliveryTime])) {
            $timeSlotMachine = $this->listDeliveryTIme[$deliveryTime];
            if ($timeSlotMachine != null) {
                $deliveryTime = isset($timeSlotMachine['slot_name']) ? $timeSlotMachine['slot_name'] : null;
                $deliveryTimeFrom = isset($timeSlotMachine['from']) ? $timeSlotMachine['from'] : null;
                $deliveryTimeTo = isset($timeSlotMachine['to']) ? $timeSlotMachine['to'] : null;
                $deliveryTimeSlotId = isset($timeSlotMachine['id']) ? $timeSlotMachine['id'] : null;
            }
        }

        //add delivery date to item
        foreach ($quote->getAllVisibleItems() as $item) {
            if ($deliveryDate != null) {
                $item->setDeliveryDate($deliveryDate);
            }

            if ($deliveryTime != null) {
                $item->setDeliveryTime($deliveryTime);
            }

            if ($deliveryTimeFrom != null) {
                $item->setDeliveryTimeslotFrom($deliveryTimeFrom);
            }

            if ($deliveryTimeTo != null) {
                $item->setDeliveryTimeslotTo($deliveryTimeTo);
            }

            if ($deliveryTimeSlotId != null) {
                $item->setDeliveryTimeslotId($deliveryTimeSlotId);
            }
        }

        return $quote;
    }

    /**
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getListReplacementReason()
    {
        $optionsConfig = $this->scopeConfig->getValue('riki_order/replacement_order/reason');

        if ($optionsConfig) {
            $options = $this->serializer->unserialize($optionsConfig);

            if (is_array($options)) {
                foreach ($options as $option) {
                    $this->listReplacementReason[$option['code']] = $option['code'] . "-" . $option['title'];
                }
            }
        }

        return $this->listReplacementReason;
    }

    /**
     * @param $dataJsonOrder
     * @return array
     */
    public function buildParamsCreateCustomer($dataJsonOrder)
    {
        $customer = $dataJsonOrder['customer'];
        $billingAddress = $dataJsonOrder['billingAddress'];
        $originalRequestData = [
            "customer" => [
                "lastname" => $this->getValueOnCol($billingAddress, 'bill_lastname'),
                "lastnamekana" => $this->getValueOnCol($billingAddress, 'bill_lastname_kana'),
                "firstname" => $this->getValueOnCol($billingAddress, 'bill_firstname'),
                "firstnamekana" => $this->getValueOnCol($billingAddress, 'bill_firstname_kana'),
                "email" => $this->getValueOnCol($customer, 'email'),
                "email_1_type" => 0,
                "dob" => $this->getValueOnCol($customer, 'birthdate'),
                "gender" => "3",
                "KEY_JOB_TITLE" => 99,
                "KEY_MARITAL_STAT_CODE" => 3,
                "KEY_EPS_FLG" => 1,
                "KEY_CAUTION" => "Created by Update Multiple Order by CSV function",
                "shosha_business_code" => $this->getValueOnCol($customer, 'business_code'),
                "offline_customer" => 1,
                "membership" => [\Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership::CODE_1],
                "multiple_website" => ["1"],

                "taxvat" => "",
                "USE_POINT_AMOUNT" => "",
                "customer_company_name" => "",
                "KEY_POST_NAME" => "",
                "key_work_ph_num" => "",
                "KEY_ASST_PH_NUM" => "",
                "PET_NAME" => "",
                "PET_BREED" => "",
                "blacklisted_reason" => "",
                "email_2" => "",
                "PETSHOP_CODE" => "",
                "EMPLOYEES" => "",
                "COM_ADDRESS2" => "",
                "COM_ADDRESS3" => "",
                "COM_ADDRESS4" => "",
                "NJL_CHARGE_COMPANY" => "",
                "NJL_CHARGE" => "",
                "CHARGE_PERSON" => "",
                "AMB_REFERENCE_CUS_CODE" => "",
                "INTRODUCER_E_MAIL" => "",
                "DAY_CONTACT_TEL" => "",
                "amb_com_name" => "",
                "amb_com_division_name" => "",
                "amb_ph_num" => "",
                "amb_sale" => "0",
                "USE_POINT_TYPE" => "0",
                "PET_SEX" => "0",
                "group_id" => "1",
                "approval_needed" => "0",
                "isblacklisted" => "0",
                "is_whitelisted" => "0",
                "legacy_promo_ndgbble" => "1",
                "legacy_promo_ndggrass" => "1",
                "legacy_promo_specialt" => "1",
                "amb_type" => "0",
                "LENDING_STATUS_NBA" => "0",
                "LENDING_STATUS_NDG" => "0",
                "LENDING_STATUS_SPT" => "0",
                "LENDING_STATUS_ICS" => "0",
                "LENDING_STATUS_NSP" => "0",
                "MD0000" => "false",
                "PM0000" => "false",
                "SPM0000" => "false",
                "NM0000" => "false",
                "ST0000" => "false",
                "OT0000" => "false",
                "WELLNESSCLUB_AMB" => "false",
                "ALLEGRIA_STATUS" => "false",
                "CHOCOLLATORY_FLG" => "false",
                "KITKAT_CLUB_FLG" => "false",
                "MILANO_STATUS" => "false",
                "SATELLITE_FLG" => "false",
                "disable_auto_group_change" => "false",
                "AMB_FRIENDS" => "false",
                "SATELLITE_AMB" => "false",
                "EDIT_MESSAGE" => "",
                "AMB_STOP_REASON" => "",
                "COM_POSTAL_CODE" => "",
                "GARDIAN_APPROVAL" => "false",
                "PETSHOP_APPLICATION_DATE" => "",
                "PETSHOP_AUTHORIZED_DATE" => "",
                "PET_BIRTH_DT" => "",
                "AMB_APPLICATION_DATE" => "",
                "AMB_STOP_DATE" => ""
            ],
            "address" => [
                "new_0" => [
                    "lastname" => $this->getValueOnCol($billingAddress, 'bill_lastname'),
                    "lastnamekana" => $this->getValueOnCol($billingAddress, 'bill_lastname_kana'),
                    "firstname" => $this->getValueOnCol($billingAddress, 'bill_firstname'),
                    "firstnamekana" => $this->getValueOnCol($billingAddress, 'bill_firstname_kana'),
                    "riki_nickname" => "本人",
                    "telephone" => $this->getValueOnCol($billingAddress, 'bill_phonenumber'),
                    "default_billing" => false,
                    "default_shipping" => false,
                    "street" => [$this->getValueOnCol($billingAddress, 'bill_address')],
                    "postcode" => $this->getValueOnCol($billingAddress, 'bill_postcode'),
                    "region" => $this->getValueOnCol($billingAddress, 'bill_region'),
                    "country_id" => "JP",
                    "region_id" => $this->getValueOnCol($billingAddress, 'bill_region'),
                    "riki_type_address" => "home"
                ]
            ]
        ];

        return $originalRequestData;
    }

    /**
     * @param $dataJsonOrder
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface|string
     */
    public function getCustomerInformation($dataJsonOrder)
    {
        /**
         * Check customer exist
         */

        try {
            $customerEmail = $this->getValueOnCol($dataJsonOrder['customer'], 'email');
            $resultCustomer = $this->getCustomerByEmail($customerEmail);

            /**
             * Customer exist
             */
            if ($resultCustomer && !is_string($resultCustomer)) {
                return $resultCustomer;
            } else {
                $originalRequestData = $this->buildParamsCreateCustomer($dataJsonOrder);
                $resultApi = $this->createCustomer->createNewCustomer($originalRequestData);
                if ($resultApi && !is_string($resultApi) && $resultApi->getEmail() == $customerEmail) {
                    /**
                     * Customer does not exist
                     */
                    $resultCustomer = $this->getCustomerByEmail($customerEmail);
                    if ($resultCustomer && !is_string($resultCustomer)) {
                        return $resultCustomer;
                    }
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return array
     */
    public function getListGiftWrapping()
    {
        $connection = $this->resourceConnection->getConnection();
        $sql = $connection->select()->from([$connection->getTableName('magento_giftwrapping')]);

        $arrGiftWrapping = $connection->fetchAll($sql);
        if (is_array($arrGiftWrapping) && !empty($arrGiftWrapping)) {
            foreach ($arrGiftWrapping as $item) {
                $this->listGiftWrapping[$item['gift_code']] = $item;
            }
        }
        return $this->listGiftWrapping;
    }

    /**
     * @param $giftCode
     * @return bool|mixed
     */
    public function getGiftWrapping($giftCode)
    {
        if (isset($this->listGiftWrapping[$giftCode])) {
            return $this->listGiftWrapping[$giftCode];
        }
        return false;
    }

    /**
     * @param $product
     * @param $qty
     * @return \Magento\Framework\DataObject
     */
    public function getRequestProductBundle($product, $qty)
    {
        $typeInstance = $product->getTypeInstance(true);
        $typeInstance->setStoreFilter($product->getStoreId(), $product);
        $optionCollection = $typeInstance->getOptionsCollection($product);
        $selectionCollection = $typeInstance->getSelectionsCollection($typeInstance->getOptionsIds($product), $product);
        $bundleOptions = [];
        $bundleOptionsQty = [];
        /** @var $option \Magento\Bundle\Model\Option */
        foreach ($optionCollection as $option) {
            /** @var $selection \Magento\Bundle\Model\Selection */
            foreach ($selectionCollection as $selection) {
                if ($option->getId() == $selection->getOptionId()) {
                    $bundleOptions[$option->getId()] = $selection->getSelectionId();
                    $bundleOptionsQty[$option->getId()] = $selection->getSelectionQty() * 1;
                    break;
                }
            }
        }

        $requestInfo = $this->dataObject->setData([
            'qty' => $qty,
            'options' => [],
            'bundle_option' => $bundleOptions,
            'bundle_option_qty' => $bundleOptionsQty
        ]);
        return $requestInfo;
    }

    /**
     * @param $quote
     * @param $couponCode
     * @return bool
     * @throws LocalizedException
     */
    public function checkCouponCodeValid($quote, $couponCode)
    {
        if ($couponCode != '') {
            $isApplyDiscount = false;
            foreach ($quote->getAllItems() as $item) {
                if (!$item->getNoDiscount()) {
                    $isApplyDiscount = true;
                    break;
                }
            }
            if (!$isApplyDiscount) {
                throw new LocalizedException(__('The coupon code "%1" is not valid.', $couponCode));
            } else {
                if ($quote->getCouponCode() !== $couponCode) {
                    throw new LocalizedException(__('The coupon code "%1" is not valid.', $couponCode));
                }
            }
        }
        return true;
    }

    /**
     * Delete lock folder
     */
    public function deleteLockFolder()
    {
        $lockFolder = $this->directoryWrite->getRelativePath('lock/' . $this->getLockFileName());

        $this->directoryWrite->delete($lockFolder);
    }

    /**
     * Each type of cutoff  email has a particular name.
     *
     * @return string
     */
    protected function getLockFileName()
    {
        $part = explode('\\', get_class($this));
        return strtolower(end($part)) . '.lock';
    }

    /**
     * Check cron run
     *
     * @throws LocalizedException
     */
    public function checkCronRun()
    {
        $lockFolder = $this->directoryWrite->getRelativePath('lock/' . $this->getLockFileName());
        if ($this->directoryWrite->isExist($lockFolder)) {
            $message = __('Please wait, system have a same process is running and haven’t finish yet.');
            throw new \Magento\Framework\Exception\LocalizedException($message);
        }

        $this->directoryWrite->create($lockFolder);
    }

    /**
     * Update status and message
     *
     * @param $entityId
     * @param $status
     * @param $message
     */
    public function updateStatusRecord(
        $entityId,
        $status,
        $message = null
    ) {
        $connection = $this->resourceConnection->getConnection('sales');
        $tableName = $connection->getTableName('riki_csv_order_import_history');

        if ($entityId > 0) {
            $connection->update(
                $tableName,
                [
                    'status' => $status,
                    'error_description' => $message
                ],
                [
                    "entity_id =(?)" => $entityId
                ]
            );
        }
    }

    /**
     * Set wherehouse
     *
     * @param $quote
     * @param $dataJsonOrder
     * @return mixed
     */
    public function setWhereHouse($quote, $dataJsonOrder)
    {
        $assignedWarehouseId = $this->getValueOnCol($dataJsonOrder, 'assigned_warehouse_id');
        if ($assignedWarehouseId != '') {
            $quote->setData(self::IMPORT_ASSIGNED_WAREHOUSE_ID_KEY, $assignedWarehouseId);
            $quote->setData(Assignation::ASSIGNED_WAREHOUSE_ID, $assignedWarehouseId);
        }
        return $quote;
    }
}
