<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Plugin\Model\Indexer\Product\Flat\Table;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Indexer\Product\Flat\Table\BuilderInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;

/**
 * Class BuilderTest
 */
class BuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataPoolMock;

    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionMock;

    /**
     * @var EntityMetadataInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataMock;

    /**
     * @var BuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $builderMock;

    /**
     * @var \Magento\Framework\DB\Ddl\Table|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tableMock;

    /**
     * @var \Magento\CatalogStaging\Plugin\Model\Indexer\Product\Flat\Table\Builder
     */
    private $plugin;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->metadataPoolMock = $this->getMockBuilder(\Magento\Framework\EntityManager\MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->builderMock = $this->getMockBuilder(BuilderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConnectionMock = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->getMockForAbstractClass();
        $this->tableMock = $this->getMockBuilder(\Magento\Framework\DB\Ddl\Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->plugin = $objectManagerHelper->getObject(
            \Magento\CatalogStaging\Plugin\Model\Indexer\Product\Flat\Table\Builder::class,
            [
                'metadataPool' => $this->metadataPoolMock,
                'resourceConnection' => $this->resourceConnectionMock
            ]
        );
    }

    public function testAfterGetTable()
    {
        $linkField = 'row_id';
        $connectionName = 'not-default';
        $tableName = 'tmp_catalog_product_indexer';
        $indexName = 'ix_catalog_product_row_id';
        $this->tableMock->expects($this->once())
            ->method('getName')
            ->willReturn($tableName);
        $this->metadataPoolMock->expects($this->once())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($this->metadataMock);
        $this->metadataMock->expects($this->once())
            ->method('getLinkField')
            ->willReturn($linkField);
        $this->metadataMock->expects($this->once())
            ->method('getEntityConnectionName')
            ->willReturn($connectionName);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getConnectionByName')
            ->with($connectionName)
            ->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->once())
            ->method('getIndexName')
            ->with($tableName, [$linkField], '')
            ->willReturn($indexName);
        $this->tableMock->expects($this->once())->method('addColumn')
            ->with($linkField, \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER)
            ->willReturnSelf();
        $this->tableMock->expects($this->once())->method('addIndex')
            ->with($indexName, [$linkField], [])
            ->willReturnSelf();
        $this->assertEquals($this->tableMock, $this->plugin->afterGetTable($this->builderMock, $this->tableMock));
    }
}
