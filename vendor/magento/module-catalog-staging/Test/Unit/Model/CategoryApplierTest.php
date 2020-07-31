<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Indexer\Category\Flat\State;
use Magento\CatalogStaging\Model\CategoryApplier;

class CategoryApplierTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var State|\PHPUnit_Framework_MockObject_MockObject
     */
    private $flatState;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $indexerRegistry;

    /**
     * @var \Magento\Framework\Indexer\CacheContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheContext;

    /**
     * @var CategoryApplier
     */
    private $model;

    protected function setUp()
    {
        $this->flatState = $this->getMockBuilder(\Magento\Catalog\Model\Indexer\Category\Flat\State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexerRegistry = $this->getMockBuilder(\Magento\Framework\Indexer\IndexerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheContext = $this->getMockBuilder(\Magento\Framework\Indexer\CacheContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new CategoryApplier(
            $this->flatState,
            $this->indexerRegistry,
            $this->cacheContext
        );
    }

    public function testExecuteWithFlatEnabled()
    {
        $entityIds = [1];

        $this->flatState->expects($this->once())
            ->method('isFlatEnabled')
            ->willReturn(true);

        $indexerMock = $this->getMockBuilder(\Magento\Framework\Indexer\IndexerInterface::class)
            ->getMockForAbstractClass();
        $indexerMock->expects($this->once())
            ->method('reindexList')
            ->with($entityIds)
            ->willReturnSelf();

        $this->indexerRegistry->expects($this->once())
            ->method('get')
            ->with(State::INDEXER_ID)
            ->willReturn($indexerMock);

        $this->cacheContext->expects($this->once())
            ->method('registerEntities')
            ->with(Category::CACHE_TAG, $entityIds)
            ->willReturnSelf();

        $this->model->execute($entityIds);
    }

    public function testExecuteWithFlatDisabled()
    {
        $entityIds = [1];

        $this->flatState->expects($this->once())
            ->method('isFlatEnabled')
            ->willReturn(false);

        $this->cacheContext->expects($this->once())
            ->method('registerEntities')
            ->with(Category::CACHE_TAG, $entityIds)
            ->willReturnSelf();

        $this->model->execute($entityIds);
    }

    public function testExecuteWithNoEntities()
    {
        $result = $this->model->execute([]);
        $this->assertNull($result);
    }
}
