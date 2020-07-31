<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCatalog\Test\Unit\Model\ResourceModel\Product\Indexer\Eav;

/**
 * Class SourceTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SourceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedCatalog\Model\ResourceModel\Product\Indexer\Eav\Source
     */
    protected $_source;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $selectMock;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Eav\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eavConfigMock;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $metadataPool;

    /**
     * @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManagerMock;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Helper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $helperMock;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeMock;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backendAttributeMock;

    /**
     * @var \Magento\Framework\Indexer\Table\StrategyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tableStrategyMock;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->selectMock->expects($this->any())->method('from')->will($this->returnSelf());
        $this->selectMock->expects($this->any())->method('join')->will($this->returnSelf());
        $this->selectMock->expects($this->any())->method('joinLeft')->will($this->returnSelf());
        $this->selectMock->expects($this->any())->method('group')->will($this->returnSelf());
        $this->selectMock->expects($this->any())->method('where')->will($this->returnSelf());
        $this->selectMock->expects($this->any())->method('columns')->will($this->returnSelf());

        $this->connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class);
        $this->connectionMock->expects($this->any())->method('select')->willReturn($this->selectMock);
        $this->connectionMock->expects($this->any())->method('describeTable')->willReturn(['column1', 'column2']);

        $this->resourceMock = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $this->resourceMock->expects($this->any())->method('getConnection')->willReturn($this->connectionMock);

        $this->contextMock = $this->createMock(\Magento\Framework\Model\ResourceModel\Db\Context::class);
        $this->contextMock->expects($this->once())->method('getResources')->willReturn($this->resourceMock);

        $this->attributeMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class);
        $this->backendAttributeMock = $this->createMock(
            \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend::class
        );
        $this->attributeMock->expects($this->any())->method('getBackend')
            ->will($this->returnValue($this->backendAttributeMock));

        $this->eavConfigMock = $this->createMock(\Magento\Eav\Model\Config::class);
        $this->eavConfigMock->expects($this->any())->method('getAttribute')->will(
            $this->returnValue($this->attributeMock)
        );

        $metadata = $this->createMock(\Magento\Framework\EntityManager\EntityMetadata::class);

        $this->metadataPool = $this->createMock(\Magento\Framework\EntityManager\MetadataPool::class);

        $this->metadataPool->expects($this->any())
            ->method('getMetadata')
            ->with(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->willReturn($metadata);

        $this->eventManagerMock = $this->getMockForAbstractClass(
            \Magento\Framework\Event\ManagerInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['dispatch']
        );

        $this->helperMock = $this->createMock(\Magento\Catalog\Model\ResourceModel\Helper::class);

        $connectionName = 'index';
        $this->tableStrategyMock = $this->createMock(\Magento\Framework\Indexer\Table\StrategyInterface::class);

        $this->tableStrategyMock->expects($this->any())->method('getTableName')->will($this->returnArgument(0));
        $this->_source = new \Magento\AdvancedCatalog\Model\ResourceModel\Product\Indexer\Eav\Source(
            $this->contextMock,
            $this->tableStrategyMock,
            $this->eavConfigMock,
            $this->eventManagerMock,
            $this->helperMock,
            $connectionName
        );
        $reflection = new \ReflectionClass(get_class($this->_source));
        $reflectionProperty = $reflection->getProperty('metadataPool');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->_source, $this->metadataPool);
    }

    /**
     * Test prepare relation index with using idx table
     *
     * @return void
     */
    public function testPrepareRelationIndexUseIdxTable()
    {
        $this->tableStrategyMock->expects($this->any())->method('getUseIdxTable')->willReturn(true);
        $this->eventManagerMock->expects($this->once())->method('dispatch')->with(
            'prepare_catalog_product_index_select'
        );
        $this->connectionMock->expects($this->never())->method('createTemporaryTableLike');
        $this->connectionMock->expects($this->never())->method('dropTemporaryTable');
        $this->assertInstanceOf(
            \Magento\AdvancedCatalog\Model\ResourceModel\Product\Indexer\Eav\Source::class,
            $this->_source->reindexAll()
        );
    }

    /**
     * Test prepare relation index without using idx table
     *
     * @return void
     */
    public function testPrepareRelationIndexNotUseIdxTable()
    {
        $this->tableStrategyMock->expects($this->any())->method('getUseIdxTable')->willReturn(false);
        $this->eventManagerMock->expects($this->once())->method('dispatch')->with(
            'prepare_catalog_product_index_select'
        );
        $this->connectionMock->expects($this->atLeastOnce())->method('createTemporaryTableLike');
        $this->connectionMock->expects($this->once())->method('dropTemporaryTable');
        $this->assertInstanceOf(
            \Magento\AdvancedCatalog\Model\ResourceModel\Product\Indexer\Eav\Source::class,
            $this->_source->reindexEntities([1])
        );
    }
}
