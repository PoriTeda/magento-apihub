<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Indexer\Category\Product;

use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Query\Generator as QueryGenerator;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;
use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Search\Request\IndexScopeResolverInterface as TableResolver;

class Preview extends AbstractAction
{
    /**
     * @var int|null
     */
    protected $categoryId;

    /**
     * @var array
     */
    protected $productIds = [];

    /**
     * Prefix for temporary table name
     */
    const TMP_PREFIX = '_catalog_staging_tmp';

    /**
     * @var TableResolver
     */
    private $tableResolver;

    /**
     * @param ResourceConnection $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Config $config
     * @param QueryGenerator $queryGenerator
     * @param MetadataPool|null $metadataPool
     * @param TableMaintainer|null $tableMaintainer
     * @param TableResolver|null $tableResolver
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Config $config,
        QueryGenerator $queryGenerator = null,
        MetadataPool $metadataPool = null,
        TableMaintainer $tableMaintainer = null,
        TableResolver $tableResolver = null
    ) {
        parent::__construct($resource, $storeManager, $config, $queryGenerator, $metadataPool, $tableMaintainer);
        $this->tableResolver = $tableResolver ?: ObjectManager::getInstance()->get(TableResolver::class);
    }

    /**
     * @param null $categoryId
     * @param array $productIds
     * @return void
     */
    public function execute($categoryId = null, array $productIds = [])
    {
        $this->categoryId = $categoryId;
        $this->productIds = $productIds;
        $this->prepareTemporaryStorage();
        $this->reindex();
    }

    /**
     * @param int $storeId
     *
     * @return string
     */
    public function getTemporaryTable($storeId)
    {
        $catalogCategoryProductDimension = new Dimension(\Magento\Store\Model\Store::ENTITY, $storeId);

        $indexTable = $this->tableResolver->resolve(
            AbstractAction::MAIN_INDEX_TABLE,
            [
                $catalogCategoryProductDimension
            ]
        );

        return $indexTable . static::TMP_PREFIX;
    }

    /**
     * @return void
     */
    protected function prepareTemporaryStorage()
    {
        foreach ($this->storeManager->getStores() as $store) {
            $catalogCategoryProductDimension = new Dimension(\Magento\Store\Model\Store::ENTITY, $store->getId());

            $indexTable = $this->tableResolver->resolve(
                AbstractAction::MAIN_INDEX_TABLE,
                [
                    $catalogCategoryProductDimension
                ]
            );

            $this->resource->getConnection()->createTemporaryTableLike(
                $this->resource->getTableName($this->getTemporaryTable($store->getId())),
                $indexTable
            );
        }
    }

    /**
     * Return index table name
     *
     * @param int $storeId
     * @return string
     */
    protected function getIndexTable($storeId)
    {
        return $this->getTemporaryTable($storeId);
    }

    /**
     * @param \Magento\Store\Model\Store $store
     * @return \Magento\Framework\DB\Select
     */
    protected function getAllProducts(\Magento\Store\Model\Store $store)
    {
        $allProductsSelect = parent::getAllProducts($store);
        $allProductsSelect->where('cp.entity_id IN (?)', $this->productIds);
        return $allProductsSelect;
    }

    /**
     * @param \Magento\Store\Model\Store $store
     * @return \Magento\Framework\DB\Select
     */
    protected function createAnchorSelect(\Magento\Store\Model\Store $store)
    {
        $anchorSelect = parent::createAnchorSelect($store);
        $anchorSelect->where('cpe.entity_id IN (?)', $this->productIds);
        $anchorSelect->where('cc.entity_id IN (?)', $this->categoryId);
        return $anchorSelect;
    }

    /**
     * @param \Magento\Store\Model\Store $store
     * @return \Magento\Framework\DB\Select
     */
    protected function getNonAnchorCategoriesSelect(\Magento\Store\Model\Store $store)
    {
        $nonAnchorSelect = parent::getNonAnchorCategoriesSelect($store);
        $nonAnchorSelect->where('cpe.entity_id IN (?)', $this->productIds);
        $nonAnchorSelect->where('cc.entity_id IN (?)', $this->categoryId);
        return $nonAnchorSelect;
    }
}
