<?php
namespace Riki\Wamb\Model;

use Riki\Wamb\Api\Data\History\EventInterface;
use Riki\Wamb\Api\Data\Rule\IsActiveInterface;

class RuleApplier
{
    /**
     * @var array
     */
    protected $indexData = [];

    /**
     * @var RuleRepository
     */
    protected $ruleRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Riki\Wamb\Model\RegisterRepository
     */
    protected $registerRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $oosRepository;

    /**
     * RuleApplier constructor.
     *
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $oosRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Wamb\Model\RegisterRepository $registerRepository
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RuleRepository $ruleRepository
     */
    public function __construct(
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $oosRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Wamb\Model\RegisterRepository $registerRepository,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Wamb\Model\RuleRepository $ruleRepository
    ) {
        $this->oosRepository = $oosRepository;
        $this->customerRepository = $customerRepository;
        $this->registerRepository = $registerRepository;
        $this->functionCache = $functionCache;
        $this->profileRepository = $profileRepository;
        $this->ruleRepository = $ruleRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Index data to match rule
     *
     * @param \Magento\Sales\Model\Order $order
     */
    public function index(\Magento\Sales\Model\Order $order)
    {
        if (!$order->getId() || isset($this->indexData[$order->getId()])) {
            return;
        }

        $idx = [];
        foreach ($order->getAllItems() as $orderItem) {
            $product = $orderItem->getProduct();
            if (!$product instanceof \Magento\Catalog\Model\Product) {
                continue;
            }
            foreach ($product->getCategoryIds() as $categoryId) {
                $idxKey = intval($orderItem->getQtyOrdered()) . '_' . $categoryId . '_' . $product->getEntityId();
                $idx[$idxKey] = $categoryId;
            }
        }

        // get oos item
        $query = $this->searchCriteriaBuilder
            ->addFilter('original_order_id', $order->getId())
            ->create();
        /** @var \Riki\AdvancedInventory\Model\OutOfStock $oosItem */
        foreach ($this->oosRepository->getList($query)->getItems() as $oosItem) {
            $product = $oosItem->getProduct();
            if (!$product instanceof \Magento\Catalog\Model\Product) {
                continue;
            }

            foreach ($product->getCategoryIds() as $categoryId) {
                $idxKey = intval($oosItem->getQty()) . '_' . $categoryId . '_' . $product->getEntityId();
                $idx[$idxKey] = $categoryId;
            }
        }

        $this->indexData[$order->getId()] = $idx;
    }

    /**
     * Validate order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return int|string
     *
     * @throws \Exception
     */
    public function validate(\Magento\Sales\Model\Order $order)
    {
        $profileId = intval($order->getSubscriptionProfileId());
        if (!$profileId) {
            return 0;
        }

        try {
            $profile = $this->profileRepository->get($profileId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return 0;
        } catch (\Exception $e) {
            throw $e;
        }

        $query = $this->searchCriteriaBuilder
            ->addFilter('course_id', $profile->getCourseId())
            ->addFilter('is_active', IsActiveInterface::IS_ACTIVE)
            ->create();
        $rules = $this->ruleRepository->getList($query);
        if (!$rules->getTotalCount()) {
            return 0;
        }

        $this->index($order);

        if (!$this->indexData[$order->getId()]) {
            return 0;
        }

        foreach ($rules->getItems() as $rule) {
            $candidates = array_intersect($this->indexData[$order->getId()], $rule->getCategoryIds());
            $qtyOrdered = array_sum(array_keys($candidates));
            if ($qtyOrdered >= $rule->getMinPurchaseQty()) {
                return $rule->getRuleId();
            }
        }

        return 0;
    }

    /**
     * Apply rule for order
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @param $ruleId
     */
    public function apply(\Magento\Sales\Model\Order $order, $ruleId)
    {
        $customer = $this->customerRepository->getById($order->getCustomerId());

        $consumerDbAttr = $customer->getCustomAttribute('consumer_db_id');
        $register = $this->registerRepository->createFromArray([
            'customer_id' => $customer->getId(),
            'consumer_db_id' => ($consumerDbAttr instanceof \Magento\Framework\Api\AttributeInterface) ? $consumerDbAttr->getValue() : $customer->getId()
        ]);

        $register->setStatus(\Riki\Wamb\Api\Data\Register\StatusInterface::WAITING);
        $register->setOrderId($order->getId());
        $register->setRuleId($ruleId);
        $this->registerRepository->save($register);

        $msg = "The consumer [{$register->getConsumerDbId()}] is applied register rule [{$register->getRuleId()}]";
        $register->addHistory(EventInterface::ORDER_MATCH_APPLY_RULE, $msg, [
            'order_id' => $register->getOrderId(),
            'rule_id' => $register->getRuleId()
        ]);
    }
}