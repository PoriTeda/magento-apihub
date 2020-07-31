<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\Sales;

class PurchasedquantityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerSegment\Model\Segment\Condition\Sales\Purchasedquantity
     */
    protected $model;

    /**
     * @var \Magento\Rule\Model\Condition\Context| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceSegmentMock;

    protected function setUp()
    {
        $this->contextMock = $this->getMockBuilder(\Magento\Rule\Model\Condition\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceSegmentMock = $this->createMock(\Magento\CustomerSegment\Model\ResourceModel\Segment::class);

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            \Magento\CustomerSegment\Model\Segment\Condition\Sales\Purchasedquantity::class,
            [
                'context' => $this->contextMock,
                'resourceSegment' => $this->resourceSegmentMock
            ]
        );
    }

    protected function tearDown()
    {
        unset(
            $this->model,
            $this->contextMock,
            $this->resourceSegmentMock
        );
    }

    /**
     * @param $operator customerSegment operator
     * @param $value
     * @param $attribute
     * @param $checkSql
     *
     * @dataProvider getConditionSqlDataProvider
     */
    public function testGetConditionsSqlIsNotFilteredAndNumericWebsite($operator, $value, $attribute, $checkSql)
    {
        $website = 1;
        $salesOrderTable = 'sales_order_table';
        $storeTable = 'store_table';
        $checkSqlResult = 'check_sql_result';
        $storeIds = [1, 2];

        $this->model->setData('operator', $operator);
        $this->model->setData('value', $value);
        $this->model->setData('attribute', $attribute);

        //select for _prepareConditionsSql
        $select = $this->createMock(\Magento\Framework\DB\Select::class);
        $select->expects($this->once())
            ->method('from')
            ->with(['sales_order' => $salesOrderTable], ['sales_order.customer_id'])
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('group')
            ->with(['sales_order.customer_id'])
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('having')
            ->with(new \Zend_Db_Expr($checkSqlResult))
            ->willReturnSelf();
        $select->expects($this->exactly(2))
            ->method('where')
            ->withConsecutive(
                ['sales_order.customer_id IS NOT NULL'],
                ['sales_order.store_id IN (?)', implode(',', $storeIds)]
            )
            ->willReturnSelf();

        //select for _limitByStoreWebsite
        $storeSelect = $this->createMock(\Magento\Framework\DB\Select::class);
        $storeSelect->expects($this->once())
            ->method('from')
            ->with(['store' => $storeTable], ['store.store_id'])
            ->willReturnSelf();
        $storeSelect->expects($this->once())
            ->method('where')
            ->with('store.website_id IN (?)', $website)
            ->willReturnSelf();

        $this->resourceSegmentMock->expects($this->exactly(2))
            ->method('createSelect')
            ->willReturnOnConsecutiveCalls($select, $storeSelect);

        $this->resourceSegmentMock->expects($this->exactly(2))
            ->method('getTable')
            ->willReturnMap([['sales_order', $salesOrderTable], ['store', $storeTable]]);

        $this->resourceSegmentMock->expects($this->once())
            ->method('getSqlOperator')
            ->willReturn($operator);

        $connection = $this->getMockForAbstractClass(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->resourceSegmentMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('fetchCol')
            ->with($storeSelect)
            ->willReturn($storeIds);
        $connection->expects($this->once())
            ->method('quote')
            ->with((double) $value)
            ->willReturn((double) $value);
        //for getConditionSql()
        $connection->expects($this->once())
            ->method('getCheckSql')
            ->with($checkSql, 1, 0)
            ->willReturn($checkSqlResult);

        $this->assertEquals($select, $this->model->getConditionsSql(null, $website, false));
    }

    /**
     * @param $operator customerSegment operator
     * @param $value
     * @param $attribute
     * @param $checkSql
     *
     * @dataProvider getConditionSqlDataProvider
     */
    public function testGetConditionsSqlIsNotFilteredAndNotNumericWebsite($operator, $value, $attribute, $checkSql)
    {
        $website =new \Zend_Db_Expr(1);
        $salesOrderTable = 'sales_order_table';
        $storeTable = 'store_table';
        $checkSqlResult = 'check_sql_result';

        $this->model->setData('operator', $operator);
        $this->model->setData('value', $value);
        $this->model->setData('attribute', $attribute);

        //select for _prepareConditionsSql
        $select = $this->createMock(\Magento\Framework\DB\Select::class);
        $select->expects($this->once())
            ->method('from')
            ->with(['sales_order' => $salesOrderTable], ['sales_order.customer_id'])
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('group')
            ->with(['sales_order.customer_id'])
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('having')
            ->with(new \Zend_Db_Expr($checkSqlResult))
            ->willReturnSelf();
        //for _limitByStoreWebsite
        $select->expects($this->once())
            ->method('join')
            ->with(['store' => $storeTable], 'sales_order.store_id=store.store_id', [])
            ->willReturnSelf();
        $select->expects($this->exactly(2))
            ->method('where')
            ->withConsecutive(
                ['sales_order.customer_id IS NOT NULL'],
                ['store.website_id IN (?)', $website]
            )
            ->willReturnSelf();

        $this->resourceSegmentMock->expects($this->once())
            ->method('createSelect')
            ->willReturn($select);

        $this->resourceSegmentMock->expects($this->exactly(2))
            ->method('getTable')
            ->willReturnMap([['sales_order', $salesOrderTable], ['store', $storeTable]]);

        $this->resourceSegmentMock->expects($this->once())
            ->method('getSqlOperator')
            ->willReturn($operator);

        $connection = $this->getMockForAbstractClass(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->resourceSegmentMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('quote')
            ->with((double) $value)
            ->willReturn((double) $value);
        //for getConditionSql()
        $connection->expects($this->once())
            ->method('getCheckSql')
            ->with($checkSql, 1, 0)
            ->willReturn($checkSqlResult);

        $this->assertEquals($select, $this->model->getConditionsSql(null, $website, false));
    }

    /**
     * @param $operator
     * @param $value
     * @param $attribute
     * @param $checkSql
     * @param $customer
     * @param $customerFilter
     *
     * @dataProvider getConditionSqlIsFilteredDataProvider
     *
     */
    public function testGetConditionsSqlIsFiltered($operator, $value, $attribute, $checkSql, $customer, $customerFilter)
    {
        $website =new \Zend_Db_Expr(1);
        $salesOrderTable = 'sales_order_table';
        $storeTable = 'store_table';
        $checkSqlResult = 'check_sql_result';

        $this->model->setData('operator', $operator);
        $this->model->setData('value', $value);
        $this->model->setData('attribute', $attribute);

        //select for _prepareConditionsSql
        $select = $this->createMock(\Magento\Framework\DB\Select::class);
        $select->expects($this->once())
            ->method('from')
            ->with(['sales_order' => $salesOrderTable], [new \Zend_Db_Expr($checkSqlResult)])
            ->willReturnSelf();
        //for _limitByStoreWebsite
        $select->expects($this->once())
            ->method('join')
            ->with(['store' => $storeTable], 'sales_order.store_id=store.store_id', [])
            ->willReturnSelf();
        $select->expects($this->exactly(2))
            ->method('where')
            ->withConsecutive(
                ['store.website_id IN (?)', $website],
                [$customerFilter]
            )
            ->willReturnSelf();

        $this->resourceSegmentMock->expects($this->once())
            ->method('createSelect')
            ->willReturn($select);

        $this->resourceSegmentMock->expects($this->exactly(2))
            ->method('getTable')
            ->willReturnMap([['sales_order', $salesOrderTable], ['store', $storeTable]]);

        $this->resourceSegmentMock->expects($this->once())
            ->method('getSqlOperator')
            ->willReturn($operator);

        $connection = $this->getMockForAbstractClass(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->resourceSegmentMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects($this->once())
            ->method('quote')
            ->with((double) $value)
            ->willReturn((double) $value);
        //for getConditionSql()
        $connection->expects($this->once())
            ->method('getCheckSql')
            ->with($checkSql, 1, 0)
            ->willReturn($checkSqlResult);

        $this->assertEquals($select, $this->model->getConditionsSql($customer, $website, true));
    }

    public function getConditionSqlDataProvider()
    {
        return [
            ['>', null, 'total', 'SUM(sales_order.total_qty_ordered) > 0'],
            ['=', 0, 'total', 'SUM(sales_order.total_qty_ordered) = 0'],
            ['<', 1, 'total', 'SUM(sales_order.total_qty_ordered) < 1'],
            ['<=', '2', 'average', 'AVG(sales_order.total_qty_ordered) <= 2']
        ];
    }

    public function getConditionSqlIsFilteredDataProvider()
    {
        return [
            ['>', 0, 'total', 'SUM(sales_order.total_qty_ordered) > 0', '', 'sales_order.customer_id = root.entity_id'],
            ['=', 2, 'average', 'AVG(sales_order.total_qty_ordered) = 2', 1, 'sales_order.customer_id = :customer_id']
        ];
    }
}
