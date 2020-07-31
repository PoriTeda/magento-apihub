<?php
namespace Riki\AdvancedInventory\Plugin\CatalogInventory\Model\Stock;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Api\SortOrder;
use Riki\AdvancedInventory\Api\Data\OutOfStock\QueueExecuteInterface;
use Riki\AdvancedInventory\Api\ConfigInterface;

class StockItemRepository
{
    /**
     * @var bool
     */
    protected $isEnabled;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Riki\Framework\Helper\Scope
     */
    protected $scopeHelper;

    /**
     * @var \Riki\AdvancedInventory\Cron\OutOfStock\GenerateOrder
     */
    protected $generatedOrderCron;

    /**
     * @var \Wyomind\AdvancedInventory\Api\StockRepositeryInterface
     */
    protected $stockRepository;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    protected $publisher;

    /**
     * @var \Riki\AdvancedInventory\Model\Queue\Schema\OosQueueSchemaFactory $oosQueueSchema
     */
    protected $oosQueueSchemaFactory;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searcherCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /** @var SortOrder  */
    protected $sortOrder;

    /** @var \Riki\AdvancedInventory\Api\OutOfStockManagementInterface  */
    protected $outOfStockManagement;

    /**
     * StockItemRepository constructor.
     * @param \Wyomind\AdvancedInventory\Api\StockRepositeryInterface $stockRepository
     * @param \Riki\AdvancedInventory\Cron\OutOfStock\GenerateOrder $generateOrderCron
     * @param \Riki\Framework\Helper\Scope $scopeHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
     * @param \Riki\AdvancedInventory\Model\Queue\Schema\OosQueueSchemaFactory $oosQueueSchemaFactory
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param SortOrder $sortOrder
     * @param \Riki\AdvancedInventory\Api\OutOfStockManagementInterface $outOfStockManagement
     */
    public function __construct(
        \Wyomind\AdvancedInventory\Api\StockRepositeryInterface $stockRepository,
        \Riki\AdvancedInventory\Cron\OutOfStock\GenerateOrder $generateOrderCron,
        \Riki\Framework\Helper\Scope $scopeHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Psr\Log\LoggerInterface $logger,
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository,
        \Riki\AdvancedInventory\Model\Queue\Schema\OosQueueSchemaFactory $oosQueueSchemaFactory,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SortOrder $sortOrder,
        \Riki\AdvancedInventory\Api\OutOfStockManagementInterface $outOfStockManagement

    ) {
        $this->stockRepository = $stockRepository;
        $this->generatedOrderCron = $generateOrderCron;
        $this->scopeHelper = $scopeHelper;
        $this->productRepository = $productRepository;
        $this->outOfStockRepository = $outOfStockRepository;
        $this->logger = $logger;
        $this->publisher = $publisher;
        $this->oosQueueSchemaFactory = $oosQueueSchemaFactory;
        $this->searcherCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->sortOrder = $sortOrder;
        $this->outOfStockManagement = $outOfStockManagement;

        $this->init();
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init()
    {
        $this->setIsEnabled(true);
    }

    /**
     * Get isEnabled
     *
     * @return bool
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * Set isEnabled
     *
     * @param $isEnabled
     *
     * @return bool
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;
        return $this->isEnabled;
    }


    /**
     * Extend save()
     *
     * @param \Magento\CatalogInventory\Model\Stock\StockItemRepository $subject
     * @param \Magento\CatalogInventory\Api\Data\StockItemInterface $result
     *
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    public function afterSave(
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $subject,
        \Magento\CatalogInventory\Api\Data\StockItemInterface $result
    )
    {
        if (!$this->getIsEnabled()) {
            return $result;
        }

        $scope = \Riki\AdvancedInventory\Model\Queue\OosConsumer::class . '::execute';
        if ($this->scopeHelper->isInFunction($scope)) {
            return $result;
        }

        if (!$result->getIsInStock()) {
            return $result;
        }

        if ($result instanceof \Magento\CatalogInventory\Model\Stock\Item) {
            if ($result->getIsQtyDecrease()) { // only execute on increasing qty
                return $result;
            }
        }

        try {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->productRepository->getById($result->getProductId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->critical($e);
            return $result;
        }

        try {
            $stock = $this->stockRepository->getStockByProductId($result->getProductId());
            $stock = \Zend_Json::decode($stock);
            $qty = isset($stock['quantity_in_stock'])
                ? floatval($stock['quantity_in_stock'])
                : $result->getQty();
            if ($qty <= 0 && isset($stock['backorder_limit_in_stock'])) {
                $qty = floatval($stock['backorder_limit_in_stock']);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $result;
        }

        $bundleOosIds = $this->outOfStockManagement->getOutOfStockIdsByProductId($result->getProductId());

        $id = 0;
        $availableQty = $product->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE ? 0 : $qty;

        do {

            $outOfStocks = $this->getOosByProduct($product, $id, $availableQty, $bundleOosIds);

            /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
            foreach ($outOfStocks as $outOfStock) {

                $oosQty = $outOfStock->getQty();

                if ($outOfStock->getProductId() != $product->getId()) { // bundle item

                    $oosQty = 0;

                    $childrenQty = $outOfStock->getChildrenQty();

                    foreach ($childrenQty as $childQtyData) {

                        foreach ($childQtyData as $childProductId   =>  $childQty) {
                            if ($childProductId == $product->getId()) {
                                $oosQty += intval($childQty);
                            }
                        }
                    }
                }

                $id = $outOfStock->getId();

                if ($oosQty == 0 || ($availableQty && $qty < $oosQty)) {
                    return $result;
                }
                $outOfStockSchema = $this->oosQueueSchemaFactory->create();
                $outOfStockSchema->setOosModelId($outOfStock->getId());

                try {
                    $this->publisher->publish('oos.order.generate', $outOfStockSchema);
                    $outOfStock->setData('queue_execute', QueueExecuteInterface::WAITING);
                    $this->outOfStockRepository->save($outOfStock);
                    $qty -= $oosQty;
                    $this->logger->info('The oos entity #' . $outOfStock->getId() . ' was pushed into queue successfully.');
                }catch (\Exception $e){
                    $this->logger->critical($e);
                }
            }

        } while ($outOfStocks);

        return $result;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param $id
     * @param $availableQty
     * @param array $bundleOosIds
     * @return \Magento\Framework\Api\Search\DocumentInterface[]
     */
    protected function getOosByProduct(\Magento\Catalog\Model\Product $product, $id, $availableQty, $bundleOosIds = [])
    {
        $fieldsFilter = [
            [
                'field' =>  'entity_id',
                'conditionType' =>  'gt',
                'value' =>  $id,
            ],
            [
                'field' =>  'qty',
                'conditionType' =>  ($availableQty ? 'lteq' : 'gt'),
                'value' =>  $availableQty,
            ],
            [
                'field' =>  'generated_order_id',
                'conditionType' =>  'is',
                'value' =>  new \Zend_Db_Expr('NULL'),
            ],
            [
                'field' =>  'quote_item_id',
                'conditionType' =>  'is',
                'value' =>  new \Zend_Db_Expr('NOT NULL'),
            ],
            [
                'field' =>  'queue_execute',
                'conditionType' =>  'is',
                'value' =>  new \Zend_Db_Expr('NULL'),
            ]
        ];

        if (count($bundleOosIds)) {
            $fieldsFilter[] = [
                [
                    'field' =>  'product_id',
                    'conditionType' =>  'eq',
                    'value' =>  $product->getId(),
                ],
                [
                    'field' =>  'entity_id',
                    'conditionType' =>  'in',
                    'value' =>  $bundleOosIds,
                ]
            ];
        } else {
            $fieldsFilter[] = [
                'field' =>  'product_id',
                'conditionType' =>  'eq',
                'value' =>  $product->getId(),
            ];
        }

        $groupsFilter = [];

        foreach ($fieldsFilter as $fieldFilter) {
            if (isset($fieldFilter[0])) { // or where

                $filters = [];

                foreach ($fieldFilter as $owFieldFilter) {
                    $filters[] = $this->filterBuilder
                        ->setField($owFieldFilter['field'])
                        ->setConditionType($owFieldFilter['conditionType'])
                        ->setValue($owFieldFilter['value'])
                        ->create();
                }

                $groupsFilter[] = $this->filterGroupBuilder->setFilters($filters)->create();

            } else {
                $filter = $this->filterBuilder
                    ->setField($fieldFilter['field'])
                    ->setConditionType($fieldFilter['conditionType'])
                    ->setValue($fieldFilter['value'])
                    ->create();
                $groupsFilter[] = $this->filterGroupBuilder->addFilter($filter)->create();
            }
        }

        $sortOrder = $this->sortOrder->setField('entity_id')->setDirection(SortOrder::SORT_ASC);

        $searchCriteria = $this->searcherCriteriaBuilder
            ->setFilterGroups($groupsFilter)
            ->setPageSize(1000)
            ->setSortOrders([$sortOrder])
            ->create();

        return $this->outOfStockRepository->getList($searchCriteria)->getItems();
    }
}