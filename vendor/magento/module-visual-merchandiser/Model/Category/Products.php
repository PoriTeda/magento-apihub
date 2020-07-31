<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Category;

use \Magento\Framework\DB\Select;
use \Magento\Catalog\Model\Category\Product\PositionResolver;
use \Magento\Framework\App\ObjectManager;

class Products
{
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManager;

    /**
     * @var \Magento\VisualMerchandiser\Model\Position\Cache
     */
    protected $_cache;

    /**
     * @var string
     */
    protected $_cacheKey;

    /**
     * @var \Magento\VisualMerchandiser\Model\Sorting
     */
    protected $sorting;

    /**
     * @var PositionResolver
     */
    private $positionResolver;

    /**
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\VisualMerchandiser\Model\Position\Cache $cache
     * @param \Magento\VisualMerchandiser\Model\Sorting $sorting
     * @param PositionResolver|null $positionResolver
     */
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\VisualMerchandiser\Model\Position\Cache $cache,
        \Magento\VisualMerchandiser\Model\Sorting $sorting,
        PositionResolver $positionResolver = null
    ) {
        $this->_productFactory = $productFactory;
        $this->_moduleManager = $moduleManager;
        $this->_cache = $cache;
        $this->sorting = $sorting;
        $this->positionResolver = $positionResolver ?: ObjectManager::getInstance()->get(PositionResolver::class);
    }

    /**
     * @param string $key
     * @return void
     */
    public function setCacheKey($key)
    {
        $this->_cacheKey = $key;
    }

    /**
     * @return \Magento\Catalog\Model\ProductFactory
     */
    public function getFactory()
    {
        return $this->_productFactory;
    }

    /**
     * @param int $categoryId
     * @param int $store
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getCollectionForGrid($categoryId, $store = null)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->getFactory()->create()
            ->getCollection()
            ->addAttributeToSelect([
                'sku',
                'name',
                'price',
                'small_image'
            ]);

        $collection->getSelect()
            ->where('at_position.category_id = ?', $categoryId);

        if ($this->_moduleManager->isEnabled('Magento_CatalogInventory')) {
            $collection->joinField(
                'stock',
                'cataloginventory_stock_item',
                'qty',
                'product_id=entity_id',
                ['stock_id' => $this->getStockId()],
                'left'
            );
        }

        $cache = $this->_cache->getPositions($this->_cacheKey);

        if ($cache === false) {
            $collection->joinField(
                'position',
                'catalog_category_product',
                'position',
                'product_id=entity_id',
                null,
                'left'
            );
            $collection->setOrder('position', $collection::SORT_ORDER_ASC);

            $positions = $this->positionResolver->getPositions($categoryId);

            $this->_cache->saveData($this->_cacheKey, $positions);
        } else {
            $collection->getSelect()
                ->reset(Select::WHERE)
                ->reset(Select::HAVING);

            $collection->addAttributeToFilter('entity_id', ['in' => array_keys($cache)]);
        }

        $collection = $this->applyCachedChanges($collection);

        if ($store !== null) {
            $collection->addStoreFilter($store);
        }

        return $collection;
    }

    /**
     * @return int
     */
    protected function getStockId()
    {
        return \Magento\CatalogInventory\Model\Stock::DEFAULT_STOCK_ID;
    }

    /**
     * Save positions from collection to cache
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return void
     */
    public function savePositions(\Magento\Catalog\Model\ResourceModel\Product\Collection $collection)
    {
        if (!$collection->isLoaded()) {
            $collection->load();
        }
        $select = clone $collection->getSelect();

        $select->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $this->prependColumn($select, $collection->getEntity()->getIdFieldName());

        $positions = array_flip($collection->getConnection()->fetchCol($select));

        $this->savePositionsToCache($positions);
    }

    /**
     * Add needed column to the Select on the first position
     *
     * There are no problems for MySQL with several same columns in the result set
     *
     * @param \Magento\Framework\DB\Select $select
     * @param string $columnName
     * @return void
     */
    private function prependColumn(\Magento\Framework\DB\Select $select, string $columnName)
    {
        $columns = $select->getPart(\Magento\Framework\DB\Select::COLUMNS);
        array_unshift($columns, ['e', $columnName, null]);
        $select->setPart(\Magento\Framework\DB\Select::COLUMNS, $columns);
    }

    /**
     * Apply cached positions, sort order products
     * returns a base collection with WHERE IN filter applied
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function applyCachedChanges(\Magento\Catalog\Model\ResourceModel\Product\Collection $collection)
    {
        $positions = $this->_cache->getPositions($this->_cacheKey);

        if (empty($positions)) {
            return $collection;
        }

        $collection->getSelect()->reset(Select::ORDER);
        asort($positions, SORT_NUMERIC);

        $ids = implode(',', array_keys($positions));
        $select = $collection->getSelect();
        $field = $select->getAdapter()->quoteIdentifier('e.entity_id');
        $orderExpression = new \Zend_Db_Expr("FIELD({$field}, {$ids})");
        $select->order($orderExpression);

        $sortOrder = $this->_cache->getSortOrder($this->_cacheKey);
        $sortBuilder = $this->sorting->getSortingInstance($sortOrder);

        $sortedCollection = $sortBuilder->sort($collection);

        return $sortedCollection;
    }

    /**
     * Save products positions to cache
     *
     * @param array $positions
     * @return void
     */
    protected function savePositionsToCache($positions)
    {
        $this->_cache->saveData(
            $this->_cacheKey,
            $positions
        );
    }
}
