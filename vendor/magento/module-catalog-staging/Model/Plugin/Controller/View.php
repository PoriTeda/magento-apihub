<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Plugin\Controller;

use Magento\Staging\Model\VersionManager;
use Magento\Framework\App\ResourceConnection;
use Magento\CatalogStaging\Model\Indexer\Category\Product\Preview;
use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Search\Request\IndexScopeResolverInterface as TableResolver;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Search\Request\Dimension;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class View
{
    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var Preview
     */
    protected $preview;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var TableResolver
     */
    private $tableResolver;

    /**
     * @param VersionManager $versionManager
     * @param ResourceConnection $resourceConnection
     * @param Preview $preview
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreManagerInterface|null $storeManager
     * @param TableResolver|null $tableResolver
     */
    public function __construct(
        VersionManager $versionManager,
        ResourceConnection $resourceConnection,
        Preview $preview,
        CategoryRepositoryInterface $categoryRepository,
        StoreManagerInterface $storeManager = null,
        TableResolver $tableResolver = null
    ) {
        $this->versionManager = $versionManager;
        $this->resourceConnection = $resourceConnection;
        $this->preview = $preview;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->tableResolver = $tableResolver ?: ObjectManager::getInstance()->get(TableResolver::class);
    }

    /**
     * @param \Magento\Catalog\Controller\Category\View $subject
     * @return void
     */
    public function beforeExecute(\Magento\Catalog\Controller\Category\View $subject)
    {
        if (!$this->versionManager->isPreviewVersion()) {
            return;
        }
        $categoryId = $subject->getRequest()->getParam('id');
        $storeId = $this->storeManager->getStore()->getId();

        /** @var \Magento\Catalog\Model\Category $category */
        $category = $this->categoryRepository->get($categoryId);
        if (!$category) {
            return;
        }

        $collection = $category->getProductCollection()->addCategoryFilter($category);
        $this->setJoinCondition($collection);
        $productIds = $collection->getAllIds();

        $this->preview->execute($categoryId, $productIds);

        $catalogCategoryProductDimension = new Dimension(\Magento\Store\Model\Store::ENTITY, $storeId);

        $indexTable = $this->tableResolver->resolve(
            AbstractAction::MAIN_INDEX_TABLE,
            [
                $catalogCategoryProductDimension
            ]
        );

        $indexTableTmp = $this->resourceConnection->getTableName($this->preview->getTemporaryTable($storeId));

        $mappedTable = $this->resourceConnection->getMappedTableName($indexTable);
        if ($mappedTable) {
            throw new \LogicException('Table ' . $indexTable . ' already mapped');
        }
        $this->resourceConnection->setMappedTableName($indexTable, $indexTableTmp);
    }

    /**
     * Catalog index does not contain disabled products which can be enabled in future update version
     *
     * @param \Magento\Framework\Data\Collection\AbstractDb $collection
     * @return void
     */
    protected function setJoinCondition($collection)
    {
        $fromPart = $collection->getSelect()->getPart(Select::FROM);
        if (isset($fromPart['cat_index']['joinType']) && $fromPart['cat_index']['joinType'] == Select::INNER_JOIN) {
            $fromPart['cat_index']['joinType'] = Select::LEFT_JOIN;
            $collection->getSelect()->setPart(Select::FROM, $fromPart);
        }
    }
}
