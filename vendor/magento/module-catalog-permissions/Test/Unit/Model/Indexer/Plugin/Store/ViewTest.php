<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin\Store;

class ViewTest extends \PHPUnit\Framework\TestCase
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
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\ResourceModel\Store
     */
    protected $subjectMock;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexerRegistryMock;

    /**
     * @var \Magento\CatalogPermissions\Model\Indexer\Plugin\Store\View
     */
    protected $model;

    protected function setUp()
    {
        $this->subjectMock = $this->createMock(\Magento\Store\Model\ResourceModel\Store::class);

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
        $this->model = new \Magento\CatalogPermissions\Model\Indexer\Plugin\Store\View(
            $this->indexerRegistryMock,
            $this->configMock
        );
    }

    public function testAfterSaveNewObject()
    {
        $this->mockIndexerMethods();
        $storeMock = $this->createPartialMock(
            \Magento\Store\Model\Store::class,
            ['isObjectNew', 'dataHasChangedFor', '__wakeup']
        );
        $storeMock->expects($this->once())->method('isObjectNew')->will($this->returnValue(true));

        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterSave($this->subjectMock, $this->subjectMock, $storeMock)
        );
    }

    public function testAfterSaveHasChanged()
    {
        $storeMock = $this->createPartialMock(
            \Magento\Store\Model\Store::class,
            ['isObjectNew', 'dataHasChangedFor', '__wakeup']
        );

        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterSave($this->subjectMock, $this->subjectMock, $storeMock)
        );
    }

    public function testAfterSaveNoNeed()
    {
        $storeMock = $this->createPartialMock(
            \Magento\Store\Model\Store::class,
            ['isObjectNew', 'dataHasChangedFor', '__wakeup']
        );

        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterSave($this->subjectMock, $this->subjectMock, $storeMock)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Indexer\Model\Indexer\State
     */
    protected function getStateMock()
    {
        $stateMock = $this->createPartialMock(
            \Magento\Indexer\Model\Indexer\State::class,
            ['setStatus', 'save', '__wakeup']
        );
        $stateMock->expects($this->once())->method('setStatus')->with('invalid')->will($this->returnSelf());
        $stateMock->expects($this->once())->method('save')->will($this->returnSelf());

        return $stateMock;
    }

    protected function mockIndexerMethods()
    {
        $this->indexerMock->expects($this->exactly(2))->method('invalidate');
        $this->indexerRegistryMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$this->equalTo(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)],
                [$this->equalTo(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)]
            )
            ->willReturn($this->indexerMock);
    }
}
