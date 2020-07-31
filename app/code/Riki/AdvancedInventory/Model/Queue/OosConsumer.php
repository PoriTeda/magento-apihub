<?php
namespace Riki\AdvancedInventory\Model\Queue;

use Magento\Catalog\Model\Product\Type as ProductType;
use Riki\AdvancedInventory\Api\Data\OutOfStock\QueueExecuteInterface;
use Riki\AdvancedInventory\Exception\RestrictedOrderStatusException;
use Riki\Sales\Helper\Order;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\Exception\ValidatorException;
use Riki\CatalogInventory\Model\StockRegistryProvider;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;

class OosConsumer
{

    const FLAG_RUNNING = 'advanced_inventory_generate_oos_order_running';

    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;

    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock\Quote
     */
    protected $quoteHelper;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $quoteManagement;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;

    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock
     */
    protected $outOfStockHelper;

    /**
     * @var \Riki\Framework\Helper\Scope
     */
    protected $scopeHelper;

    /**
     * @var \Magento\Quote\Api\Data\CartInterfaceFactory
     */
    protected $quoteFactory;

    /**
     * @var \Riki\AdvancedInventory\Helper\Logger
     */
    protected $loggerHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $storeEmulation;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Riki\ShipLeadTime\Api\StockStateInterface
     */
    protected $shipLeadTimeStockState;

    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $helperCourse;

    /**
     * @var \Magento\CatalogInventory\Model\StockRegistryStorage
     */
    protected $stockRegistryStorage;

    /**
     * @var \Riki\AdvancedInventory\Model\Stock
     */
    protected $stockModel;

    /**
     * @var \Wyomind\AdvancedInventory\Api\StockRepositeryInterface
     */
    protected $wyomindStockRepository;

    /**
     * OosConsumer constructor.
     * @param \Magento\Store\Model\App\Emulation $storeEmulation
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\AdvancedInventory\Helper\Logger $loggerHelper
     * @param \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory
     * @param \Riki\Framework\Helper\Scope $scopeHelper
     * @param \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Quote\Api\CartManagementInterface $quoteManagement
     * @param \Riki\AdvancedInventory\Helper\OutOfStock\Quote $quoteHelper
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Riki\ShipLeadTime\Api\StockStateInterface $shipLeadTimeStockState
     * @param \Riki\SubscriptionCourse\Helper\Data $helperCourse
     */
    public function __construct(
        \Magento\Store\Model\App\Emulation $storeEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\Registry $registry,
        \Riki\AdvancedInventory\Helper\Logger $loggerHelper,
        \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory,
        \Riki\Framework\Helper\Scope $scopeHelper,
        \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Riki\AdvancedInventory\Helper\OutOfStock\Quote $quoteHelper,
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\ShipLeadTime\Api\StockStateInterface $shipLeadTimeStockState,
        \Riki\SubscriptionCourse\Helper\Data $helperCourse,
        \Magento\CatalogInventory\Model\StockRegistryStorage $stockRegistryStorage,
        \Wyomind\AdvancedInventory\Api\StockRepositeryInterface $wyomindStockRepository,
        \Riki\AdvancedInventory\Model\Stock $stockModel
    ) {
        $this->helperCourse = $helperCourse;
        $this->storeEmulation = $storeEmulation;
        $this->storeManager = $storeManager;
        $this->functionCache = $functionCache;
        $this->registry = $registry;
        $this->loggerHelper = $loggerHelper;
        $this->quoteFactory = $quoteFactory;
        $this->scopeHelper = $scopeHelper;
        $this->outOfStockHelper = $outOfStockHelper;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->quoteManagement = $quoteManagement;
        $this->quoteHelper = $quoteHelper;
        $this->outOfStockRepository = $outOfStockRepository;
        $this->quoteRepository = $quoteRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipLeadTimeStockState = $shipLeadTimeStockState;
        $this->stockRegistryStorage = $stockRegistryStorage;
        $this->wyomindStockRepository = $wyomindStockRepository;
        $this->stockModel = $stockModel;
    }


    /**
     * Consumer generate order for out of stock product
     * @param \Riki\AdvancedInventory\Model\Queue\OosQueueSchemaInterface $OosQueueInterface
     * @return void
     */
    public function execute(\Riki\AdvancedInventory\Model\Queue\OosQueueSchemaInterface $OosQueueInterface)
    {
        $outOfStockId = $OosQueueInterface->getOosModelId();
        $this->loggerHelper->getOosLogger()->info("Start generate for oos #{$outOfStockId}");

        $itemIds = explode(',', $outOfStockId);

        $criteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $itemIds, 'in')
            ->addFilter('generated_order_id', true, 'null')
            ->create();

        $outOfStockList = $this->outOfStockRepository->getList($criteria);

        if (!$outOfStockList->getTotalCount()) {
            return;
        }

        $outOfStock = $outOfStockList->getItems();

        $defaultStoreId = $this->getDefaultStoreIdForOutOfStockOrder($outOfStock);
        // double set store id to make sure get correct data depend on store id
        $this->storeManager->setCurrentStore($defaultStoreId);
        $this->storeEmulation->startEnvironmentEmulation($defaultStoreId);

        // clean any cache on this oos
        $this->functionCache->invalidateByCacheTag([
            'oos_generate_' . $outOfStockId
        ]);

        $this->scopeHelper->inFunction(__METHOD__, [
            'outOfStock' => $outOfStock
        ]);
        $this->registry->unregister('current_oos_generating');
        $this->registry->register('current_oos_generating', $outOfStock);

        /*validate current out of stock item and get exactly item will be generated order*/
        $outOfStockItemWillBeGeneratedOrder = $this->validate($outOfStock);
        try {
            $this->registry->unregister('skip_validate_by_oos_order_generating');
            $this->registry->register('skip_validate_by_oos_order_generating', true);
            $quote = $this->initialize($outOfStockItemWillBeGeneratedOrder);
            $this->loggerHelper->getOosLogger()->info("Initialized quote for oos #{$outOfStockId}");
            /*list out of stock item id(string) will be generated order*/
            $generateItemIds = implode(',', array_keys($outOfStockItemWillBeGeneratedOrder));
            $this->loggerHelper->getOosLogger()->info("Oos #{$generateItemIds} generated quote #{$quote->getId()}");
        } catch (\Exception $exInitQuote) {
            $this->loggerHelper->getOosLogger()->error("Fail to initialize quote for oss #{$outOfStockId}" .
                " due to " . $exInitQuote->getMessage() . " " . $exInitQuote->getTraceAsString());
            throw new LocalizedException(__($exInitQuote->getMessage()));
        }

        $this->registry->unregister('current_oos_quote_generated');
        $this->registry->register('current_oos_quote_generated', $quote);

        $oosObject = reset($outOfStock);
        $profileId = $oosObject->getSubscriptionProfileId();
        if ($profileId) {
            $quote->setProfileId($profileId);
        }
        $quote->setIsOosOrder(1);

        $orderId = $this->quoteManagement->placeOrder($quote->getId());
        $this->registry->unregister('skip_validate_by_oos_order_generating');
        $this->loggerHelper->getOosLogger()->info("Oos #{$generateItemIds} generated order #{$orderId}");

        $this->update($outOfStockItemWillBeGeneratedOrder, $orderId);
        $this->loggerHelper->getOosLogger()->info("Oos #{$generateItemIds} generated successfully");

        $this->registry->unregister('disable_riki_check_fraud_sales_order_place_before');
        $this->registry->unregister('skip_cumulative_promotion');
        $this->scopeHelper->outFunction(__METHOD__);

        $this->storeEmulation->stopEnvironmentEmulation();
    }

    /**
     * Validate input data
     *
     * @param [] $outOfStockList
     * @return array
     *
     * @throws ValidatorException
     * @throws LocalizedException
     */
    public function validate(array $outOfStockList)
    {
        /*list out of stock item will be generated order, after validate*/
        $outOfStockItemWillBeGeneratedOrder = [];
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteFactory->create();

        $shippingAddress = false;

        $rejectRequeue = false;
        /** @var \Riki\AdvancedInventory\Model\OutOfStock $oos */
        foreach ($outOfStockList as $oos) {
            /*original order data*/
            $origOrder = $this->outOfStockHelper->getOriginalOrder($oos);
            if (!$origOrder instanceof  \Magento\Sales\Model\Order) {
                $this->loggerHelper->getOosLogger()->info('Original order does not existed');
                continue;
            }

            if ($oos->getIsFree() && $origOrder->getStatus() == OrderStatus::STATUS_ORDER_PENDING_CC) {
                $msg = sprintf('%s is pending cc', $origOrder->getIncrementId());
                $this->loggerHelper->getOosLogger()->info($msg);
                continue;
            }

            /** @var \Magento\Catalog\Model\Product $product */
            $this->functionCache->invalidate($oos->getProductId());
            $this->stockRegistryStorage->removeStockItem($oos->getProductId());
            $product = $this->productRepository->getById(
                $oos->getProductId(),
                false,
                $oos->getStoreId(),
                true
            );
            $product->setIsOosProduct(1);
            $this->functionCache->invalidateByCacheTag(['stock_update_qty_' . $product->getId()]);
            $this->removeNegativeStockFromProduct($product, $oos->getStoreId());
            if ($product->getTypeId() == ProductType::TYPE_BUNDLE) {
                $bundleItemProducts = $this->getBundleItemProducts($oos);
                foreach ($bundleItemProducts as $childProduct) {
                    $this->functionCache->invalidateByCacheTag(['stock_update_qty_' . $childProduct->getId()]);
                    $this->stockRegistryStorage->removeStockItem($childProduct->getId());
                    $childProduct->setIsOosProduct(1);
                }
            }

            if (!$product->getIsSalable()) {
                $this->changeOutOfStockItemQueueExecute($oos);
                $msg = __(
                    'Product %1 is not saleable,  can not generate order for item #%2',
                    $oos->getProductId(),
                    $oos->getId()
                );
                $this->loggerHelper->getOosLogger()->info($msg);
                continue;
            }

            if (!$shippingAddress) {
                /*generate shipping address for out of stock quote*/
                $shippingAddress = $this->quoteHelper->generateShippingAddressForOutOfStockOrder($origOrder, $quote);
                $quote->setShippingAddress($shippingAddress);
            }

            $product->setIsOosProduct(1);

            if (!$this->canAddToCart($oos, $quote, $product)) {
                $this->changeOutOfStockItemQueueExecute($oos);
                continue;
            }

            /*restrict state which cannot generated out of stock order*/
            $restrictState = ['holded'];
            if (in_array($origOrder->getState(), $restrictState)) {
                $msg = sprintf('%s is holded or closed state', $origOrder->getIncrementId());
                $this->loggerHelper->getOosLogger()->info($msg);
                continue;
            }

            /*cannot generate order if original order was canceled*/
            $deniedState = ['canceled', 'closed'];
            if (in_array($origOrder->getState(), $deniedState)) {
                $msg = sprintf('%s is canceled / closed state', $origOrder->getIncrementId());
                $this->loggerHelper->getOosLogger()->info($msg);
                throw new RestrictedOrderStatusException(__($msg));
            }

            $outOfStockItemWillBeGeneratedOrder[$oos->getId()] = $oos;
        }

        if (!$outOfStockItemWillBeGeneratedOrder) {
            $msg = sprintf('All of items are not available.');
            throw new ValidatorException(__($msg));
        }

        return $outOfStockItemWillBeGeneratedOrder;
    }

    /**
     * Initialize cart data
     *
     * @param [] $oos
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function initialize(array $outOfStockList)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteHelper->generate($outOfStockList);
        $billingAddress = $quote->getBillingAddress();
        $this->scopeHelper->inFunction(__METHOD__, [
            'outOfStock' => $outOfStockList,
            'quote' => $quote
        ]);
        $this->registry->unregister('skip_cumulative_promotion');
        $this->registry->register('skip_cumulative_promotion', $quote->getId()); // no cumulated gif on oos order
        $this->registry->unregister('disable_riki_check_fraud_sales_order_place_before');
        $this->registry->register('disable_riki_check_fraud_sales_order_place_before', $quote->getId()); // no fraud check on oos order
        /**
         * Purpose: to make sure that original_price, base_origin_price is set to quote item by the way cache quote data
         * Reason: vendor/magento/module-quote/Model/QuoteManagement.php:326 will be reload quote, quote_item from db.
         * This cause quote item missing original_price, base_origin_price because these fields don't exist
         * in quote_item tbl.
         * By default magento: original_price, base_origin_price will bet set to quote item when quote collect totals
         * vendor/magento/module-quote/Model/Quote/Address/Total/Subtotal.php:140
         */
        $quote = $this->quoteRepository->get($quote->getId(), [$quote->getStoreId()]);
        $newBillingAddress = $quote->getBillingAddress();
        if ($newBillingAddress->getId() != $billingAddress->getId()) {
            $this->loggerHelper->getOosLogger()->info('NED-4837: OOS generate order has duplicated billing address with empty data , fixed by workaround solution');
            $currentId = $newBillingAddress->getId();
            $newBillingAddress->setData($billingAddress->getData());
            $newBillingAddress->setId($currentId);
            $newBillingAddress->save();
        }
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            try {
                $additionalData = \Zend_Json::decode($quoteItem->getData('additional_data') ?: '{}');
                if (isset($additionalData['original_price'])) {
                    $quoteItem->setBaseOriginalPrice($additionalData['original_price']);
                }
            } catch (\Zend_Json_Exception $e) {
                $this->loggerHelper->getOosLogger()->warning($e);
            }
        }

        return $quote;
    }

    /**
     * Update data after generate order
     *
     * @param [] $oos
     * @param $orderId
     */
    public function update(array $oosList, $orderId)
    {
        if (!$oosList) {
            return;
        }

        $genOrder = false;
        $origOrder = false;
        foreach ($oosList as $oos) {
            $oos->setGeneratedOrderId($orderId);
            $this->changeOutOfStockItemQueueExecute($oos, QueueExecuteInterface::SUCCESS);
            $oos->setData('invalidate_cache', 1);
            if (!$genOrder) {
                $genOrder = $this->outOfStockHelper->getGeneratedOrder($oos);
            }

            if (!$origOrder) {
                $origOrder = $this->outOfStockHelper->getOriginalOrder($oos);
            }
        }

        if (!$genOrder instanceof \Magento\Sales\Model\Order) {
            return;
        }

        if (!$origOrder instanceof \Magento\Sales\Model\Order) {
            return;
        }

        $orderType = $origOrder->getRikiType();
        $isDelayPayment = false;
        if ($orderType == Order::RIKI_TYPE_SUBSCRIPTION) {
            if ($genOrder->getGrandTotal() > 0) {
                $additionalData = json_decode($oos->getAdditionalData(), true);
                if (isset($additionalData['is_delay_payment'])
                    && isset($additionalData['subscription_type'])) {
                    $isDelayPayment = $additionalData['is_delay_payment'];
                    $subscriptionType = $additionalData['subscription_type'];

                    if ($subscriptionType == SubscriptionType::TYPE_SUBSCRIPTION && $isDelayPayment) {
                        $orderType = Order::RIKI_TYPE_DELAY_PAYMENT;
                    }
                }
            }
        } elseif ($orderType == Order::RIKI_TYPE_DELAY_PAYMENT) {
            if ($genOrder->getGrandTotal() == 0) {
                $orderType = Order::RIKI_TYPE_SUBSCRIPTION;
            }
        }

        if ($orderType == Order::RIKI_TYPE_SUBSCRIPTION && !$isDelayPayment) {
            $listAgents = [
                \Riki\DelayPayment\Helper\Data::PAYMENT_AGENT_NICOS,
                \Riki\DelayPayment\Helper\Data::PAYMENT_AGENT_JCB
            ];
            $paymentAgent = $genOrder->getData('payment_agent');
            $matchPaymentAgent = (str_replace($listAgents, '', $paymentAgent) != $paymentAgent);
            if ($matchPaymentAgent) {
                $genOrder->setData('payment_agent', str_replace('2', '', $paymentAgent));
            }
        }


        $genOrder->setRikiType($orderType);
        $genOrder->setSubscriptionProfileId($oos->getSubscriptionProfileId());
        $genOrder->setRemoteIp('0.0.0.0'); // mark order oos and by pass paygent validate @see \Bluecom\Paygent\Model\Paygent

        try {
            $this->orderRepository->save($genOrder);
        } catch (\Exception $e) {
            $this->loggerHelper->getOosLogger()->info($e->getMessage());
        }
    }

    /**
     * get default store for out of stock order
     *
     * @param $outOfStock
     * @return int|string
     */
    private function getDefaultStoreIdForOutOfStockOrder($outOfStock)
    {
        if ($outOfStock) {
            /** @var \Riki\AdvancedInventory\Model\OutOfStock $oos */
            foreach ($outOfStock as $oos) {
                if ($oos->getStoreId()) {
                    return $oos->getStoreId();
                }
            }
        }

        $defaultStore = $this->storeManager->getDefaultStoreView();

        if ($defaultStore) {
            return $defaultStore->getId();
        }

        return 1;
    }

    /**
     * Change out of stock item queue_execute flag
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock $oos
     * @param bool $queueExecute
     * @return bool
     */
    private function changeOutOfStockItemQueueExecute(
        \Riki\AdvancedInventory\Model\OutOfStock $oos,
        $queueExecute = false
    ) {
        if (!$queueExecute) {
            $queueExecute = new \Zend_Db_Expr('NULL');
        }

        $oos->setData('queue_execute', $queueExecute);
        try {
            $this->outOfStockRepository->save($oos);
            return true;
        } catch (\Exception $e) {
            $this->loggerHelper->getOosLogger()->info($e->getMessage());
        }

        return false;
    }

    /**
     * @param \Riki\AdvancedInventory\Model\OutOfStock $oos
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Catalog\Model\Product $product
     * @return boolean
     */
    private function canAddToCart($oos, $quote, $product)
    {
        try {
            if ($product->getTypeId() == ProductType::TYPE_BUNDLE) {
                $bundleItemProducts = $this->getBundleItemProducts($oos);
                foreach ($bundleItemProducts as $childProduct) {
                    if (!$this->isSalableProduct($oos, $quote, $childProduct)) {
                        return false;
                    }
                    $requestedQty = $childProduct->getData('bundle_item_requested_qty');
                    $childProduct->setIsOosProduct(1);
                    if (!$this->tryAddingToCart($oos, $quote, $childProduct, $requestedQty)) {
                        return false;
                    }
                }
            } else {
                if (!$this->isSalableProduct($oos, $quote, $product)) {
                    return false;
                }
                return $this->tryAddingToCart($oos, $quote, $product, $oos->getQty());
            }
        } catch (\Exception $e) {
            $this->loggerHelper->getOosLogger()->info($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * @param \Riki\AdvancedInventory\Model\OutOfStock $oos
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Catalog\Model\Product $product
     * @param int $qty
     * @return bool
     * @throws LocalizedException
     */
    protected function tryAddingToCart($oos, $quote, $product, $qty)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $quote->addProduct($product, $qty);
        if ($quoteItem->getHasError()) {
            $msg = $quoteItem->getMessage(true);
            $this->loggerHelper->getOosLogger()->info($msg);
            return false;
        }
        return true;
    }

    /**
     * @param \Riki\AdvancedInventory\Model\OutOfStock $oos
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getBundleItemProducts($oos)
    {
        $bundleItemProducts = [];
        $quoteItemData = $oos->getData('quote_item_data');
        if ($quoteItemData) {
            $quoteItemData = json_decode($quoteItemData, true);
            foreach ($quoteItemData as $bundle) {
                if (isset($bundle['children'])) {
                    foreach ($bundle['children'] as $bundleItem) {
                        $this->functionCache->invalidate($bundleItem['product_id']);
                        $product = $this->productRepository->getById($bundleItem['product_id']);
                        $product->setData('bundle_item_requested_qty', $bundleItem['qty']);
                        $bundleItemProducts[] = $product;
                    }
                }
            }
        }
        return $bundleItemProducts;
    }

    /**
     * @param \Riki\AdvancedInventory\Model\OutOfStock $oos
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     */
    private function isSalableProduct($oos, $quote, $product)
    {
        $this->registry->unregister(StockRegistryProvider::UNREGISTER_STOCK_ITEM);
        $this->registry->register(StockRegistryProvider::UNREGISTER_STOCK_ITEM, $product->getId());
        if (!$product->getIsSalable()) {
            $msg = __(
                'Product %1 is not saleable,  can not generate order for item #%2',
                $product->getId(),
                $oos->getId()
            );
            $this->loggerHelper->getOosLogger()->info($msg);
            return false;
        }
        $availableQty = $this->shipLeadTimeStockState->checkAvailableQty(
            $quote,
            $product->getSku(),
            $oos->getQty()
        );
        if ($availableQty < $oos->getQty()) {
            $msg = __(
                'Product %1 is out of stock, can not generate order for item #%2',
                $oos->getProductId(),
                $oos->getId()
            );
            $this->loggerHelper->getOosLogger()->info($msg);
            return false;
        }
        return true;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param array $orderData
     */
    private function removeNegativeStockFromProduct($product, $storeId)
    {

        if ($product->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
            /** @var \Riki\Catalog\Model\Product\Bundle\Type $bundleTypeInstance */
            $bundleTypeInstance = $product->getTypeInstance();
            $optionCollection = $bundleTypeInstance->getOptionsCollection($product);
            $selectionOptions = $bundleTypeInstance->getSelectionsCollection($optionCollection->getAllIds(), $product);

            foreach ($selectionOptions as $selection) {
                $this->removeNegativeStockFromProduct($selection, $storeId);
            }
        } else {
            // Check and remove negative quantity from stock setting
            $stockSetting = $this->stockModel->getStockSettingsByStoreId($product->getId(), $storeId)->getData();

            $negativeStocks = array_filter($stockSetting, function ($value, $key) {
                return preg_match('/^quantity_[\d]+/', $key) && $value < 0;
            }, ARRAY_FILTER_USE_BOTH);

            if (!empty($negativeStocks)) {
                foreach ($negativeStocks as $stockKey => $quantity) {
                    $placeId = explode('quantity_', $stockKey)[1];

                    if ($stockSetting['use_config_setting_for_backorders_'. $placeId] == 1 ) {
                        if ($stockSetting['default_use_default_setting_for_backorder_' . $placeId] == 1) {
                            if ($stockSetting['backorders_' . $placeId] != 0) {
                                continue;
                            }
                        } else if ($stockSetting['default_allow_backorder_'. $placeId] != 0) {
                            continue;
                        }
                    } else if ($stockSetting['backorder_allowed_' . $placeId] != 0) {
                        continue;
                    }

                    $this->wyomindStockRepository->updateStock(
                        $product->getId(),
                        1,
                        $placeId,
                        $stockSetting['manage_stock_' . $placeId],
                        0,
                        $stockSetting['backorder_allowed_' . $placeId],
                        $stockSetting['default_use_default_setting_for_backorder_' . $placeId]
                    );
                }
                $this->wyomindStockRepository->updateInventory($product->getId());
            }
        }
    }
}
