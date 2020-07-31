<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Category\Action;

use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\CacheInterface;

/**
 * @api
 * @since 100.0.2
 */
class Rows extends \Magento\CatalogPermissions\Model\Indexer\AbstractAction
{
    /**
     * Limitation by categories
     *
     * @var int[]
     */
    protected $entityIds;

    /**
     * Affected product IDs
     *
     * @var int[]
     */
    protected $productIds;

    /**
     * @var \Magento\CatalogPermissions\Helper\Index
     */
    protected $helper;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param WebsiteCollectionFactory $websiteCollectionFactory
     * @param GroupCollectionFactory $groupCollectionFactory
     * @param ConfigInterface $config
     * @param StoreManagerInterface $storeManager
     * @param CatalogConfig $catalogConfig
     * @param CacheInterface $coreCache
     * @param \Magento\CatalogPermissions\Helper\Index $helper
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        WebsiteCollectionFactory $websiteCollectionFactory,
        GroupCollectionFactory $groupCollectionFactory,
        ConfigInterface $config,
        StoreManagerInterface $storeManager,
        CatalogConfig $catalogConfig,
        CacheInterface $coreCache,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\CatalogPermissions\Helper\Index $helper
    ) {
        parent::__construct(
            $resource,
            $websiteCollectionFactory,
            $groupCollectionFactory,
            $config,
            $storeManager,
            $catalogConfig,
            $coreCache,
            $metadataPool
        );
        $this->helper = $helper;
    }

    /**
     * Refresh entities index
     *
     * @param int[] $entityIds
     * @param bool $useIndexTempTable
     * @return void
     */
    public function execute(array $entityIds = [], $useIndexTempTable = false)
    {
        if ($entityIds) {
            $this->entityIds = $entityIds;
            $this->useIndexTempTable = $useIndexTempTable;

            $this->removeObsoleteIndexData();

            $this->reindex();
            $this->cleanCache();
        }
    }

    /**
     * Remove index entries before reindexation
     *
     * @return void
     */
    protected function removeObsoleteIndexData()
    {
        $this->connection->delete(
            $this->getIndexTempTable(),
            ['category_id IN (?)' => array_merge($this->entityIds, $this->helper->getChildCategories($this->entityIds))]
        );
        $this->connection->delete(
            $this->getProductIndexTempTable(),
            ['product_id IN (?)' => $this->getProductList()]
        );
    }

    /**
     * Retrieve category list
     *
     * Return entity_id, path pairs.
     *
     * @return array
     */
    protected function getCategoryList()
    {
        return $this->helper->getCategoryList($this->entityIds);
    }

    /**
     * Check whether select ranging is needed
     *
     * @return bool
     */
    protected function isRangingNeeded()
    {
        return false;
    }

    /**
     * Return list of product IDs to reindex
     *
     * @return int[]
     */
    protected function getProductList()
    {
        if ($this->productIds === null) {
            $this->productIds = $this->helper->getProductList($this->entityIds);
        }
        return $this->productIds;
    }
}
