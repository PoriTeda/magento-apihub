<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Plugin\Helper\Product\Flat;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;

/**
 * Class FlatColumnsDefinitionTest
 */
class FlatColumnsDefinitionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataPoolMock;

    /**
     * @var EntityMetadataInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataMock;

    /**
     * @var \Magento\Catalog\Helper\Product\Flat\Indexer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $indexerMock;

    /**
     * @var \Magento\CatalogStaging\Plugin\Helper\Product\Flat\FlatColumnsDefinition
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
        $this->indexerMock = $this->getMockBuilder(\Magento\Catalog\Helper\Product\Flat\Indexer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->plugin = $objectManagerHelper->getObject(
            \Magento\CatalogStaging\Plugin\Helper\Product\Flat\FlatColumnsDefinition::class,
            [
                'metadataPool' => $this->metadataPoolMock
            ]
        );
    }

    public function testAfterGetFlatColumnsDdlDefinition()
    {
        $linkField = 'row_id';
        $this->metadataPoolMock->expects($this->once())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($this->metadataMock);
        $this->metadataMock->expects($this->once())->method('getLinkField')
            ->willReturn($linkField);
        $expected = [
            $linkField => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => null,
                'unsigned' => true,
                'nullable' => false,
                'default' => false,
                'comment' => 'Row Id',
            ]
        ];
        $this->assertEquals($expected, $this->plugin->afterGetFlatColumnsDdlDefinition($this->indexerMock, []));
    }
}
