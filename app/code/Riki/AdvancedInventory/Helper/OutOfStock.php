<?php
namespace Riki\AdvancedInventory\Helper;

use Riki\AdvancedInventory\Api\ConstantInterface;

class OutOfStock extends \Magento\Framework\App\Helper\AbstractHelper
{
    const RIKI_CONFIG_CRON_QUEUE_OOSCONSUMER = 'advancedinventory_outofstock/generate_order/trigger_consumer_cron_expression';

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Quote\Api\Data\CartInterfaceFactory
     */
    protected $quoteFactory;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * TODO: should implement repository for prize, not able now
     *
     * @var \Riki\Prize\Model\PrizeFactory
     */
    protected $prizeFactory;
    /**
     * TODO: should use repository, but now datainterface of rule not compatible with reqs, later
     *
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $salesRuleFactory;

    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    protected $quoteItemFactory;

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * @var \Riki\AdvancedInventory\Model\OutOfStock\Repository
     */
    protected $outOfStockRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Riki\CatalogRule\Model\Framework\Mview\Processor
     */
    protected $mviewProcessor;

    /**
     * OutOfStock constructor.
     *
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
     * @param \Magento\SalesRule\Model\RuleFactory $salesRuleFactory
     * @param \Riki\Prize\Model\PrizeFactory $prizeFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Riki\AdvancedInventory\Model\OutOfStock\Repository $outOfStockRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Riki\CatalogRule\Model\Framework\Mview\Processor $mviewProcessor
     */
    public function __construct(
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Magento\SalesRule\Model\RuleFactory $salesRuleFactory,
        \Riki\Prize\Model\PrizeFactory $prizeFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\App\Helper\Context $context,
        \Riki\AdvancedInventory\Model\OutOfStock\Repository $outOfStockRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\CatalogRule\Model\Framework\Mview\Processor $mviewProcessor
    ) {
        $this->datetimeHelper = $datetimeHelper;
        $this->orderItemRepository = $orderItemRepository;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->salesRuleFactory = $salesRuleFactory;
        $this->prizeFactory = $prizeFactory;
        $this->orderRepository = $orderRepository;
        $this->searchHelper = $searchHelper;
        $this->quoteFactory = $quoteFactory;
        $this->quoteRepository = $quoteRepository;
        $this->functionCache = $functionCache;
        $this->outOfStockRepository = $outOfStockRepository;
        $this->timezone = $timezone;
        $this->mviewProcessor = $mviewProcessor;
        parent::__construct($context);
    }

    /**
     * Get quote for store Out Of Stock item
     *
     * @return \Magento\Quote\Api\Data\CartInterface|mixed|null
     */
    public function getOutOfStockQuote()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }

        $getMethod = 'getBy' . $this->searchHelper->generateGetMethod(ConstantInterface::OOS_QUOTE_FIELD);
        $quote = $this->searchHelper
            ->$getMethod(ConstantInterface::OOS_QUOTE_VALUE)
            ->getOne()
            ->execute($this->quoteRepository);

        if ($quote) {
            $this->functionCache->store($quote);
            return $quote;
        }

        $quote = $this->quoteFactory->create();
        $quote->setData('store_id', 0);
        $quote->setData('is_active', 0);
        $quote->setdata(ConstantInterface::OOS_QUOTE_FIELD, ConstantInterface::OOS_QUOTE_VALUE);
        try {
            $quote->save();
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }

        $this->functionCache->store($quote);

        return $quote;
    }

    /**
     * Get generated order
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock $outOfStock
     *
     * @return \Magento\Sales\Model\Order|\Magento\Sales\Api\Data\OrderInterface
     */
    public function getGeneratedOrder(\Riki\AdvancedInventory\Model\OutOfStock $outOfStock)
    {
        if ($this->functionCache->has($outOfStock->getGeneratedOrderId())
            && !$outOfStock->getData('invalidate_cache')
        ) {
            return $this->functionCache->load($outOfStock->getGeneratedOrderId());
        }

        $result = $this->searchHelper
            ->getByEntityId($outOfStock->getGeneratedOrderId())
            ->getOne()
            ->execute($this->orderRepository);

        $this->functionCache->store($result, $outOfStock->getGeneratedOrderId());

        return $result;
    }

    /**
     * Get prize
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock $outOfStock
     *
     * @return \Riki\Prize\Model\Prize
     */
    public function getPrize(\Riki\AdvancedInventory\Model\OutOfStock $outOfStock)
    {
        if ($this->functionCache->has($outOfStock->getPrizeId())
            && !$outOfStock->getData('invalidate_cache')
        ) {
            return $this->functionCache->load($outOfStock->getPrizeId());
        }

        $prize = $this->prizeFactory->create()->load($outOfStock->getPrizeId());
        $this->functionCache->store($prize, $outOfStock->getPrizeId());

        return $prize;
    }

    /**
     * Get sale rule
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock $outOfStock
     *
     * @return mixed|null
     */
    public function getSalesRule(\Riki\AdvancedInventory\Model\OutOfStock $outOfStock)
    {
        if ($this->functionCache->has($outOfStock->getSalesruleId())
            && !$outOfStock->getData('invalidate_cache')
        ) {
            return $this->functionCache->load($outOfStock->getSalesruleId());
        }

        $rule = $this->salesRuleFactory->create()->load($outOfStock->getSalesruleId());
        $this->functionCache->store($rule, $outOfStock->getSalesruleId());

        return $rule;
    }

    /**
     * Get original order
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock $outOfStock
     *
     * @return \Magento\Sales\Model\Order|\Magento\Sales\Api\Data\OrderInterface
     */
    public function getOriginalOrder(\Riki\AdvancedInventory\Model\OutOfStock $outOfStock)
    {
        if ($this->functionCache->has($outOfStock->getOriginalOrderId())
            && !$outOfStock->getData('invalidate_cache')
        ) {
            return $this->functionCache->load($outOfStock->getOriginalOrderId());
        }

        try {
            $order = $this->orderRepository->get($outOfStock->getOriginalOrderId());
            $this->functionCache->store($order, $outOfStock->getOriginalOrderId());
            return $order;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }

        return null;
    }

    /**
     * Get payment of out of stock order
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock $outOfStock
     *
     * @return \Magento\Sales\Model\Order\Payment|null
     */
    public function getPayment(\Riki\AdvancedInventory\Model\OutOfStock $outOfStock)
    {
        if ($this->functionCache->has($outOfStock->getOriginalOrderId())
            && !$outOfStock->getData('invalidate_cache')
        ) {
            return $this->functionCache->load($outOfStock->getOriginalOrderId());
        }

        $order = $this->getOriginalOrder($outOfStock);
        if (!$order) {
            return null;
        }

        return $order->getPayment();
    }

    /**
     * Get payment method code
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock $outOfStock
     *
     * @return string
     */
    public function getPaymentMethodCode(\Riki\AdvancedInventory\Model\OutOfStock $outOfStock)
    {
        if ($this->functionCache->has($outOfStock->getOriginalOrderId())
            && !$outOfStock->getData('invalidate_cache')
        ) {
            return $this->functionCache->load($outOfStock->getOriginalOrderId());
        }

        $payment = $this->getPayment($outOfStock);
        if (!$payment) {
            return '';
        }

        return $payment->getMethod();
    }

    /**
     * Get quote item of out of stock
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock $outOfStock
     *
     * @return \Magento\Quote\Model\Quote\Item[]
     */
    public function getQuoteItems(\Riki\AdvancedInventory\Model\OutOfStock $outOfStock)
    {
        $result = [];

        if (!$outOfStock->getQuoteItemData()) {
            return $result;
        }
        $quoteItemData = json_decode($outOfStock->getQuoteItemData(), true);
        foreach ($quoteItemData as $itemData) {
            if (isset($itemData['item_id'])) {
                $quoteItemModel = $this->quoteItemFactory->create();
                unset($itemData['item_id']);
                /** Parse qty to integer to map with code in
                    vendor/magento/module-quote/Model/Quote/Item/CartItemPersister.php:75 */
                $itemData['qty'] = (int)$itemData['qty'];
                $quoteItemModel->setData($itemData);
                $result[] = $quoteItemModel;
            }
        }
        
        return $result;
    }

    /**
     * Get order item which have min delivery_date
     *
     * @param \Magento\Framework\DataObject $object
     *
     * @return \Magento\Sales\Model\Order\Item
     */
    public function getMinDeliveryOrderItem(\Magento\Framework\DataObject $object)
    {
        $id = 0;
        if ($object instanceof \Riki\AdvancedInventory\Model\OutOfStock) {
            $id = $object->getData('order_id');
        }
        if ($this->functionCache->has($id)
            && !$object->getData('invalidate_cache')
        ) {
            return $this->functionCache->load($id);
        }

        $result = $this->searchHelper
            ->getByOrderId($id)
            ->sortByDeliveryDate(\Magento\Framework\Api\SortOrder::SORT_ASC)
            ->getOne()
            ->execute($this->orderItemRepository);

        $this->functionCache->store($result, $id);

        return $result;
    }

    /**
     * Make oos quote always alive and valid data. (no deleted by cron clean)
     *
     * @return void
     */
    public function updateOutOfStockQuote()
    {
        $quote = $this->getOutOfStockQuote();
        if (!$quote->getId()) {
            return;
        }

        // direct sql used by boost performance
        // direct sql will be remove via refactor
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $conn */
        $conn = $quote->getResource()->getConnection();
        $conn->update($conn->getTableName('quote'), [
            'is_active' => 0,
            'updated_at' => $this->datetimeHelper->toDb()
        ], 'entity_id = ' . $quote->getId());
    }

    /**
     * @param $orderId
     * @return \Riki\AdvancedInventory\Model\OutOfStock | null
     */
    public function getOosItemByGeneratedOrder($orderId)
    {
        $result = $this->searchHelper
            ->getByGeneratedOrderId($orderId)
            ->getOne()
            ->execute($this->outOfStockRepository);

        if ($result && $result->getId()) {
            return $result;
        }

        return null;
    }

    /**
     * Is out of stock order
     *
     * @param $orderId
     * @return bool
     */
    public function isOutOfStockOrder($orderId)
    {
        if ($this->getOosItemByGeneratedOrder($orderId)) {
            return true;
        }

        return false;
    }

    /**
     * get list of out of stock model from an order
     *
     * @param $orderId
     * @return mixed
     */
    public function getOutOfStocksByOrder($orderId)
    {
        if ($orderId instanceof \Magento\Sales\Model\Order) {
            $orderId = $orderId->getId();
        }

        return $this->searchHelper
            ->getByOriginalOrderId($orderId)
            ->getAll()
            ->execute($this->outOfStockRepository);
    }

    /**
     * process generate OOS within the time period set in the backend
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CronException
     */
    public function allowGenerateOrder($loggerHelper)
    {
        $e = $this->scopeConfig->getValue(self::RIKI_CONFIG_CRON_QUEUE_OOSCONSUMER);
        if (!$e) {
            $loggerHelper->getOosLogger()->critical('RIKI_CONFIG_CRON_QUEUE_OOSCONSUMER not not exists');
            return false;
        }

        $e = preg_split('#\s+#', $e, null, PREG_SPLIT_NO_EMPTY);
        if (sizeof($e) < 5 || sizeof($e) > 6) {
            $loggerHelper->getOosLogger()->critical('Invalid cron expression: %1', $e);
        }

        $currentTime = $this->timezone->scopeTimeStamp();

        if($this->mviewProcessor->matchCronExpression($e[0], strftime('%M', $currentTime)) && $this->mviewProcessor->matchCronExpression($e[1], strftime('%H', $currentTime))) {
            return true;
        }
        return false;
    }
}
