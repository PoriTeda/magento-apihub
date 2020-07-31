<?php
namespace Riki\Rma\Model;

class Item extends \Magento\Rma\Model\Item implements \Riki\Rma\Api\Data\ItemInterface
{
    const SKU = 'sku';

    /**
     * @var \Riki\Framework\Helper\Cache\AppCache
     */
    protected $appCache;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    /**
     * Item constructor.
     *
     * @param \Riki\Framework\Helper\Cache\AppCache $appCache
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Rma\Model\RmaFactory $rmaFactory
     * @param \Magento\Rma\Model\Item\Attribute\Source\StatusFactory $statusFactory
     * @param \Magento\Sales\Model\Order\ItemFactory $itemFactory
     * @param \Magento\Rma\Model\Item\FormFactory $formFactory
     * @param \Magento\Framework\App\RequestFactory $requestFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     * @param array $data
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\AppCache $appCache,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Rma\Model\RmaFactory $rmaFactory,
        \Magento\Rma\Model\Item\Attribute\Source\StatusFactory $statusFactory,
        \Magento\Sales\Model\Order\ItemFactory $itemFactory,
        \Magento\Rma\Model\Item\FormFactory $formFactory,
        \Magento\Framework\App\RequestFactory $requestFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        array $data = []
    ) {
        $this->appCache = $appCache;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderItemRepository = $orderItemRepository;
        $this->functionCache = $functionCache;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $rmaFactory,
            $statusFactory,
            $itemFactory,
            $formFactory,
            $requestFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }


    /**
     * {@inheritdoc}
     */
    protected function _init($resourceModel)
    {
        parent::_init($resourceModel);
        $this->_collectionName = 'Riki\Rma\Model\ResourceModel\Item\Collection';
    }

    /**
     * {@inheritdoc}
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get order item
     *
     * @return \Magento\Sales\Api\Data\OrderItemInterface
     */
    public function getOrderItem()
    {
        return $this->orderItemRepository->get($this->getOrderItemId());
    }

    /**
     * Get parent order item
     *
     * @return \Magento\Sales\Api\Data\OrderItemInterface|null
     */
    public function getParentOrderItem()
    {
        $orderItem = $this->getOrderItem();
        if (!$orderItem || !$orderItem->getParentItemId()) {
            return null;
        }

        if ($this->functionCache->has($orderItem->getParentItemId())) {
            return $this->functionCache->load($orderItem->getParentItemId());
        }

        $result = $this->orderItemRepository->get($orderItem->getParentItemId());

        $this->functionCache->store($result, $orderItem->getParentItemId());

        return $result;
    }

    /**
     * Get bundle option
     *
     * @return array
     */
    public function getBundleOptions()
    {
        $result = [];
        $parentOrderItem = $this->getParentOrderItem();
        if (!$parentOrderItem) {
            return $result;
        }

        $cacheKey = [$parentOrderItem->getItemId()];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $query = $this->searchCriteriaBuilder
            ->addFilter('parent_item_id', $parentOrderItem->getItemId())
            ->create();
        $childOrderItems = $this->orderItemRepository->getList($query)->getItems();

        $maxPrice = 0;
        foreach ($childOrderItems as $childOrderItem) {
            $pOptions = (array)$childOrderItem->getProductOptions();
            if (!isset($pOptions['bundle_selection_attributes'])) {
                continue;
            }
            $sOptions = $this->serializer->unserialize($pOptions['bundle_selection_attributes']);
            $result[$childOrderItem->getItemId()] = [
                'price' => isset($sOptions['price']) ? intval($sOptions['price']) : 0,
                'qty' => isset($sOptions['qty']) ? intval($sOptions['qty']) : 0
            ];

            $beMaxPrice = isset($result[$maxPrice])
                && $result[$childOrderItem->getItemId()]['price'] >= $result[$maxPrice]['price'];
            if ($maxPrice == 0 || $beMaxPrice) {
                $maxPrice = $childOrderItem->getItemId();
            }
        }
        if (isset($result[$maxPrice])) {
            $result[$maxPrice]['maxPrice'] = true;
        }
        $this->functionCache->store($result, $cacheKey);

        return $result;
    }

    /**
     * {@deprecated}
     *
     * Get tax_amount of order item
     *
     * @return int
     */
    public function getOrderItemTaxAmount()
    {
        $cacheKey = [$this->getOrderItemId()];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $orderItem = $this->getOrderItem();
        $result = intval($orderItem->getData('tax_riki'));
        if ($orderItem->getParentItemId()) {
            $parentOrderItem = $this->getParentOrderItem();
            if ($parentOrderItem && intval($parentOrderItem->getData('tax_riki'))) {
                $bundleOptions = $this->getBundleOptions();
                if (isset($bundleOptions[$this->getOrderItemId()])) {
                    $taxAmount = ($bundleOptions[$this->getOrderItemId()]['price'] * intval($parentOrderItem->getData('tax_riki')) / $parentOrderItem->getData('price'));
                    if (isset($bundleOptions[$this->getOrderItemId()]['maxPrice'])) {
                        $result = ceil($taxAmount);
                    } else {
                        $result = floor($taxAmount);
                    }
                }
            }
        }

        $this->functionCache->store($result, $cacheKey);

        return $result;
    }

    /**
     * Get order item tax percent
     *
     * @return float
     */
    public function getOrderItemTaxPercent()
    {
        $cacheKey = [$this->getOrderItemId()];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $parentOrderItem = $this->getParentOrderItem();
        if ($parentOrderItem) {
            $result = floatval($parentOrderItem->getData('tax_percent'));
        } else {
            $orderItem = $this->getOrderItem();
            $result = floatval($orderItem->getData('tax_percent'));
        }

        $this->functionCache->store($result, $cacheKey);

        return $result;
    }

    /**
     * Get order item price
     *
     * @return float
     */
    public function getOrderItemPrice()
    {
        $cacheKey = [$this->getOrderItemId()];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $orderItem = $this->getOrderItem();
        $result = $orderItem->getPrice();

        $bundleOptions = $this->getBundleOptions();
        if (isset($bundleOptions[$this->getOrderItemId()]['price'])) {
            $result = $bundleOptions[$this->getOrderItemId()]['price'] / $bundleOptions[$this->getOrderItemId()]['qty'];
        }

        $this->functionCache->store($result, $cacheKey);

        return $result;
    }

    /**
     * Get order item discount_amount
     *
     * @return float
     */
    public function getOrderItemDiscountAmount()
    {
        $cacheKey = [$this->getOrderItemId()];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $orderItem = $this->getOrderItem();
        $result = $orderItem->getDiscountAmount();
        if ($orderItem->getParentItemId()) {
            $parentOrderItem = $this->getParentOrderItem();
            if ($parentOrderItem && intval($parentOrderItem->getDiscountAmount())) {
                $bundleOptions = $this->getBundleOptions();
                if (isset($bundleOptions[$this->getOrderItemId()])) {
                    $discountAmount = ($bundleOptions[$this->getOrderItemId()]['price']) * $parentOrderItem->getDiscountAmount() / ($parentOrderItem->getPrice());
                    if (isset($bundleOptions[$this->getOrderItemId()]['maxPrice'])) {
                        $result = ceil($discountAmount);
                    } else {
                        $result = floor($discountAmount);
                    }
                }
            }
        }

        $this->functionCache->store($result, $cacheKey);

        return $result;
    }

    /**
     * Get sku
     *
     * @return string
     */
    public function getSku()
    {
        return $this->getData(self::SKU);
    }

    /**
     * Set sku
     *
     * @param string $sku
     * @return \Riki\Rma\Api\Data\ItemInterface
     */
    public function setSku($sku)
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * Get order_item_id
     *
     * @return int
     */
    public function getOrderItemId()
    {
        return $this->getData(self::ORDER_ITEM_ID);
    }

    /**
     * Set order_item_id
     *
     * @param int $id
     * @return \Riki\Rma\Api\Data\ItemInterface
     */
    public function setOrderItemId($id)
    {
        return $this->setData(self::ORDER_ITEM_ID, $id);
    }
}