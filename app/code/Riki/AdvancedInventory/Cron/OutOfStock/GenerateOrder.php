<?php
namespace Riki\AdvancedInventory\Cron\OutOfStock;

use \Magento\Cron\Observer\ProcessCronQueueObserver;

use Riki\AdvancedInventory\Api\Data\OutOfStock\QueueExecuteInterface;
use Riki\AdvancedInventory\Api\ConfigInterface;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;

class GenerateOrder
{
    const FLAG_RUNNING = 'advanced_inventory_generate_oos_order_running';

    /**
     * @var \Magento\Cron\Model\Schedule
     */
    protected $schedule;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;

    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock\Quote
     */
    protected $quoteHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

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
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $storeEmulation;

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
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * @var \Magento\Quote\Api\Data\CartInterfaceFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Cron\Model\ScheduleFactory
     */
    protected $scheduleFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Shell
     */
    protected $shell;

    /**
     * @var \Riki\AdvancedInventory\Helper\Logger
     */
    protected $loggerHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * GenerateOrder constructor.
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\AdvancedInventory\Helper\Logger $loggerHelper
     * @param \Magento\Framework\Shell $shell
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Cron\Model\ScheduleFactory $scheduleFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper
     * @param \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory
     * @param \Riki\Framework\Helper\Scope $scopeHelper
     * @param \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     * @param \Magento\Store\Model\App\Emulation $storeEmulation
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Quote\Api\CartManagementInterface $quoteManagement
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\AdvancedInventory\Helper\OutOfStock\Quote $quoteHelper
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Riki\AdvancedInventory\Helper\Logger $loggerHelper,
        \Magento\Framework\Shell $shell,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Cron\Model\ScheduleFactory $scheduleFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory,
        \Riki\Framework\Helper\Scope $scopeHelper,
        \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper,
        \Magento\Store\Model\App\Emulation $storeEmulation,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Psr\Log\LoggerInterface $logger,
        \Riki\AdvancedInventory\Helper\OutOfStock\Quote $quoteHelper,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
    ) {
        $this->quoteRepository = $cartRepository;
        $this->storeManager = $storeManager;
        $this->registry = $registry;
        $this->loggerHelper = $loggerHelper;
        $this->shell = $shell;
        $this->scopeConfig = $scopeConfig;
        $this->scheduleFactory = $scheduleFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->datetimeHelper = $datetimeHelper;
        $this->quoteFactory = $quoteFactory;
        $this->scopeHelper = $scopeHelper;
        $this->outOfStockHelper = $outOfStockHelper;
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->storeEmulation = $storeEmulation;
        $this->stockRegistry = $stockRegistry;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->quoteManagement = $quoteManagement;
        $this->logger = $logger;
        $this->quoteHelper = $quoteHelper;
        $this->searchHelper = $searchHelper;
        $this->outOfStockRepository = $outOfStockRepository;
    }

    /**
     * Execute
     *
     * @param \Magento\Cron\Model\Schedule $schedule
     *
     * @return bool
     */
    public function execute(\Magento\Cron\Model\Schedule $schedule)
    {
        $this->registry->unregister(static::FLAG_RUNNING);
        $this->registry->register(static::FLAG_RUNNING, true);
        $this->loggerHelper->getOosLogger()->info('Starting schedule ' . $schedule->getId());
        $this->schedule = $schedule;
        $running = $this->getRunningJob();
        if ($running->getId()) {
            return true;
        }

        $schedule->setData('messages', $this->getPidInfo(getmypid()))->save();
        $this->scopeHelper->inFunction(__METHOD__);

        $limit = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->outOfStock()
            ->generateOrder()
            ->cronBatchLimit();
        $pageSize = $limit < 5 ? $limit : 5;
        $count = 0;
        $this->loggerHelper->getOosLogger()->info('Limit: ' . $limit);
        $this->loggerHelper->getOosLogger()->info('Page size: ' . $pageSize);
        do {
            $query = $this->searchCriteriaBuilder
                ->addFilter('queue_execute', QueueExecuteInterface::WAITING)
                ->addFilter('generated_order_id', new \Zend_Db_Expr('NULL'), 'is')
                ->addFilter('quote_item_id', new \Zend_Db_Expr('NOT NULL'), 'is')
                ->setPageSize($pageSize)
                ->create();
            $outOfStocks = $this->outOfStockRepository->getList($query)->getItems();

            if (!$outOfStocks) {
                return true;
            }

            if ($count >= $limit ) {
                return true;
            }

            /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
            foreach ($outOfStocks as $outOfStock) {
                $count++;
                $this->storeManager->setCurrentStore($outOfStock->getStoreId());
                $this->storeEmulation->startEnvironmentEmulation($outOfStock->getStoreId());
                $this->process($outOfStock);
                $this->storeEmulation->stopEnvironmentEmulation();
            }
        } while($outOfStocks);
        $this->loggerHelper->getOosLogger()->info('Finish schedule ' . $schedule->getId() . '. Totals: ' . $count);

        $this->scopeHelper->outFunction(__METHOD__);
        $this->registry->unregister(static::FLAG_RUNNING);
        return true;
    }

    /**
     * Process out of stock item
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock $outOfStock
     *
     * @return void
     */
    public function process(\Riki\AdvancedInventory\Model\OutOfStock $outOfStock)
    {
        $this->scopeHelper->inFunction(__METHOD__, [
            'outOfStock' => $outOfStock
        ]);
        $this->loggerHelper->getOosLogger()->info('Generating oos: ' . $outOfStock->getId());
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteFactory->create();
        try {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->productRepository->getById($outOfStock->getProductId(), false, $outOfStock->getStoreId());
            if (!$product->getIsSalable()) {
                $outOfStock->setData('queue_execute', new \Zend_Db_Expr('NULL'));
                $this->loggerHelper->getOosLogger()->warning(sprintf('Product %s is not saleable ', $outOfStock->getProductId()));
                $this->outOfStockRepository->save($outOfStock);
                return;
            }
            $quoteItem = $quote->addProduct($product, $outOfStock->getQty());
            if ($quoteItem->getHasError()) {
                $outOfStock->setData('queue_execute', new \Zend_Db_Expr('NULL'));
                $this->loggerHelper->getOosLogger()->warning($quoteItem->getMessage(true));
                $this->outOfStockRepository->save($outOfStock);
                return;
            }

            $origOrder = $this->outOfStockHelper->getOriginalOrder($outOfStock);
            if ($outOfStock->getIsFree()
                && $origOrder
                && $origOrder->getStatus() == OrderStatus::STATUS_ORDER_PENDING_CC
            ) {
                $this->loggerHelper->getOosLogger()->warning(sprintf('%s is pending cc', $origOrder->getIncrementId()));
                $outOfStock->setData('queue_execute', new \Zend_Db_Expr('NULL'));
                $this->outOfStockRepository->save($outOfStock);
                return;
            }

            $quote = $this->quoteHelper->generate($outOfStock);
            $this->scopeHelper->inFunction(__METHOD__, [
                'outOfStock' => $outOfStock,
                'quote' => $quote
            ]);
            $this->registry->unregister('skip_cumulative_promotion');
            $this->registry->register('skip_cumulative_promotion', $quote->getId()); // no cumulated gif on oos order
            $this->registry->unregister('disable_riki_check_fraud_sales_order_place_before');
            $this->registry->register('disable_riki_check_fraud_sales_order_place_before', $quote->getId()); // no fraud check on oos order

            // correct the original price because we no collect totals, the original price missed
            $quote = $this->quoteRepository->get($quote->getId());
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

            $orderId = $this->quoteManagement->placeOrder($quote->getId());
            $outOfStock->setGeneratedOrderId($orderId);
            $outOfStock->setData('queue_execute', QueueExecuteInterface::SUCCESS);
            $this->outOfStockRepository->save($outOfStock);

            $outOfStock->setData('invalidate_cache', 1);
            $genOrder = $this->outOfStockHelper->getGeneratedOrder($outOfStock);

            if ($genOrder) {
                $orderType = $origOrder ? $origOrder->getData('riki_type') : null;
                if (!$orderType) {
                    $orderType = $outOfStock->getProfile()
                        ? ($outOfStock->getProfile()->getData('hanpukai_qty') ? 'HANPUKAI' : 'SUBSCRIPTION')
                        : 'SPOT';
                }
                $genOrder->setData('riki_type', $orderType);
                $genOrder->setData('subscription_profile_id', $outOfStock->getSubscriptionProfileId());
                $genOrder->setRemoteIp('0.0.0.0'); // mark order oos and by pass paygent validate @see \Bluecom\Paygent\Model\Paygent
                $this->orderRepository->save($genOrder);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->loggerHelper->getOosLogger()->warning($e);
            if ($quote->getHasError()) {
                $outOfStock->setData('queue_execute', new \Zend_Db_Expr('NULL'));
            } else {
                $outOfStock->setData('queue_execute', QueueExecuteInterface::ERROR);
            }
            $this->outOfStockRepository->save($outOfStock);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->loggerHelper->getOosLogger()->critical($e);
            $outOfStock->setData('queue_execute', QueueExecuteInterface::ERROR);
            $this->outOfStockRepository->save($outOfStock);
        }
        $this->loggerHelper->getOosLogger()->info('Finish oos: ' . $outOfStock->getId());
        $this->registry->unregister('disable_riki_check_fraud_sales_order_place_before');
        $this->registry->unregister('skip_cumulative_promotion');
        $this->scopeHelper->outFunction(__METHOD__);
    }

    /**
     * Awake cron
     *
     * Try to self-schedule this cron
     *
     * @return boolean
     */
    public function awake()
    {
        $jobCode = 'riki_advanced-inventory_out-of-stock_generate_order';
        $cronExpression = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->outOfStock()
            ->generateOrder()
            ->cronScheduleExecute();
        if (!$cronExpression) {
            return false;
        }

        $pending = $this->getPendingJob();
        if ($pending->getId()) {
            return false;
        }

        /** @var \Magento\Cron\Model\Schedule $running */
        $running = $this->getRunningJob();
        if ($running->getId()) {
            return false;
        }

        // copy logic from \Magento\Cron\Observer\ProcessCronQueueObserver::saveSchedule
        $groupId = 'default';
        $scheduleAheadFor = (int)$this->scopeConfig->getValue(
            'system/cron/' . $groupId . '/' . \Magento\Cron\Observer\ProcessCronQueueObserver::XML_PATH_SCHEDULE_AHEAD_FOR,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $timeInterval = $scheduleAheadFor * ProcessCronQueueObserver::SECONDS_IN_MINUTE;
        $currentTime = $this->datetimeHelper->scopeTimeStamp();
        $timeAhead = $currentTime + $timeInterval;
        for ($time = $currentTime; $time < $timeAhead; $time += ProcessCronQueueObserver::SECONDS_IN_MINUTE) {
            $scheduleAt = strftime('%Y-%m-%d %H:%M', $time);
            $createAt = strftime('%Y-%m-%d %H:%M:%S', $currentTime);
            if ($createAt > $scheduleAt) {
                continue;
            }

            $schedule = $this->scheduleFactory->create()
                ->setCronExpr($cronExpression)
                ->setJobCode($jobCode)
                ->setStatus(\Magento\Cron\Model\Schedule::STATUS_PENDING)
                ->setCreatedAt($createAt)
                ->setScheduledAt($scheduleAt);
            if ($schedule->trySchedule()) {
                // time matches cron expression
                $schedule->save();
                return true;
            }
        }

        return false;
    }

    /**
     * Get pending job
     *
     * @return \Magento\Cron\Model\Schedule
     */
    public function getPendingJob()
    {
        $jobCode = 'riki_advanced-inventory_out-of-stock_generate_order';
        $pending = $this->scheduleFactory->create()
            ->getCollection()
            ->addFieldToFilter('job_code', ['eq' => $jobCode])
            ->addFieldToFilter('status', ['eq' => \Magento\Cron\Model\Schedule::STATUS_PENDING])
            ->setPageSize(1)
            ->getFirstItem();

        return $pending;
    }

    /**
     * Get running job
     *
     * @return \Magento\Cron\Model\Schedule
     */
    public function getRunningJob()
    {
        $jobCode = 'riki_advanced-inventory_out-of-stock_generate_order';
        /** @var \Magento\Cron\Model\ResourceModel\Schedule\Collection $collection */
        $collection = $this->scheduleFactory->create()
            ->getCollection()
            ->addFieldToFilter('job_code', ['eq' => $jobCode])
            ->addFieldToFilter('status', ['eq' => \Magento\Cron\Model\Schedule::STATUS_RUNNING])
            ->setOrder('scheduled_at')
            ->setPageSize(1);

        if ($this->schedule instanceof \Magento\Cron\Model\Schedule
            && $this->schedule->getId()
        ) {
            $collection->addFieldToFilter('schedule_id', ['neq' => $this->schedule->getId()]);
        }

        /** @var \Magento\Cron\Model\Schedule $running */
        $running = $collection->getFirstItem();
        if ($running && $running->getId()) {
            $pId = intval($running->getMessages());
            $pInfo = $this->getPidInfo($pId);
            if ($pInfo == $running->getMessages()) {
                return $running;
            }
        }

        return $this->scheduleFactory->create();
    }

    /**
     * Get process id info
     *
     * @param $pid
     *
     * @return string
     */
    public function getPidInfo($pid)
    {
        if (!$pid) {
            return '';
        }

        try {
            $ps = $this->shell->execute("ps -p {$pid} -wo pid,lstart");
            $ps = explode("\n", $ps);

            if (count($ps) < 2) {
                return '';
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->loggerHelper->getOosLogger()->warning($e);
            return '';
        }

        return trim((string)end($ps));
    }
}
