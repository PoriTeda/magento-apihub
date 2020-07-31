<?php

namespace Riki\Subscription\Helper\Order;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderManagementInterface;
use \Riki\AdvancedInventory\Helper\Inventory as InventoryHelper;
use Riki\Subscription\Helper\Order\Data as SubscriptionOrerHelper;

class Simulator extends \Riki\Subscription\Helper\Order\Data
{
    /**
     * @var \Riki\Subscription\Model\Emulator\TableManager $tableManager
     */
    protected $tableManager;

    /**
     * @var \Riki\Subscription\Model\Emulator\AutomaticallyShipment\CreateShipment
     */
    protected $createEmulatorShipment;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $magentoCustomerRepository;

    public function __construct(
        InventoryHelper $inventoryHelper,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $product,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Data\Form\FormKey $formkey,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\AddressFactory $customerAddress,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory,
        \Riki\Subscription\Logger\LoggerOrder $loggerOrder,
        \Magento\Framework\Registry $registry,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Riki\AutomaticallyShipment\Model\CreateShipment $createShipment,
        \Riki\AdvancedInventory\Model\Assignation $modelAssignation,
        \Wyomind\Core\Helper\Data $coreHelperData,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        OrderManagementInterface $orderManagement,
        \Riki\Subscription\Model\Frequency\FrequencyFactory $frequencyFactory,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Riki\Subscription\Api\CreateOrderRepositoryInterface $createOrderRepository,
        \Riki\Subscription\Helper\Hanpukai\Data $hanpukaiHelper,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Amasty\Promo\Model\Registry $promoRegistry,
        \Riki\Sales\Helper\Address $addressHelper,
        \Riki\BackOrder\Helper\Data $backOrderHelper,
        \Riki\Checkout\Model\Order\Address\ItemFactory $orderItemAddress,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Sales\Model\Order\AddressRepository $orderAddressRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riki\Subscription\Helper\Order\Email $emailOrder,
        \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository,
        \Riki\DeliveryType\Model\DeliveryDate $deliveryDate,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Riki\SubscriptionMachine\Helper\Order\Generate $freeMachine,
        \Riki\Subscription\Model\Emulator\CartFactory $emulateCartModelFactory,
        \Riki\Subscription\Model\Emulator\CreateOrderRepository $emulatorCreateOrderRepository,
        \Riki\Subscription\Model\Emulator\TableManager $tableManager,
        \Riki\Subscription\Model\Emulator\CartManagement $emulatorCartManagement,
        \Riki\Subscription\Model\Emulator\OrderManagement $emulatorOrderManagement,
        \Riki\Subscription\Model\Emulator\AdvancedInventory\Assignation $emulatorAssignation,
        \Riki\Subscription\Model\Emulator\AutomaticallyShipment\CreateShipment $emulatorCreateShipment,
        \Riki\Subscription\Model\Emulator\Order\Address\ItemFactory $emulatorOrderAddressItem,
        \Riki\Subscription\Model\Emulator\Point\RewardQuoteFactory $emulatorRewardQuoteFactory,
        \Riki\EmailMarketing\Helper\Order $orderHelper,
        \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository,
        \Riki\Subscription\Logger\LoggerSimulator $loggerSimulator,
        \Riki\Subscription\Model\Emulator\Order\AddressRepository $simulatorAddressRepository,
        \Riki\Subscription\Model\Email\ProfilePaymentMethodError $profilePaymentMethodErrorEmail,
        \Magento\GiftWrapping\Helper\Data $giftWrappingHelper,
        AddressRepositoryInterface $customerAddressRepository,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory,
        CustomerRepositoryInterface $magentoCustomerRepository,
        \Riki\Coupons\Helper\Coupon $couponHelper,
        \Riki\Loyalty\Helper\Data $loyaltyHelper,
        \Riki\ShipLeadTime\Api\StockStateInterface $leadTimeStockStatus,
        \Riki\AdvancedInventory\Helper\Assignation $assignationHelper,
        \Riki\Subscription\Helper\StockPoint\Data $stockPointHelper,
        \Riki\AdvancedInventory\Observer\OosCapture $oosCapture,
        \Riki\SubscriptionCourse\Helper\Data $helperCourse,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Riki\DeliveryType\Helper\Data $deliveryTypeHelper,
        \Magento\Quote\Model\Quote\Config $quoteConfig,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\AdvancedInventory\Model\Stock $stockModel,
        \Riki\CreateProductAttributes\Model\Product\CaseDisplay $caseDisplay,
        \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator $validatorMonthlyFee,
        \Wyomind\AdvancedInventory\Api\StockRepositeryInterface $wyomindStockRepository,
        \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $profileIndexer = null
    ) {
        parent::__construct(
            $inventoryHelper,
            $context,
            $storeManager,
            $product,
            $productFactory,
            $formkey,
            $quote,
            $quoteManagement,
            $customerFactory,
            $customerRepository,
            $customerAddress,
            $orderService,
            $profileFactory,
            $productCartFactory,
            $loggerOrder,
            $registry,
            $quoteRepository,
            $createShipment,
            $modelAssignation,
            $coreHelperData,
            $objectManager,
            $messageManager,
            $orderManagement,
            $frequencyFactory,
            $sessionQuote,
            $createOrderRepository,
            $hanpukaiHelper,
            $promoItemHelper,
            $promoRegistry,
            $addressHelper,
            $orderItemAddress,
            $stockRegistry,
            $state,
            $timezone,
            $orderAddressRepository,
            $checkoutSession,
            $emailOrder,
            $wrappingRepository,
            $deliveryDate,
            $rewardManagement,
            $rewardQuoteFactory,
            $profileRepository,
            $freeMachine,
            $orderHelper,
            $rikiCustomerRepository,
            $backOrderHelper,
            $profilePaymentMethodErrorEmail,
            $giftWrappingHelper,
            $customerAddressRepository,
            $quoteAddressFactory,
            $couponHelper,
            $loyaltyHelper,
            $leadTimeStockStatus,
            $assignationHelper,
            $stockPointHelper,
            $oosCapture,
            $helperCourse,
            $productCollectionFactory,
            $deliveryTypeHelper,
            $quoteConfig,
            $resourceConnection,
            $stockModel,
            $caseDisplay,
            $validatorMonthlyFee,
            $wyomindStockRepository,
            $profileIndexer
        );
        $this->quote = $emulateCartModelFactory;
        $this->_createOrderRepository = $emulatorCreateOrderRepository;
        $this->tableManager = $tableManager;
        $this->quoteManagement = $emulatorCartManagement;
        $this->orderManagement = $emulatorOrderManagement;
        $this->_modelAssignation = $emulatorAssignation;
        $this->createEmulatorShipment = $emulatorCreateShipment;
        $this->_addressItemFactory = $emulatorOrderAddressItem;
        $this->rewardQuoteFactory = $emulatorRewardQuoteFactory;
        $this->_loggerOrder = $loggerSimulator;
        $this->orderAddress = $simulatorAddressRepository;
        $this->magentoCustomerRepository = $magentoCustomerRepository;
    }

    /**
     *  Try to simulate order for profile
     *
     * @param $profileId
     * @param null $arrPost
     * @return mixed
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createMageOrder($profileId, $arrPost = null, $isSimulator = null, $isNewPaygent = null, $isList = false, $iDeliveryNumber = null, $iSimulateShipment = false, $isExportBi = false)
    {
        if (!$profileId) {
            return false;
        }

        $this->_registry->unregister(\Riki\Subscription\Helper\Order\Data::PROFILE_GENERATE_STATE_REGISTRY_NAME);
        $this->_registry->register(\Riki\Subscription\Helper\Order\Data::PROFILE_GENERATE_STATE_REGISTRY_NAME, true);

        $orderData = $this->getProfile($profileId, true, $iDeliveryNumber, null, $isExportBi);

        if (!$orderData) {
            throw new \Magento\Framework\Exception\InputException(__("Profile does not exists"));
        }

        // If customer does not exist, NoSuchEntityException exception is thrown
        $this->magentoCustomerRepository->getById($orderData['customer_id']);

        $this->tableManager->createTemporaryTables();

        if ($iSimulateShipment) {
            $order = parent::createMageSimulateQuote($orderData);
            return [$order, $this->assignWarehouseAndShipment($order)];
        }

        return parent::createMageSimulateQuote($orderData);
    }

    public function createMageOrderForAPI($profileId, $cartData = null, $customer = null, $changedData = [])
    {
        try {
            $orderData = $this->getProfile($profileId, true, null, $cartData, false, $changedData);
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(__("Could not load profile"));
        }
        if (!$orderData) {
            throw new \Magento\Framework\Exception\InputException(__("Profile does not exists"));
        }

        /* if customer not found throw exception */
        if (!$customer->getId()) {
            throw new \Magento\Framework\Exception\InputException(__("Customer does not exists"));
        }

        $this->tableManager->createTemporaryTables();

        $this->_registry->unregister(\Riki\Subscription\Helper\Order\Data::PROFILE_GENERATE_STATE_REGISTRY_NAME);
        $this->_registry->register(\Riki\Subscription\Helper\Order\Data::PROFILE_GENERATE_STATE_REGISTRY_NAME, true);

        return parent::createMageSimulateQuote($orderData);
    }

    /**
     * AssignWarehouseAndShipment
     *
     * @param $order
     * @return bool|null|void
     */
    public function assignWarehouseAndShipment($order)
    {
        if ($this->_coreHelperData->getStoreConfig("advancedinventory/settings/enabled")
            && $this->_coreHelperData->getDefaultConfig("advancedinventory/settings/autoassign_order")
            && $order
        ) {
            $this->_modelAssignation->order = $order;

            $assignation = $this->_modelAssignation->generateAssignationByOrder($order, false);
            //set assignation for order
            $orderAssignationData = \Zend_Json::encode($assignation['inventory']);
            $order->setAssignation($orderAssignationData);
            try {
                $order->save();
            } catch (\Exception $e) {
                $this->_messageManager->addError('Cannot update Order while creating shipment');
                $this->_logger->critical($e->getMessage());
            }
            /* RIKI AUTOMATICALLY CREATE SHIPMENT */
            try {
                $shipmentData = $this->createEmulatorShipment->createShipment($order);
                return $shipmentData;
            } catch (\Exception $e) {
                $this->_messageManager->addError('Cannot create shipment for order #' . $order->getIncrementId());
                $this->_loggerOrder->critical($e);
            }
            /* END RIKI AUTOMATICALLY CREATE SHIPMENT */
        }

        return null;
    }

    /**
     * Simulate for hanpukai email
     *
     * @param Order $order
     * @param string $profileId
     * @param array $arrData
     * @param null $isSimulator
     *
     * @return array
     * @throws \Exception
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function simulateMageOrder($order, $profileId, $arrData, $isSimulator = null)
    {
        try {
            $orderData = $this->simulateOrderData($order, $profileId, $arrData);
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(__("Could not load profile"));
        }

        if (!$orderData) {
            throw new \Magento\Framework\Exception\InputException(__("Profile does not exists"));
        }
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->customerFactory->create();
        try {
            $customer->load($orderData['customer_id']);// load customet by email address
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            throw $exception;
        }

        /* if customer not found throw exception */
        if (!$customer instanceof \Magento\Customer\Model\Customer
            && !$customer->getId()
        ) {
            throw new \Magento\Framework\Exception\InputException(__("Customer does not exists"));
        }

        $this->tableManager->createTemporaryTables();

        return parent::simulateMageOrder($order, $profileId, $arrData, true);
    }

    /**
     * Simulate Quote
     */
    public function createMageQuote($arrProductInfo, $isSimulator = true)
    {
        $this->tableManager->createTemporaryTables();
        return parent::createMageQuote($arrProductInfo, true);
    }

    /**
     * will create a simulator order if already has profile data
     * Usually use for subscription profile view|edit|confirm and get order summary
     *
     * @param $profileId
     * @param $profileData
     * @param $isSimulator
     *
     * @return object
     */
    public function createSimulatorOrderHasData(\Magento\Framework\DataObject $data, $isList = false, $isAdmin = false)
    {
        $profileData = [];
        $error = 0;
        $errorMessage = [];
        $warning = 0;
        /*Validate $data*/
        /*profile id */
        if ($data->getData('profile_id')) {
            $profileData['profile_id'] = $data->getData('profile_id');
        } else {
            $error++;
            $errorMessage[] = __('Profile ID is null');
        }

        /*Course ID*/
        if ($data->getData('course_id')) {
            $profileData['course_id'] = $data->getData('course_id');
        } else {
            $error++;
            $errorMessage[] = __('Course ID is null');
        }
        /*Frequency Unit*/
        if ($data->getData('frequency_unit')) {
            $profileData['frequency_unit'] = $data->getData('frequency_unit');
        } else {
            $error++;
            $errorMessage[] = __('Frequency unit is null');
        }
        /*Course ID*/
        if ($data->getData('frequency_interval')) {
            $profileData['frequency_interval'] = $data->getData('frequency_interval');
        } else {
            $error++;
            $errorMessage[] = __('Frequency interval is null');
        }
        /*Customer*/
        if ($data->getData('customer_id')) {
            $profileData['customer_id'] = $data->getData('customer_id');
        } else {
            $error++;
            $errorMessage[] = __('Customer ID is null');
        }
        /*Store ID*/
        if ($data->getData('store_id')) {
            $profileData['store_id'] = $data->getData('store_id');
        } else {
            $error++;
            $errorMessage[] = __('Store ID is null');
        }
        /*Payment method*/
        $profileData['payment_method'] = $data->getData('payment_method');
        /*Shipping method*/
        if ($data->getData('shipping_condition')) {
            $profileData['shipping_method'] = $data->getData('shipping_condition');
        } else {
            $error++;
            $errorMessage[] = __('Shipping method is null');
        }
        /*Trading Id*/
        if ($data->getData('trading_id')) {
            $profileData['trading_id'] = $data->getData('trading_id');
        } else {
            $profileData['trading_id'] = null;
        }

        /*Order times -- used to apply promotion for n delivery*/
        if ($data->getData('order_times')) {
            $profileData['order_times'] = $data->getData('order_times');
        } else {
            $error++;
            $errorMessage[] = __('Order times is null');
        }
        /*Create order flag -- used to apply promotion for n delivery*/
        if ($data->getData('create_order_flag')) {
            $profileData['create_order_flag'] = $data->getData('create_order_flag');
        } else {
            $profileData['create_order_flag'] = 0;
        }
        /*earn_point_on_order */
        if ($data->getData('earn_point_on_order')) {
            $profileData['earn_point_on_order'] = $data->getData('earn_point_on_order');
        } else {
            $profileData['earn_point_on_order'] = null;
        }
        /*Coupon code*/
        if ($data->getData('coupon_code')) {
            $profileData['coupon_code'] = $data->getData('coupon_code');
        } else {
            $profileData['coupon_code'] = null;
        }

        $profileData[SubscriptionOrerHelper::PROFILE_STOCK_POINT_BUCKET_ID] =
            $data->getData(SubscriptionOrerHelper::PROFILE_STOCK_POINT_BUCKET_ID);

        /*flag to check this profile is used stock point or not*/
        $isStockPointProfile = !$data->getData('is_delete_stock_point') &&
            (
                $data->getData(SubscriptionOrerHelper::PROFILE_STOCK_POINT_BUCKET_ID) ||
                $data->getData('stock_point_data') || // for case has redirected from Map page and has not click update all change
                $data->getData('stock_point_data_post') // for case confirm all change
            );

        $profileData[SubscriptionOrerHelper::IS_STOCK_POINT_PROFILE] = $isStockPointProfile;

        /*Order items*/
        if ($data->getData('product_cart') && sizeof($data->getData('product_cart')) > 0) {
            $productCartData = [];
            if (isset($profileData['course_id'])) {
                foreach ($data->getData('product_cart') as $productId => $product) {
                    try {
                        $timeSlot = $this->deliveryDate->getTimeSlotInfo($product->getData('delivery_time_slot'));
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        $timeSlot = false;
                    }
                    $productData = [
                        'product_id' => $product->getData('product_id'),
                        'parent_item_id' => $product->getData('parent_item_id'),
                        'qty' => $product->getData('qty'),
                        'unit_case' => (null != $product->getData('unit_case')) ? $product->getData('unit_case') : 'EA',
                        'unit_qty' => (null != $product->getData('unit_qty')) ? $product->getData('unit_qty') : 1,
                        'price' => $product->getPrice(),
                        'gw_id' => $product->getData('gw_id'),
                        'gift_message_id' => $product->getData('gift_message_id'),
                        'billing_address_id' => $product->getData('billing_address_id'),
                        'shipping_address_id' => $product->getData('shipping_address_id'),
                        'delivery_date' => $product->getData('delivery_date'),
                        'is_skip_seasonal' => $product->getData('is_skip_seasonal'),
                        'skip_from' => $product->getData('skip_from'),
                        'skip_to' => $product->getData('skip_to'),
                        'is_spot' => $product->getData('is_spot'),
                        'is_addition' => $product->getData('is_addition'),
                        'stock_point_discount_rate' => $product->getData('stock_point_discount_rate')
                    ];
                    if ($timeSlot != false and $timeSlot->hasData('id')) {
                        $productData['delivery_time'] = $timeSlot->getData('slot_name');
                        $productData['delivery_time_id'] = $product->getData('delivery_time_slot');
                        $productData['delivery_time_from'] = $timeSlot->getData('from');
                        $productData['delivery_time_to'] = $timeSlot->getData('to');
                    } else {
                        $productData['delivery_time'] = null;
                        $productData['delivery_time_id'] = null;
                        $productData['delivery_time_from'] = null;
                        $productData['delivery_time_to'] = null;
                    }
                    $productCartData[] = $productData;
                    if (!isset($profileData['shipping_address'])
                        && $product->getData('shipping_address_id')
                        && $product->getData('billing_address_id')
                    ) {
                        /*Shipping address id*/
                        $profileData['shipping_address_id'] = $product->getData('shipping_address_id');
                        /*Billing addres id*/
                        $profileData['billing_address_id'] = $product->getData('billing_address_id');
                    }
                }
            }
            $profileData['items'] = $productCartData;
        } else {
            $error++;
            $errorMessage[] = __('Product data is null');
        }
        /*Shipping address error*/
        if (isset($profileData['shipping_address_id'])) {
            $shippingAddressModel = $this->customerAddressRepository->getById($profileData['shipping_address_id']);
            $profileData['shipping_address'] = $shippingAddressModel;
        } else {
            $error++;
            $errorMessage[] = __('Shipping address is null');
        }
        /*Billing address error*/
        if (isset($profileData['billing_address_id'])) {
            $billingAddressModel = $this->customerAddressRepository->getById($profileData['billing_address_id']);
            $profileData['billing_address'] = $billingAddressModel;
        } else {
            $error++;
            $errorMessage[] = __('Billing address is null');
        }

        if (count($data->getData('product_cart')) > 0) {
            if ($error) {
                foreach ($errorMessage as $message) {
                    $this->_messageManager->addError($message);
                }
                return false;
            }
        }

        $this->tableManager->createTemporaryTables();

        $this->_registry->unregister(\Riki\Subscription\Helper\Order\Data::PROFILE_GENERATE_STATE_REGISTRY_NAME);
        $this->_registry->register(\Riki\Subscription\Helper\Order\Data::PROFILE_GENERATE_STATE_REGISTRY_NAME, true);

        /*Monthly Fee*/
        $profileData['is_monthly_fee'] = $this->validatorMonthlyFee->isMonthlyFeeProfile($profileData['profile_id']);

        return parent::createMageSimulateQuote($profileData);
    }

    /**
     * @param \Riki\Customer\Api\Data\CustomerInterface $customer
     * @return int
     */
    protected function getCustomerPointBalance($customer)
    {
        $pointBalance = $customer->getCustomAttribute('reward_point');
        if ($pointBalance) {
            return (int)$pointBalance->getValue();
        } else {
            $customerCode = $customer->getCustomAttribute('consumer_db_id');
            if ($customerCode) {
                return $this->rewardManagement->getPointBalance($customerCode->getValue(), true);
            }
        }

        return 0;
    }

    /**
     * Only need to set data to quote instead save to DB for simulator
     *
     * @param Quote $quote
     * @param $userSetting
     * @param $userRedeem
     * @return $this|Data
     */
    protected function saveRewardQuote(Quote $quote, $userSetting, $userRedeem)
    {
        $quote->setData('reward_user_setting', $userSetting)
            ->setData('reward_user_redeem', $userRedeem);

        return $this;
    }

    /**
     * @param $cartEstimation
     * @param null $customer
     * @return \Magento\Framework\Model\AbstractExtensibleModel|\Magento\Sales\Api\Data\OrderInterface|object|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function simulateCartEstimation($cartEstimation, $customer = null) {
        $this->tableManager->createTemporaryTables();
        $cartEstimation->setData('is_simulator', 1);
        return parent::createMageOrderForGillette($cartEstimation, $customer);
    }
}