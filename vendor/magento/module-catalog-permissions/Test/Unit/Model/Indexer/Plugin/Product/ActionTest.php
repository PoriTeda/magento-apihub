<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin\Product;

class ActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Indexer\IndexerInterface
     */
    protected $indexerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\CatalogPermissions\App\ConfigInterface
     */
    protected $configMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product\Action
     */
    protected $subjectMock;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexerRegistryMock;

    /**
     * @var \Magento\CatalogPermissions\Model\Indexer\Plugin\Product\Action
     */
    protected $model;

    protected function setUp()
    {
        $this->subjectMock = $this->createMock(\Magento\Catalog\Model\Product\Action::class);

        $this->indexerMock = $this->getMockForAbstractClass(
            \Magento\Framework\Indexer\IndexerInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getId', 'getState', '__wakeup']
        );

        $this->configMock = $this->getMockForAbstractClass(
            \Magento\CatalogPermissions\App\ConfigInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['isEnabled']
        );
        $this->configMock->expects($this->any())->method('isEnabled')->will($this->returnValue(true));

        $this->indexerRegistryMock = $this->createPartialMock(
            \Magento\Framework\Indexer\IndexerRegistry::class,
            ['get']
        );

        $this->model = new \Magento\CatalogPermissions\Model\Indexer\Plugin\Product\Action(
            $this->indexerRegistryMock,
            $this->configMock
        );
    }

    public function testAfterUpdateAttributesNonScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->willReturn(false);
        $this->indexerMock->expects($this->once())->method('reindexList')->with([1, 2, 3]);
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterUpdateAttributes($this->subjectMock, $this->subjectMock, [1, 2, 3], [4, 5, 6], 1)
        );
    }

    public function testAfterUpdateAttributesScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->willReturn(true);
        $this->indexerMock->expects($this->never())->method('reindexList');
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterUpdateAttributes($this->subjectMock, $this->subjectMock, [1, 2, 3], [4, 5, 6], 1)
        );
    }

    public function testAfterUpdateWebsitesNonScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->willReturn(false);
        $this->indexerMock->expects($this->once())->method('reindexList')->with([1, 2, 3]);
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->model->afterUpdateWebsites($this->subjectMock, null, [1, 2, 3], [4, 5, 6], 'type');
    }

    public function testAfterUpdateWebsitesScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->willReturn(true);
        $this->indexerMock->expects($this->never())->method('reindexList');
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->model->afterUpdateWebsites($this->subjectMock, null, [1, 2, 3], [4, 5, 6], 'type');
    }
}
