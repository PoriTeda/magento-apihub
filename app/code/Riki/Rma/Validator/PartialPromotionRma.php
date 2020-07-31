<?php
namespace Riki\Rma\Validator;

class PartialPromotionRma extends \Zend_Validate_Abstract implements \Magento\Framework\Validator\ValidatorInterface
{
    const WARNING = 'warning';

    /**
     * {@inherit}
     *
     * @var mixed[]
     */
    protected $_messageTemplates = [
        self::WARNING => 'Partial return not possible as the order contains a promotion, bundle product, and/or tier price',
    ];

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Rma\Api\ItemRepositoryInterface
     */
    protected $rmaItemRepository;

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface
     */
    protected $salesRuleRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * PartialPromotionRma constructor.
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\SalesRule\Api\RuleRepositoryInterface $salesRuleRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     * @param \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository
     * @param \Riki\Rma\Helper\Data $dataHelper
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\SalesRule\Api\RuleRepositoryInterface $salesRuleRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository,
        \Riki\Rma\Helper\Data $dataHelper
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->salesRuleRepository = $salesRuleRepository;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->orderItemRepository = $orderItemRepository;
        $this->rmaItemRepository = $rmaItemRepository;
        $this->dataHelper = $dataHelper;

        $this->setTranslator(\Magento\Framework\Validator\AbstractValidator::getDefaultTranslator());
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isValid($value)
    {
        /** @var \Magento\Rma\Model\Rma $value */
        if (!$value instanceof \Magento\Rma\Model\Rma) {
            $this->_error(self::WARNING);
            return false;
        }

        if ($value->getData('full_partial') == \Riki\Rma\Model\Config\Source\Rma\Type::FULL) {
            return true;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->dataHelper->getRmaOrder($value);
        if (!$order instanceof \Magento\Sales\Model\Order) {
            return true;
        }

        if ($this->hasContainBundle($order)
            || $this->hasAppliedPromotion($order)
            || $this->hasAppliedTierPrice($value, $order)
        ) {
            $this->_error(self::WARNING);
            return false;
        }

        return true;
    }

    /**
     * Check order contains bundle product
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    protected function hasContainBundle(\Magento\Sales\Model\Order $order)
    {
        $itemCount = $order->getParentItemsRandomCollection()->getSize();
        return $itemCount != $order->getTotalItemCount();
    }

    /**
     * Check order contains promotions
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    protected function hasAppliedPromotion(\Magento\Sales\Model\Order $order)
    {
        $ruleIds = $order->getData('applied_rule_ids');
        if (!$ruleIds) {
            return false;
        }

        $query = $this->searchCriteriaBuilder
            ->addFilter('rule_id', explode(',', $ruleIds), 'in')
            ->addFilter('ignore_warning_rma', \Riki\Rma\Api\Data\SalesRule\IgnoreWarningRmaInterface::NO)
            ->setPageSize(1)
            ->create();

        $result = $this->salesRuleRepository->getList($query);
        if (!$result->getTotalCount()) {
            return false;
        }

        return true;
    }

    /**
     * Check return product has applied tier price
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    protected function hasAppliedTierPrice(
        \Magento\Rma\Model\Rma $rma,
        \Magento\Sales\Model\Order $order
    )
    {
        $query = $this->searchCriteriaBuilder
            ->addFilter('rma_entity_id', $rma->getId())
            ->create();

        $result = $this->rmaItemRepository->getList($query);
        if (!$result->getTotalCount()) {
            return false;
        }

        foreach ($result->getItems() as $item) {
            try {
                $orderItem =  $this->orderItemRepository->get($item->getData('order_item_id'));
                if ($item->getData('qty_requested') == $orderItem->getQtyOrdered()) {
                    continue;
                }

                $product = $this->productRepository->getById($orderItem->getProductId());
                foreach ($product->getTierPrices() as $tierPrice) {
                    if ($tierPrice->getValue() != $orderItem->getBasePrice()) {
                        continue;
                    }

                    if ($tierPrice->getQty() > $item->getData('qty_requested')) {
                        return true;
                    }
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->logger->critical($e);
                continue;
            }
        }

        return false;
    }

}