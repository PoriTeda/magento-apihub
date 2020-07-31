<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin;

class ProductTest extends \PHPUnit\Framework\TestCase
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
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product
     */
    protected $subjectMock;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexerRegistryMock;

    /**
     * @var Product
     */
    protected $model;

    protected function setUp()
    {
        $this->subjectMock = $this->createMock(\Magento\Catalog\Model\Product::class);

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

        $this->model = new \Magento\CatalogPermissions\Model\Indexer\Plugin\Product(
            $this->indexerRegistryMock,
            $this->configMock
        );
    }

    public function testAfterSaveNonScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->will($this->returnValue(false));
        $this->indexerMock->expects($this->once())->method('reindexList')->with([1]);
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)
            ->will($this->returnValue($this->indexerMock));

        $this->subjectMock->expects($this->once())->method('getId')->will($this->returnValue(1));

        $this->assertEquals($this->subjectMock, $this->model->afterSave($this->subjectMock));
    }

    public function testAfterSaveScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->will($this->returnValue(true));
        $this->indexerMock->expects($this->never())->method('reindexList');
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)
            ->will($this->returnValue($this->indexerMock));

        $this->subjectMock->expects($this->once())->method('getId')->will($this->returnValue(1));

        $this->assertEquals($this->subjectMock, $this->model->afterSave($this->subjectMock));
    }

    public function testAfterDeleteNonScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->will($this->returnValue(false));
        $this->indexerMock->expects($this->once())->method('reindexList')->with([1]);
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)
            ->will($this->returnValue($this->indexerMock));

        $this->subjectMock->expects($this->once())->method('getId')->will($this->returnValue(1));

        $this->assertEquals($this->subjectMock, $this->model->afterDelete($this->subjectMock));
    }

    public function testAfterDeleteScheduled()
    {
        $this->indexerMock->expects($this->once())->method('isScheduled')->will($this->returnValue(true));
        $this->indexerMock->expects($this->never())->method('reindexList');
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)
            ->will($this->returnValue($this->indexerMock));

        $this->subjectMock->expects($this->once())->method('getId')->will($this->returnValue(1));

        $this->assertEquals($this->subjectMock, $this->model->afterDelete($this->subjectMock));
    }
}
