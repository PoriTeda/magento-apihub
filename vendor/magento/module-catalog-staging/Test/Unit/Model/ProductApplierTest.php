<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Model;

use Magento\CatalogStaging\Model\ProductApplier;

class ProductApplierTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productCollection;

    /**
     * @var \Magento\CatalogStaging\Helper\ReindexPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $reindexPool;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $indexerRegistry;

    /**
     * @var \Magento\Framework\Indexer\CacheContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheContext;

    /**
     * @var ProductApplier
     */
    private $model;

    protected function setUp()
    {
        $this->productCollection = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->reindexPool = $this->getMockBuilder(\Magento\CatalogStaging\Helper\ReindexPool::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexerRegistry = $this->getMockBuilder(\Magento\Framework\Indexer\IndexerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheContext = $this->getMockBuilder(\Magento\Framework\Indexer\CacheContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new ProductApplier(
            $this->productCollection,
            $this->reindexPool,
            $this->indexerRegistry,
            $this->cacheContext
        );
    }

    public function testExecute()
    {
        $entityIds = [1];
        $affectedCategories = [2];

        $this->reindexPool->expects($this->once())
            ->method('reindexList')
            ->with($entityIds)
            ->willReturnSelf();

        $selectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $adapterMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->setMethods(['fetchCol'])
            ->getMockForAbstractClass();

        $selectMock->expects($this->once())
            ->method('reset')
            ->willReturnSelf();
        $selectMock->expects($this->once())
            ->method('distinct')
            ->with(true)
            ->willReturnSelf();
        $selectMock->expects($this->once())
            ->method('from')
            ->with('catalog_category_product', ['category_id'])
            ->willReturnSelf();
        $selectMock->expects($this->once())
            ->method('where')
            ->with('product_id IN (?)', $entityIds)
            ->willReturnSelf();

        $adapterMock->expects($this->once())
            ->method('fetchCol')
            ->with($selectMock)
            ->willReturn($affectedCategories);

        $this->productCollection->expects($this->once())
            ->method('getSelect')
            ->willReturn($selectMock);
        $this->productCollection->expects($this->once())
            ->method('getTable')
            ->with('catalog_category_product')
            ->willReturn('catalog_category_product');
        $this->productCollection->expects($this->once())
            ->method('getConnection')
            ->willReturn($adapterMock);

        $this->cacheContext->expects($this->exactly(2))
            ->method('registerEntities')
            ->willReturnMap([
                [\Magento\Catalog\Model\Category::CACHE_TAG, $affectedCategories, $this->cacheContext],
                [\Magento\Catalog\Model\Product::CACHE_TAG, $entityIds, $this->cacheContext],
            ]);

        $this->model->execute($entityIds);
    }

    public function testExecuteWithNoEntityIds()
    {
        $result = $this->model->execute([]);
        $this->assertNull($result);
    }
}
