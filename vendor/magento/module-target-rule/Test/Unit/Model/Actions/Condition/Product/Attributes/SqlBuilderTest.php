<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model\Actions\Condition\Product\Attributes;

use Magento\TargetRule\Model\Actions\Condition\Product\Attributes;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes\SqlBuilder;

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
            ['from', 'assemble', 'where', 'joinLeft']
        );
        $this->metadataPoolMock = $this->createPartialMock(
            \Magento\Framework\EntityManager\MetadataPool::class,
            ['getMetadata']
        );
        $this->eavAttributeMock = $this->createPartialMock(
            \Magento\Catalog\Model\ResourceModel\Eav\Attribute::class,
            ['isScopeGlobal', 'isStatic', 'getBackendTable', 'getId']
        );
        $this->attributesMock = $this->createPartialMock(
            Attributes::class,
            ['getAttributeObject', 'getValueType']
        );

        $this->sqlBuilder = new SqlBuilder($this->metadataPoolMock, $this->indexResourceMock);
    }

    public function testGenerateWhereClauseForStaticAttribute()
    {
        $attributesValue = '1,2';
        $attributesNormalizedValue = [1,2];
        $attributesOperator = '()';
        $attribute = 'filter';
        $bind = [];
        $expectedClause = "e.row_id IN (1,2)";
        $this->attributesMock->setOperator($attributesOperator);
        $this->attributesMock->setAttribute($attribute);
        $this->attributesMock->setValue($attributesValue);

        $this->eavAttributeMock->expects($this->once())
            ->method('isStatic')
            ->willReturn(true);

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->willReturn($this->selectMock);
        $this->indexResourceMock->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->indexResourceMock->expects($this->once())
            ->method('getOperatorCondition')
            ->with('e.' . $attribute, $attributesOperator, $attributesNormalizedValue)
            ->willReturn($expectedClause);

        $this->attributesMock->expects($this->any())
            ->method('getAttributeObject')
            ->willReturn($this->eavAttributeMock);
        $this->attributesMock->expects($this->exactly(2))
            ->method('getValueType')
            ->willReturn(Attributes::VALUE_TYPE_CONSTANT);

        $resultClause = $this->sqlBuilder->generateWhereClause(
            $this->attributesMock,
            $bind
        );

        $this->assertEquals("({$expectedClause})", $resultClause);
    }

    public function testGenerateWhereClauseForCategoryIds()
    {
        $attributesValue = '1,2';
        $attributesOperator = '()';
        $attribute = 'category_ids';
        $bind = [];
        $categoryTable = 'catalog_category_product';
        $categoryWhere = 'category_id in (1,2)';
        $this->attributesMock->setOperator($attributesOperator);
        $this->attributesMock->setAttribute($attribute);
        $this->attributesMock->setValue($attributesValue);

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->willReturn($this->selectMock);

        $this->indexResourceMock->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->indexResourceMock->expects($this->atLeastOnce())
            ->method('getTable')
            ->with('catalog_category_product')
            ->willReturn($categoryTable);
        $this->indexResourceMock->expects($this->once())
            ->method('getOperatorBindCondition')
            ->with(
                'category_id',
                'category_ids',
                $attributesOperator,
                $bind,
                ['bindArrayOfIds']
            )->willReturn($categoryWhere);

        $this->attributesMock->expects($this->any())
            ->method('getAttributeObject')
            ->willReturn($this->eavAttributeMock);
        $this->attributesMock->expects($this->once())
            ->method('getValueType')
            ->willReturn(Attributes::VALUE_TYPE_SAME_AS);

        $this->selectMock->expects($this->once())
            ->method('from')
            ->with($categoryTable, 'COUNT(*)')
            ->willReturnSelf();
        $this->selectMock->expects($this->exactly(2))
            ->method('where')
            ->willReturnMap([
                ['product_id=e.entity_id', null, null, $this->selectMock],
                [$categoryWhere, null, null, $this->selectMock]
            ]);
        $this->selectMock->expects($this->once())
            ->method('assemble')
            ->willReturn($categoryWhere);

        $resultClause = $this->sqlBuilder->generateWhereClause(
            $this->attributesMock,
            $bind
        );
        $this->assertEquals("({$categoryWhere}) > 0", $resultClause);
    }
}
