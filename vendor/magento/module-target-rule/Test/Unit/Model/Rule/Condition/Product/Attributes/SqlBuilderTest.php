<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model\Rule\Condition\Product\Attributes;

use Magento\TargetRule\Model\Rule\Condition\Product\Attributes;
use Magento\TargetRule\Model\Rule\Condition\Product\Attributes\SqlBuilder;
use Magento\Store\Model\Store;

class SqlBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SqlBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sqlBuilder;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataPoolMock;

    /**
     * @var \Magento\Framework\EntityManager\EntityMetadataInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityMetadataMock;

    /**
     * @var \Magento\TargetRule\Model\ResourceModel\Index|\PHPUnit_Framework_MockObject_MockObject
     */
    private $indexResourceMock;

    /**
     * @var \Magento\Framework\DB\Select|\PHPUnit_Framework_MockObject_MockObject
     */
    private $selectMock;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionMock;

    /**
     * @var Attributes|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributesMock;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eavAttributeMock;

    public function setUp()
    {
        $this->indexResourceMock = $this->createPartialMock(
            \Magento\TargetRule\Model\ResourceModel\Index::class,
            [
                'getTable',
                'bindArrayOfIds',
                'getOperatorCondition',
                'getOperatorBindCondition',
                'getResource',
                'select',
                'getConnection',
                'getStoreId'
            ]
        );
        $this->connectionMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIfNullSql', 'getCheckSql'])
            ->getMockForAbstractClass();

        $this->selectMock = $this->createPartialMock(
            \Magento\Framework\DB\Select::class,
            ['from', 'assemble', 'where', 'joinLeft', 'joinInner', 'union']
        );
        $this->metadataPoolMock = $this->createPartialMock(
            \Magento\Framework\EntityManager\MetadataPool::class,
            ['getMetadata']
        );
        $this->entityMetadataMock = $this->getMockBuilder(
            \Magento\Framework\EntityManager\EntityMetadataInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getLinkField'])
            ->getMockForAbstractClass();
        $this->eavAttributeMock = $this->createPartialMock(
            \Magento\Catalog\Model\ResourceModel\Eav\Attribute::class,
            ['isScopeGlobal', 'isStatic', 'getBackendTable', 'getId']
        );
        $this->attributesMock = $this->createPartialMock(
            Attributes::class,
            ['getAttributeObject']
        );

        $this->sqlBuilder = new SqlBuilder($this->metadataPoolMock, $this->indexResourceMock);
    }

    public function testGenerateWhereClauseForGlobalScopeAttribute()
    {
        $attributeId = 42;
        $attributesValue = 3;
        $attributesOperator = '{}';
        $attributeTable = 'catalog_product_entity_varchar';
        $relationTable = 'catalog_product_relation';
        $this->attributesMock->setOperator($attributesOperator);
        $this->attributesMock->setAttribute('filter');
        $this->attributesMock->setValue($attributesValue);
        $bind = [];
        $linkField = 'row_id';

        $this->indexResourceMock
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->indexResourceMock->expects($this->once())
            ->method('getTable')
            ->with($relationTable)
            ->willReturn($relationTable);
        $this->indexResourceMock
            ->method('getOperatorCondition')
            ->with('table.value', $attributesOperator, $attributesValue)
            ->willReturn('table.value=' . $attributesValue);
        $this->connectionMock->expects($this->atLeast(3))
            ->method('select')
            ->willReturnCallback(function () {
                return clone $this->selectMock;
            });

        $mainSelect  = clone $this->selectMock;
        $this->selectMock
            ->method('from')
            ->willReturnSelf();
        $this->selectMock
            ->method('joinInner')
            ->with(['relation' => 'catalog_product_relation'], "table.$linkField=relation.child_id", [])
            ->willReturnSelf();
        $this->selectMock
            ->method('where')
            ->willReturnMap([
                ['table.attribute_id=?', $attributeId, null, $this->selectMock],
                ['table.store_id=?', 0, null, $this->selectMock],
                ['table.value=:targetrule_bind_0', null, null, $this->selectMock],
            ]);
        $mainSelect->expects($this->atLeastOnce())
            ->method('union')
            ->willReturnSelf();

        $this->attributesMock
            ->method('getAttributeObject')
            ->willReturn($this->eavAttributeMock);
        $this->eavAttributeMock->expects($this->once())
            ->method('isScopeGlobal')
            ->willReturn(true);
        $this->eavAttributeMock->expects($this->once())
            ->method('isStatic')
            ->willReturn(false);
        $this->eavAttributeMock
            ->method('getId')
            ->willReturn($attributeId);
        $this->eavAttributeMock
            ->method('getBackendTable')
            ->willReturn($attributeTable);

        $this->metadataPoolMock->expects($this->once())
            ->method('getMetadata')
            ->with(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->will($this->returnValue($this->entityMetadataMock));
        $this->entityMetadataMock->expects($this->once())
            ->method('getLinkField')
            ->willReturn($linkField);

        $this->selectMock
            ->method('from')
            ->willReturnSelf();
        $this->selectMock->expects($this->once())
            ->method('joinInner')
            ->with(['relation' => $relationTable], "table.{$linkField}=relation.child_id", [])
            ->willReturnSelf();
        $this->selectMock
            ->method('where')
            ->willReturnMap([
                ['table.attribute_id=?', $attributeId, null, $this->selectMock],
                ['table.store_id=?', 0, null, $this->selectMock],
                ["table.value={$attributesValue}", null, null, $this->selectMock]
            ]);

        $resultClause = $this->sqlBuilder->generateWhereClause(
            $this->attributesMock,
            $bind
        );
        $this->assertEquals("e.{$linkField} IN ()", $resultClause);
    }

    public function testGenerateWhereClauseForNonGlobalScopeAttribute()
    {
        $storeId = 1;
        $attributeId = 42;
        $attributesValue = 'string';
        $attributesOperator = '==';
        $attributeTable = 'catalog_product_entity_varchar';
        $this->attributesMock->setOperator($attributesOperator);
        $this->attributesMock->setAttribute('filter');
        $this->attributesMock->setValue($attributesValue);
        $entityFieldName = 'entity_id';
        $bind = [];
        $checkSql = 'IF(attr_s.value_id > 0, attr_s.value, attr_d.value)';
        $leftJoinSql = "attr_s.{$entityFieldName} = attr_d.{$entityFieldName}" .
            " AND attr_s.attribute_id = attr_d.attribute_id AND attr_s.store_id=?";

        $this->metadataPoolMock->expects($this->once())
            ->method('getMetadata')
            ->with(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->will($this->returnValue($this->entityMetadataMock));

        $this->entityMetadataMock->expects($this->once())
            ->method('getLinkField')
            ->willReturn($entityFieldName);

        $this->eavAttributeMock->expects($this->once())
            ->method('isScopeGlobal')
            ->willReturn(false);

        $this->indexResourceMock->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->attributesMock->expects($this->any())
            ->method('getAttributeObject')
            ->willReturn($this->eavAttributeMock);

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->willReturn($this->selectMock);
        $this->connectionMock->expects($this->once())
            ->method('getCheckSql')
            ->willReturn($checkSql);
        $this->connectionMock->expects($this->once())
            ->method('quoteInto')
            ->with($leftJoinSql, $storeId)
            ->willReturn($leftJoinSql);

        $this->eavAttributeMock->expects($this->once())
            ->method('isStatic')
            ->willReturn(false);
        $this->eavAttributeMock->expects($this->once())
            ->method('getId')
            ->willReturn($attributeId);
        $this->eavAttributeMock->expects($this->once())
            ->method('getBackendTable')
            ->willReturn($attributeTable);

        $this->indexResourceMock->expects($this->once())
            ->method('getOperatorCondition')
            ->with($checkSql, $attributesOperator, $attributesValue)
            ->willReturn($checkSql);

        $this->selectMock->expects($this->once())
            ->method('from')
            ->with(
                ['attr_d' => $attributeTable],
                'COUNT(*)'
            )
            ->willReturnSelf();

        $this->selectMock->expects($this->once())
            ->method('joinLeft')
            ->with(
                ['attr_s' => $attributeTable],
                $leftJoinSql,
                []
            )
            ->willReturnSelf();
        $this->selectMock->expects($this->exactly(4))
            ->method('where')
            ->willReturnMap([
                ["attr_d.{$entityFieldName} = e.entity_id",null, null, $this->selectMock],
                ['attr_d.attribute_id=?', $attributeId, null, $this->selectMock],
                ["attr_d.storeId=?", Store::DEFAULT_STORE_ID, null, $this->selectMock],
                [$checkSql, null, null, $this->selectMock]
            ]);

        $resultClause = $this->sqlBuilder->generateWhereClause(
            $this->attributesMock,
            $bind,
            $storeId
        );

        $this->assertEquals("() > 0", $resultClause);
    }
}
