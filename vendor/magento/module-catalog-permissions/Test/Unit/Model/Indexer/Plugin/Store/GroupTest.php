<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin\Store;

class GroupTest extends \PHPUnit\Framework\TestCase
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
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\ResourceModel\Group
     */
    protected $subjectMock;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexerRegistryMock;

    /**
     * @var \Magento\CatalogPermissions\Model\Indexer\Plugin\Store\Group
     */
    protected $model;

    protected function setUp()
    {
        $this->subjectMock = $this->createMock(\Magento\Store\Model\ResourceModel\Group::class);
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
        $this->model = new \Magento\CatalogPermissions\Model\Indexer\Plugin\Store\Group(
            $this->indexerRegistryMock,
            $this->configMock
        );
    }

    /**
     * @param array $valueMap
     * @dataProvider changedDataProvider
     */
    public function testAfterSave($valueMap)
    {
        $this->mockIndexerMethods();
        $groupMock = $this->createPartialMock(
            \Magento\Store\Model\Group::class,
            ['dataHasChangedFor', 'isObjectNew', '__wakeup']
        );
        $groupMock->expects($this->exactly(2))->method('dataHasChangedFor')->will($this->returnValueMap($valueMap));
        $groupMock->expects($this->once())->method('isObjectNew')->will($this->returnValue(false));

        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterSave($this->subjectMock, $this->subjectMock, $groupMock)
        );
    }

    /**
     * @param array $valueMap
     * @dataProvider changedDataProvider
     */
    public function testAfterSaveNotNew($valueMap)
    {
        $groupMock = $this->createPartialMock(
            \Magento\Store\Model\Group::class,
            ['dataHasChangedFor', 'isObjectNew', '__wakeup']
        );
        $groupMock->expects($this->exactly(2))->method('dataHasChangedFor')->will($this->returnValueMap($valueMap));
        $groupMock->expects($this->once())->method('isObjectNew')->will($this->returnValue(true));
        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterSave($this->subjectMock, $this->subjectMock, $groupMock)
        );
    }

    public function changedDataProvider()
    {
        return [
            [
                [['root_category_id', true], ['website_id', false]],
                [['root_category_id', false], ['website_id', true]],
            ]
        ];
    }

    public function testAfterSaveWithoutChanges()
    {
        $groupMock = $this->createPartialMock(
            \Magento\Store\Model\Group::class,
            ['dataHasChangedFor', 'isObjectNew', '__wakeup']
        );
        $groupMock->expects(
            $this->exactly(2)
        )->method(
            'dataHasChangedFor'
        )->will(
            $this->returnValueMap([['root_category_id', false], ['website_id', false]])
        );
        $groupMock->expects($this->never())->method('isObjectNew');

        $this->assertEquals(
            $this->subjectMock,
            $this->model->afterSave($this->subjectMock, $this->subjectMock, $groupMock)
        );
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
            ->will($this->returnValue($this->indexerMock));
    }
}
