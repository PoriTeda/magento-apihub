<?php
namespace Riki\AdvancedInventory\Model;

use Magento\Framework\Model\AbstractModel;
use Riki\AdvancedInventory\Api\Data\OutOfStockInterface;

class OutOfStock extends AbstractModel implements OutOfStockInterface
{
    const OOS_FLAG = 'is_oos_order';

    const ADDITIONAL_INFORMATION_AUTHORIZE_TIMES = 'authorize_times';

    const XML_PATH_MAXIMUM_ADDITIONAL_INFORMATION_AUTHORIZE_TIMES = 'advancedinventory_outofstock/generate_order/max_authorize_times';

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    protected $quoteItemFactory;

    /**
     * @var \Riki\SubscriptionMachine\Model\MachineSkusFactory
     */
    protected $machineSkuFactory;

    /**
     * @var \Riki\SubscriptionMachine\Model\MachineCustomerFactory
     */
    protected $machineCustomerFactory;

    /**
     * @var \Riki\AdvancedInventory\Helper\Logger
     */
    protected $loggerHelper;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $productHelper;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    protected $oosPublisher;

    /**
     * @var \Riki\AdvancedInventory\Model\Queue\Schema\OosQueueSchemaFactory $oosQueueSchema
     */
    protected $oosQueueSchemaFactory;

    /**
     * @var \Riki\Loyalty\Model\RewardQuoteFactory
     */
    protected $rewardQuoteFactory;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * OutOfStock constructor.
     *
     * @param \Magento\Framework\Api\SortOrderBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory
     * @param Queue\Schema\OosQueueSchemaFactory $oosQueueSchemaFactory
     * @param \Magento\Framework\MessageQueue\PublisherInterface $oosPublisher
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Riki\AdvancedInventory\Helper\Logger $loggerHelper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\SubscriptionMachine\Model\MachineCustomerFactory $machineCustomerFactory
     * @param \Riki\SubscriptionMachine\Model\MachineSkusFactory $machineSkuFactory
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param Quote\ItemFactory $quoteItemFactory
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory,
        \Riki\AdvancedInventory\Model\Queue\Schema\OosQueueSchemaFactory $oosQueueSchemaFactory,
        \Magento\Framework\MessageQueue\PublisherInterface $oosPublisher,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Riki\AdvancedInventory\Helper\Logger $loggerHelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\SubscriptionMachine\Model\MachineCustomerFactory $machineCustomerFactory,
        \Riki\SubscriptionMachine\Model\MachineSkusFactory $machineSkuFactory,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\AdvancedInventory\Model\Quote\ItemFactory $quoteItemFactory,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->rewardQuoteFactory = $rewardQuoteFactory;
        $this->oosQueueSchemaFactory = $oosQueueSchemaFactory;
        $this->oosPublisher = $oosPublisher;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->productHelper = $productHelper;
        $this->quoteFactory = $quoteFactory;
        $this->loggerHelper = $loggerHelper;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->machineCustomerFactory = $machineCustomerFactory;
        $this->machineSkuFactory = $machineSkuFactory;
        $this->profileFactory = $profileFactory;
        $this->profileRepository = $profileRepository;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->functionCache = $functionCache;
        $this->productRepository = $productRepository;
        $this->quoteRepository = $quoteRepository;
        $this->orderFactory = $orderFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }


    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Riki\AdvancedInventory\Model\ResourceModel\OutOfStock::class);
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function getQuoteId()
    {
        return $this->getData(self::QUOTE_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $quoteId
     *
     * @return $this
     */
    public function setQuoteId($quoteId)
    {
        $this->setData(self::QUOTE_ID, $quoteId);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function getOriginalOrderId()
    {
        return $this->getData(self::ORIGINAL_ORDER_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $originalOrderId
     *
     * @return $this
     */
    public function setOriginalOrderId($originalOrderId)
    {
        $this->setData(self::ORIGINAL_ORDER_ID, $originalOrderId);
        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return string|int
     */
    public function getGeneratedOrderId()
    {
        return $this->getData(self::GENERATED_ORDER_ID);
    }

    /**
     * @inheritdoc
     * @return $this
     */
    public function setGeneratedOrderId($generatedOrderId)
    {
        $this->setData(self::GENERATED_ORDER_ID, $generatedOrderId);
        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return string|int
     */
    public function getProductId()
    {
        return $this->getData(self::PRODUCT_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $productId
     *
     * @return $this
     */
    public function setProductId($productId)
    {
        $this->setData(self::PRODUCT_ID, $productId);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getQty()
    {
        return $this->getData(self::QTY);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $qty
     *
     * @return $this
     */
    public function setQty($qty)
    {
        $this->setData(self::QTY, $qty);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getPrizeId()
    {
        return $this->getData(self::PRIZE_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $prizeId
     *
     * @return $this
     */
    public function setPrizeId($prizeId)
    {
        $this->setData(self::PRIZE_ID, $prizeId);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getQuoteItemId()
    {
        return $this->getData(self::QUOTE_ITEM_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $quoteItemId
     *
     * @return $this
     */
    public function setQuoteItemId($quoteItemId)
    {
        $this->setData(self::QUOTE_ITEM_ID, $quoteItemId);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getSalesruleId()
    {
        return $this->getData(self::SALESRULE_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $salesruleId
     *
     * @return $this
     */
    public function setSalesruleId($salesruleId)
    {
        $this->setData(self::SALESRULE_ID, $salesruleId);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getSubscriptionProfileId()
    {
        return $this->getData(self::SUBSCRIPTION_PROFILE_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $subscriptionProfileId
     *
     * @return $this
     */
    public function setSubscriptionProfileId($subscriptionProfileId)
    {
        $this->setData(self::SUBSCRIPTION_PROFILE_ID, $subscriptionProfileId);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $storeId
     *
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->setData(self::STORE_ID, $storeId);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    /**
     * {@inheritdoc}
     *
     * @param $customerId
     *
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        $this->setData('customer_id', $customerId);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getMachineSkuId()
    {
        return $this->getData('machine_sku_id');
    }

    /**
     * {@inheritdoc}
     *
     * @param $machineSkuId
     *
     * @return $this
     */
    public function setMachineSkuId($machineSkuId)
    {
        $this->setData('machine_sku_id', $machineSkuId);
        return $this;
    }


    /**
     * Getter
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|mixed
     */
    public function getProduct()
    {
        if ($this->functionCache->has($this->getProductId())) {
            return $this->functionCache->load($this->getProductId());
        }

        $product = $this->productRepository->getById($this->getProductId());
        $this->functionCache->store($product, $this->getProductId());

        return $product;
    }

    /**
     * Getter
     *
     * @return \Magento\Quote\Api\Data\CartInterface|mixed
     */
    public function getQuote()
    {
        if ($this->functionCache->has($this->getQuoteId())) {
            return $this->functionCache->load($this->getQuoteId());
        }

        $quote = $this->quoteRepository->get($this->getQuoteId(), [$this->getStoreId()]);
        $this->functionCache->store($quote, $this->getQuoteId());

        return $quote;
    }

    /**
     *  Get quote item
     *
     * @return mixed|null
     */
    public function getQuoteItem()
    {
        if ($this->functionCache->has($this->getQuoteItemId())) {
            return $this->functionCache->load($this->getQuoteItemId());
        }

        $quoteItem = $this->quoteItemFactory
            ->create()
            ->load($this->getQuoteItemId());

        $this->functionCache->store($quoteItem, $this->getQuoteItemId());

        return $quoteItem;
    }

    /**
     * Get flag to detect oos is free
     *
     * @return bool
     */
    public function getIsFree()
    {
        if ($this->getPrizeId() || $this->getSalesruleId() || $this->getMachineSkuId()) {
            return true;
        }

        // Check for case using AMB_DUO_SKU
        if ($this->getIsSkuSpecified()) {
            return true;
        } else {
            $quoteItemData = json_decode($this->getData('quote_item_data') ?: '{}', true);
            if (is_array($quoteItemData)) {
                foreach ($quoteItemData as $itemData) {
                    if (isset($itemData['additional_data']) && $itemData['additional_data']) {
                        $additionalData = json_decode($itemData['additional_data'] ?: '{}', true);
                        if (isset($additionalData['is_sku_specified']) && $additionalData['is_sku_specified']) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * @should: use repository, but currently repository return api data does not contain data which I need gru`u`u`
     *
     * Get profile
     *
     * @return \Riki\Subscription\Model\Profile\Profile|null
     */
    public function getProfile()
    {
        if (!$this->getSubscriptionProfileId()) {
            return null;
        }

        if ($this->functionCache->has($this->getSubscriptionProfileId())) {
            return $this->functionCache->load($this->getSubscriptionProfileId());
        }

        /** @var \Riki\Subscription\Model\Profile\Profile $result */
        $result = $this->profileFactory->create()->load($this->getSubscriptionProfileId());
        $result = $result->getId() ? $result : null;

        $this->functionCache->store($result, $this->getSubscriptionProfileId());

        return $result;
    }

    /**
     * Get customer
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomer()
    {
        if ($this->functionCache->has($this->getCustomerId())) {
            return $this->functionCache->load($this->getCustomerId());
        }

        try {
            $result = $this->customerRepository->getById($this->getCustomerId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->_logger->warning($e);
            $result = null;
        }

        $this->functionCache->store($result, $this->getCustomerId());

        return $result;
    }

    /**
     * Get consumer_db_id of customer
     *
     * @return string
     */
    public function getConsumerDbId()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return '';
        }

        $attr = $customer->getCustomAttribute('consumer_db_id');
        if (!$attr instanceof \Magento\Framework\Api\AttributeValue) {
            return '';
        }

        return $attr->getValue();
    }

    /**
     * get Machine Sku
     *
     * Get MachineSku model
     *
     * @return \Riki\SubscriptionMachine\Model\MachineSkus|null
     */
    public function getMachineSku()
    {
        if (!$this->getMachineSkuId()) {
            return null;
        }

        if ($this->functionCache->has($this->getMachineSkuId())) {
            return $this->functionCache->load($this->getMachineSkuId());
        }

        /** @var \Riki\SubscriptionMachine\Model\MachineSkus $result */
        $result = $this->machineSkuFactory->create()->load($this->getMachineSkuId());
        $result = $result->getId() ? $result : null;

        return $result;
    }

    /**
     * get machine customer
     *
     * Get MachineCustomer model
     *
     * @return \Riki\MachineApi\Model\MachineCustomer|null
     */
    public function getMachineCustomer()
    {
        if (!$this->getMachineSkuId()) {
            return null;
        }

        $machineSku = $this->getMachineSku();
        if (!$machineSku) {
            return null;
        }

        $customer = $this->getCustomer();
        if (!$customer) {
            return null;
        }

        $consumerDbIdAttr = $customer->getCustomAttribute('consumer_db_id');
        $consumerDbId = $consumerDbIdAttr ? $consumerDbIdAttr->getValue() : 0;

        if ($this->functionCache->has($this->getMachineSkuId())) {
            return $this->functionCache->load($this->getMachineSkuId());
        }

        /** @var \Riki\SubscriptionMachine\Model\ResourceModel\MachineCustomer\Collection $collection */
        $collection = $this->machineCustomerFactory->create()->getCollection();
        $collection->addFieldToFilter('consumer_db_id', $consumerDbId)
            ->addFieldToFilter('machine_type_code', $this->getMachineSku()->getData('machine_type_code'))
            ->setPageSize(1);

        /** @var \Riki\MachineApi\Model\MachineCustomer $result */
        $result = $collection->getFirstItem();
        $result = $result->getId() ? $result : null;

        return $result;
    }

    /**
     * Get original order
     *
     * @return \Magento\Sales\Api\Data\OrderInterface|null
     */
    public function getOriginalOrder()
    {
        $key = ['funcKey' => 'sales_order_', $this->getOriginalOrderId()];
        if ($this->functionCache->has($key)) {
            return $this->functionCache->load($key);
        }

        try {
            $result = $this->orderRepository->get($this->getOriginalOrderId());
        } catch (\Magento\Framework\Exception\InputException $e) {
            $result = null;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $result = null;
        }

        $this->functionCache->store($result, $key);

        return $result;
    }

    /**
     * Get generated order
     *
     * @return \Magento\Sales\Api\Data\OrderInterface|null
     */
    public function getGeneratedOrder()
    {
        $key = ['funcKey' => 'sales_order_', $this->getGeneratedOrderId()];
        if ($this->functionCache->has($key)) {
            return $this->functionCache->load($key);
        }

        try {
            $result = $this->orderRepository->get($this->getGeneratedOrderId());
        } catch (\Magento\Framework\Exception\InputException $e) {
            $result = null;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $result = null;
        }

        $this->functionCache->store($result, $key);

        return $result;
    }

    /**
     * Get is all point to checkout
     *
     * @return bool
     */
    public function getIsUseAllPoint()
    {
        $order = $this->getOriginalOrder();
        if (!$order) {
            return false;
        }

        $rewardQuote = $this->rewardQuoteFactory->create()->load($order->getQuoteId(), 'quote_id');
        if (!$rewardQuote->getId()) {
            return false;
        }

        if ($rewardQuote->getRewardUserSetting() == \Riki\Loyalty\Model\RewardQuote::USER_USE_ALL_POINT) {
            return true;
        }

        return false;
    }

    /**
     * Get this oos can charge payment fee on generate oos
     * Only first oos order can charge payment fee
     *
     * @return bool
     */
    public function getCanChargePaymentFee()
    {
        $cacheTag = ['oos_generate_' . $this->getId()];
        $cacheKey = [$this->getId(), 'cacheTag' => $cacheTag];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $result = true;
        if (intval($this->getOriginalOrder()->getFee())) {
            $result = false;
        } elseif ($this->getOriginalOrder()->getFreePaymentWbs()) {
            $result = false;
        } else {
            /** @var \Riki\AdvancedInventory\Model\ResourceModel\OutOfStock $resource */
            $resource = $this->getResource();
            $genOrderIds = $resource->getGenOrderIdsByOrigOrderId($this->getOriginalOrderId());

            if ($genOrderIds) {
                $query = $this->searchCriteriaBuilder
                    ->addFilter('entity_id', $genOrderIds, 'in')
                    ->addFilter('fee', 0, 'gt')
                    ->setPageSize(1)
                    ->create();
                $result = !((bool)$this->orderRepository->getList($query)->getItems());
            }
        }

        $this->functionCache->store($result, $cacheKey);

        return $result;
    }

    /**
     * Init new quote item
     *
     * use for capturing oos
     *
     * @param \Magento\Quote\Model\Quote
     *
     * @return \Magento\Quote\Model\Quote\Item|null
     */
    public function initNewQuoteItem(\Magento\Quote\Model\Quote $quote = null)
    {
        if (!$this->getUniqKey()) {
            return null;
        }

        $oosQuoteItem = $this->getData('quote_item');
        if ($oosQuoteItem instanceof \Magento\Quote\Model\Quote\Item
            && $oosQuoteItem->getOosUniqKey() == $this->getUniqKey()
        ) {
            return $oosQuoteItem;
        }

        /** @var \Magento\Quote\Model\Quote $oosQuote */
        $oosQuote = $this->quoteFactory->create();
        if ($quote) {
            $oosQuote->setCourseId($quote->getCourseId());
            $oosQuote->setFrequencyId($quote->getFrequencyId());
        }

        /** @var \Magento\Catalog\Model\Product $product */
        $product = clone $this->getProduct();
        if (!$product instanceof \Magento\Catalog\Model\Product) {
            return null;
        }

        /** @var \Magento\Framework\DataObject $request */
        $request = $this->dataObjectFactory->create();
        $request->setQty($this->getQty());

        $oosQuote->setIsSuperMode(true);
        $oosQuote->setIsGenerate(1); // by pass \Riki\Quote\Observer\UpdateCartItem::handleProductCase
        $skipSaleable = $this->productHelper->getSkipSaleableCheck();
        $this->productHelper->setSkipSaleableCheck(true);
        
        if ($this->getIsFree()) {
            $product->setPrice(0);
        }

        try {
            /** @var \Magento\Quote\Model\Quote\Item $oosQuoteItem */
            $oosQuoteItem = $oosQuote->addProduct($product, $request);
        } catch (\Exception $e) {
            $this->loggerHelper->getOosLogger()->critical($e);
            $oosQuoteItem = null;
        }

        $oosQuote->setIsSuperMode(false);
        $this->productHelper->setSkipSaleableCheck($skipSaleable);

        if (!$oosQuoteItem instanceof \Magento\Quote\Model\Quote\Item
            || $oosQuoteItem->getHasError()
        ) {
            return null;
        }

        if ($this->getData('unit_qty')) {
            $oosQuoteItem->setData('unit_qty', $this->getData('unit_qty'));
        }

        if ($this->getData('unit_case')) {
            $oosQuoteItem->setData('unit_case', $this->getData('unit_case'));
        }

        if ($this->getData('original_delivery_date')) {
            $oosQuoteItem->setData('original_delivery_date', $this->getData('original_delivery_date'));
        }

        if ($this->getData('gw_id')) {
            $oosQuoteItem->setData('gw_id', $this->getData('gw_id'));
        }

        try {
            $additionalData = \Zend_Json::decode($oosQuoteItem->getData('additional_data') ?: '{}');
        } catch (\Zend_Json_Exception $e) {
            $this->loggerHelper->getOosLogger()->warning($e);
            $additionalData = [];
        }

        $additionalData['original_price'] = $product->getPrice();
        // Add is_duo_machine to addition data for oos item
        // It will use for check to update status 'Pending_for_machine' when this product is in stock
        if ($this->getData('is_duo_machine')) {
            $additionalData['is_duo_machine'] = $this->getData('is_duo_machine');
        }
        // Add is_sku_specified to addition data for oos item
        if ($this->getData('is_sku_specified')) {
            $additionalData['is_sku_specified'] = $this->getData('is_sku_specified');
        }
        $oosQuoteItem->setAdditionalData(\Zend_Json::encode($additionalData));

        $oosQuoteItem->setOosUniqKey($this->getUniqKey());
        $this->setData('quote_item', $oosQuoteItem);

        return $oosQuoteItem;
    }

    /**
     * Create message from this and push into queue
     *
     * @return bool
     */
    public function pushIntoQueue()
    {
        if (!$this->getId()) {
            return false;
        }

        try {
            /** @var \Riki\AdvancedInventory\Model\Queue\OosQueueSchemaInterface $outOfStockSchema */
            $outOfStockSchema = $this->oosQueueSchemaFactory->create();
            $outOfStockSchema->setOosModelId($this->getId());
            $this->oosPublisher->publish('oos.order.generate', $outOfStockSchema);
            $this->setQueueExecute(\Riki\AdvancedInventory\Api\Data\OutOfStock\QueueExecuteInterface::WAITING);
            $this->save();
            $this->loggerHelper->getOosLogger()->info('The oos entity #' . $this->getId() . ' was pushed into queue successfully.');
            return true;
        } catch (\Exception $e) {
            $this->loggerHelper->getOosLogger()->critical($e);
        }

        return false;
    }

    /**
     * @return $this
     */
    public function afterSave()
    {
        if ($this->dataHasChangedFor('generated_order_id')) {
            $machineCustomer = $this->getMachineCustomer();
            if ($machineCustomer && $machineCustomer->getData('status') == 11) { //@should: use const
                $machineCustomer->setData('status', 1)->save(); // @shoud use const & repository
            }

            $origOrder = $this->getOriginalOrder();

            // Get order with lasted data
            $genOrder = $this->orderFactory->create()
                ->load($this->getGeneratedOrderId());

            if ($origOrder) {
                $genOrder->setData('subscription_order_time', $origOrder->getData('subscription_order_time'));
            } else {
                $quoteItem = $this->getQuoteItem();
                try {
                    $additionalData = \Zend_Json::decode($quoteItem->getData('additional_data') ?: '{}');
                    if (isset($additionalData['subscription_order_time'])) {
                        $genOrder->setData('subscription_order_time', $additionalData['subscription_order_time']);
                    }
                } catch (\Zend_Json_Exception $e) {
                    $this->loggerHelper->getOosLogger()->warning($e);
                }
            }

            if ($genOrder->hasDataChanges()) {
                $genOrder->save();
            }
        }

        if ($this->dataHasChangedFor('queue_execute') &&
            is_numeric($this->getQueueExecute()) &&
            $this->getQueueExecute() == \Riki\AdvancedInventory\Api\Data\OutOfStock\QueueExecuteInterface::SUCCESS
        ) {
            $this->getResource()->clearChildrenQty($this);
        }

        parent::afterSave();

        return $this;
    }

    /**
     * @return array
     */
    public function getChildrenQty()
    {
        return $this->getResource()->getChildrenQty($this);
    }

    /**
     * @param $field
     * @return mixed|null
     */
    public function getAdditionalInformation($field)
    {
        $additionalData = $this->getData('additional_data');

        if (is_array($additionalData) && isset($additionalData[$field])) {
            return $additionalData[$field];
        }

        return null;
    }

    /**
     * @param $field
     * @param $value
     * @return $this
     */
    public function setAdditionalInformation($field, $value)
    {
        $additionalData = $this->getData('additional_data');

        if (is_array($additionalData)) {
            $additionalData[$field] = $value;
        } elseif (empty($additionalData)) {
            $additionalData = [$field => $value];
        } else {
            $this->_logger->error(__(
                'The OOS order #%1 have wrong additional data: %2',
                $this->getId(),
                $additionalData
            ));
        }

        $this->setData('additional_data', $additionalData);

        return $this;
    }
}
