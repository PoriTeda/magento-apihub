<?php
namespace Riki\Wamb\Model;

use Riki\Wamb\Api\Data\Register\StatusInterface;

class Register extends \Magento\Framework\Model\AbstractModel implements \Riki\Wamb\Api\Data\RegisterInterface
{
    /**
     * @var \Riki\Wamb\Model\HistoryRepository
     */
    protected $historyRepository;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Riki\Wamb\Helper\ConfigData
     */
    protected $configDataHelper;

    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    protected $rikiCustomerRepository;

    /**
     * @var \Riki\Customer\Model\AmbCustomerRepository
     */
    protected $ambCustomerRepository;

    /**
     * Wamb constructor.
     *
     * @param \Riki\Customer\Model\AmbCustomerRepository $ambCustomerRepository
     * @param \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository
     * @param \Riki\Wamb\Helper\ConfigData $configDataHelper
     * @param HistoryRepository $historyRepository
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Riki\Customer\Model\AmbCustomerRepository $ambCustomerRepository,
        \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository,
        \Riki\Wamb\Helper\ConfigData $configDataHelper,
        \Riki\Wamb\Model\HistoryRepository $historyRepository,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->ambCustomerRepository = $ambCustomerRepository;
        $this->rikiCustomerRepository = $rikiCustomerRepository;
        $this->configDataHelper = $configDataHelper;
        $this->functionCache = $functionCache;
        $this->orderRepository = $orderRepository;
        $this->historyRepository = $historyRepository;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }


    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(\Riki\Wamb\Model\ResourceModel\Register::class);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $customerId
     *
     * @return \Riki\Wamb\Api\Data\RegisterInterface
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getConsumerDbId()
    {
        return $this->getData(self::CONSUMER_DB_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $consumer_db_id
     *
     * @return \Riki\Wamb\Api\Data\RegisterInterface
     */
    public function setConsumerDbId($consumer_db_id)
    {
        return $this->setData(self::CONSUMER_DB_ID, $consumer_db_id);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $status
     *
     * @return \Riki\Wamb\Api\Data\RegisterInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param $orderId
     *
     * @return \Riki\Wamb\Api\Data\RegisterInterface
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getRuleId()
    {
        return $this->getData(self::RULE_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param $ruleId
     *
     * @return \Riki\Wamb\Api\Data\RegisterInterface
     */
    public function setRuleId($ruleId)
    {
        return $this->setData(self::RULE_ID, $ruleId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getRegisterId()
    {
        return $this->getData(self::REGISTER_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $registerId
     *
     * @return \Riki\Wamb\Api\Data\RegisterInterface
     */
    public function setRegisterId($registerId)
    {
        return $this->setData(self::REGISTER_ID, $registerId);
    }

    /**
     * Get order
     *
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder()
    {
        if (!$this->hasData('order')) {
            try {
                $order = $this->orderRepository->get($this->getOrderId());
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $order = null;
                // silence
            }
            $this->setData('order', $order);
        }

        return $this->getData('order');
    }

    /**
     * Add customer history
     *
     * @param $event
     * @param $message
     * @param $detail
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     */
    public function addHistory($event, $message, $detail = [])
    {
        $detail = \Zend_Json::encode($detail);
        $history = $this->historyRepository->createFromArray([
            'customer_id' => $this->getCustomerId(),
            'consumer_db_id' => $this->getConsumerDbId(),
            'event' => $event,
            'message' => $message,
            'detail' => $detail
        ]);

        $this->historyRepository->save($history);

        return $history;
    }

    /**
     * Check wamb can register
     *
     * @return bool
     */
    public function getCanRegister()
    {
        $allowedStatus = [
            StatusInterface::ERROR,
            StatusInterface::WAITING,
        ];
        if (!in_array($this->getStatus(), $allowedStatus)) {
            return false;
        }

        $order = $this->getOrder();
        if (!$order instanceof \Magento\Sales\Model\Order) {
            return false;
        }

        if (!$order->getId()) {
            return false;
        }

        $allowedOrderStatus = $this->configDataHelper->getWambAllowedOrderStatus();
        if (!in_array($order->getStatus(), $allowedOrderStatus)) {
            return false;
        }

        return true;
    }

    /**
     * Get consumer info
     *
     * @return array
     */
    public function getConsumerInfo()
    {
        $cacheKey = [$this->getConsumerDbId()];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $info = $this->rikiCustomerRepository->prepareInfoSubCustomer($this->getConsumerDbId());
        $this->functionCache->store($info, $cacheKey);

        return $info;
    }

    /**
     * Get wamb is registered ?
     *
     * @return bool
     */
    public function getIsRegistered()
    {
        $consumerInfo = $this->getConsumerInfo() ?: [];

        if (!isset($consumerInfo['WAMB_Status']) || $consumerInfo['WAMB_Status'] != 1) {
            return false;
        }

        return true;
    }

    /**
     * Get wamb consumer is wamb membership ?
     *
     * @return bool
     */
    public function getIsWambMembership()
    {
        $consumerInfo = $this->getConsumerInfo() ?: [];

        if (!isset($consumerInfo['WELLNESSCLUB_AMB']) || $consumerInfo['WELLNESSCLUB_AMB'] != 1) {
            return false;
        }

        return true;
    }

    /**
     * Get wamb consumer is amb membership?
     */
    public function getIsAmbMembership()
    {
        $consumerInfo = $this->getConsumerInfo() ?: [];

        if (!isset($consumerInfo['amb_type']) || $consumerInfo['amb_type'] != 1) {
            return false;
        }

        return true;
    }
}
