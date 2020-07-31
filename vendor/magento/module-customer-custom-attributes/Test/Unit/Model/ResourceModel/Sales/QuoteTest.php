<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\ResourceModel\Sales;

use Magento\Customer\Model\Attribute;
use Magento\Framework\DB\Ddl\Table;

class QuoteTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote
     */
    protected $quote;

    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parentResourceModelMock;

    protected function setUp()
    {
        $this->resourceMock = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $this->connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->parentResourceModelMock = $this->createMock(\Magento\Quote\Model\ResourceModel\Quote::class);

        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->connectionMock));
        $this->resourceMock->expects($this->any())
            ->method('getTableName')
            ->will($this->returnArgument(0));

        $contextMock = $this->createMock(\Magento\Framework\Model\ResourceModel\Db\Context::class);
        $contextMock->expects($this->once())->method('getResources')->willReturn($this->resourceMock);

        $this->quote = new \Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote(
            $contextMock,
            $this->parentResourceModelMock
        );
    }

    /**
     * @param string $backendType
     * @dataProvider dataProviderSaveNewAttributeNegative
     */
    public function testSaveNewAttributeNegative($backendType)
    {
        $attributeMock = $this->createMock(\Magento\Customer\Model\Attribute::class);
        $attributeMock->expects($this->once())
            ->method('getBackendType')
            ->will($this->returnValue($backendType));

        $this->connectionMock->expects($this->never())
            ->method('addColumn');

        $this->assertEquals($this->quote, $this->quote->saveNewAttribute($attributeMock));
    }

    /**
     * @return array
     */
    public function dataProviderSaveNewAttributeNegative()
    {
        return [
            [''],
            [Attribute::TYPE_STATIC],
            ['something_wrong'],
        ];
    }

    /**
     * @param string $backendType
     * @param array $definition
     * @dataProvider dataProviderSaveNewAttribute
     */
    public function testSaveNewAttribute($backendType, array $definition)
    {
        $attributeMock = $this->createMock(\Magento\Customer\Model\Attribute::class);
        $attributeMock->expects($this->once())
            ->method('getBackendType')
            ->will($this->returnValue($backendType));
        $attributeMock->expects($this->once())
            ->method('getAttributeCode')
            ->will($this->returnValue('attribute_code'));

        $definition['comment'] = 'Customer Attribute Code';

        $this->connectionMock->expects($this->once())
            ->method('addColumn')
            ->with('magento_customercustomattributes_sales_flat_quote', 'customer_attribute_code', $definition, null);

        $this->assertEquals($this->quote, $this->quote->saveNewAttribute($attributeMock));
    }

    /**
     * @return array
     */
    public function dataProviderSaveNewAttribute()
    {
        return [
            ['datetime', ['type' => Table::TYPE_DATE]],
            ['decimal', ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, 'length' => '12,4']],
            ['int', ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER]],
            ['text', ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT]],
            ['varchar', ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 'length' => 255]],
        ];
    }

    public function testDeleteAttribute()
    {
        $attributeMock = $this->createMock(\Magento\Customer\Model\Attribute::class);
        $attributeMock->expects($this->once())
            ->method('getAttributeCode')
            ->will($this->returnValue('attribute_code'));

        $this->connectionMock->expects($this->once())
            ->method('dropColumn')
            ->with('magento_customercustomattributes_sales_flat_quote', 'customer_attribute_code', null);

        $this->assertEquals($this->quote, $this->quote->deleteAttribute($attributeMock));
    }

    public function testIsEntityExistsNoId()
    {
        $salesMock = $this->createMock(\Magento\CustomerCustomAttributes\Model\Sales\AbstractSales::class);
        $salesMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(0));

        $this->connectionMock->expects($this->never())
            ->method('select');
        $this->connectionMock->expects($this->never())
            ->method('fetchOne');

        $this->assertEquals(false, $this->quote->isEntityExists($salesMock));
    }

    /**
     * @param string $fetchedColumn
     * @param bool $result
     * @dataProvider dataProviderIsEntityExists
     */
    public function testIsEntityExists($fetchedColumn, $result)
    {
        $salesMock = $this->createMock(\Magento\CustomerCustomAttributes\Model\Sales\AbstractSales::class);
        $salesMock->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue(1));

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->will($this->returnValue($selectMock));

        $this->parentResourceModelMock->expects($this->once())
            ->method('getMainTable')
            ->will($this->returnValue('parent_table'));
        $this->parentResourceModelMock->expects($this->once())
            ->method('getIdFieldName')
            ->will($this->returnValue('parent_id'));

        $selectMock->expects($this->once())
            ->method('from')
            ->with('parent_table', 'parent_id')
            ->will($this->returnSelf());
        $selectMock->expects($this->once())
            ->method('forUpdate')
            ->with(true)
            ->will($this->returnSelf());
        $selectMock->expects($this->once())
            ->method('where')
            ->with("parent_id = ?", 1)
            ->will($this->returnSelf());

        $this->connectionMock->expects($this->once())
            ->method('fetchOne')
            ->with($selectMock)
            ->will($this->returnValue($fetchedColumn));

        $this->assertEquals($result, $this->quote->isEntityExists($salesMock));
    }

    /**
     * @return array
     */
    public function dataProviderIsEntityExists()
    {
        return [
            ['', false],
            ['some_value', true],
        ];
    }
}
