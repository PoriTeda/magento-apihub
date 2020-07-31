<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\Sales;

use Magento\CustomerSegment\Model\Segment\Condition\Sales\Ordersnumber;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\CustomerSegment\Model\ConditionFactory;
use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\Framework\View\Layout;

/**
 * Class OrdersnumberTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrdersnumberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Ordersnumber
     */
    protected $model;

    /**
     * @var Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderResourceMock;

    /**
     * @var ConditionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $conditionFactoryMock;

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceSegment;

    /**
     * @var string
     */
    protected $salesOrderTable = 'sales_order';

    /**
     * @var string
     */
    protected $storeTable = 'store';

    /**
     * @var array
     */
    protected $storeIds = [1];

    /**
     * @var Layout|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $layout;

    /**
     * Test setUp
     */
    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->orderResourceMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order::class);
        $this->layout = $this->createMock(\Magento\Framework\View\Layout::class);
        $ruleContextMock = $this->getMockBuilder(\Magento\Rule\Model\Condition\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ruleContextMock->method('getLayout')->willReturn($this->layout);
        $this->resourceSegment =
            $this->createMock(\Magento\CustomerSegment\Model\ResourceModel\Segment::class);

        $this->conditionFactoryMock = $this->createMock(\Magento\CustomerSegment\Model\ConditionFactory::class);

        $this->model = $objectManager->getObject(
            \Magento\CustomerSegment\Model\Segment\Condition\Sales\Ordersnumber::class,
            [
                'context' => $ruleContextMock,
                'orderResource' => $this->orderResourceMock,
                'conditionFactory' => $this->conditionFactoryMock,
                'resourceSegment' => $this->resourceSegment
            ]
        );
    }

    /**
     * Test get new child select options
     */
    public function testGetNewChildSelectOptions()
    {
        $orderStatusOption = 'Order Status';
        $upToDateOption = 'Up To Date';
        $dateRangeOption = 'Date Range';

        $orderStatusMock = $this->createMock(\Magento\CustomerSegment\Model\Segment\Condition\Order\Status::class);
        $orderStatusMock->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->willReturn($orderStatusOption);
        $upToDateMock = $this->createMock(\Magento\CustomerSegment\Model\Segment\Condition\Uptodate::class);
        $upToDateMock->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->willReturn($upToDateOption);
        $dateRangeMock = $this->createMock(\Magento\CustomerSegment\Model\Segment\Condition\Daterange::class);
        $dateRangeMock->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->willReturn($dateRangeOption);

        $returnValueMap = [
            ['Order\Status', [], $orderStatusMock],
            ['Uptodate', [], $upToDateMock],
            ['Daterange', [], $dateRangeMock]
        ];

        $this->conditionFactoryMock->method('create')
            ->willReturnMap($returnValueMap);

        $expectedResult = [
            [
                'value' => '',
                'label' => __('Please choose a condition to add.')
            ],
            $orderStatusOption,
            [
                'value' => [
                    $upToDateOption,
                    $dateRangeOption,
                ],
                'label' => __('Date Ranges')
            ]
        ];
        $this->assertEquals($expectedResult, $this->model->getNewChildSelectOptions());
    }

    /**
     * Test load attribute options
     */
    public function testLoadAttributeOptions()
    {
        $this->assertEquals($this->model, $this->model->loadAttributeOptions());
        $this->assertEquals(['total' => __('Total'), 'average' => __('Average')], $this->model->getAttributeOption());
    }

    /**
     * Test get value element type
     */
    public function testGetValueElementType()
    {
        $this->assertEquals('text', $this->model->getValueElementType());
    }

    /**
     * Test get matched events
     */
    public function testGetMatchedEvents()
    {
        $this->assertEquals(['sales_order_save_commit_after'], $this->model->getMatchedEvents());
    }

    /**
     * Test load value options
     */
    public function testLoadValueOptions()
    {
        $this->assertEquals($this->model, $this->model->loadValueOptions());
        $this->assertEquals([], $this->model->getValueOption());
    }

    /**
     * @dataProvider conditionProvider
     */
    public function testGetConditionsSql($operator, $value, $attribute, $checkSql)
    {
        $website = 1;
        $salesOrderTable = 'sales_order_table';
        $storeTable = 'store_table';
        $checkSqlResult = 'check_sql_result';
        $storeIds = [1, 2];

        $this->model->setData('operator', $operator);
        $this->model->setData('value', $value);
        $this->model->setData('attribute', $attribute);

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

        $storeSelect = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeSelect->expects($this->once())
            ->method('from')
            ->with(['store' => $storeTable], ['store.store_id'])
            ->willReturnSelf();
        $storeSelect->expects($this->once())
            ->method('where')
            ->with('store.website_id IN (?)', $website)
            ->willReturnSelf();

        $this->resourceSegment->expects($this->exactly(2))
            ->method('createSelect')
            ->willReturnOnConsecutiveCalls($select, $storeSelect);

        $this->resourceSegment->expects($this->once())
            ->method('getSqlOperator')
            ->willReturn($operator);

        $connection = $this->getMockForAbstractClass(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->resourceSegment->expects($this->any())
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

        $this->resourceSegment->expects($this->exactly(2))
            ->method('getTable')
            ->willReturnMap([['sales_order', $salesOrderTable], ['store', $storeTable]]);

        $this->assertEquals($select, $this->model->getConditionsSql(null, 1, false));
    }

    /**
     * @dataProvider satisfiedIdsDataProvider
     * @param int $customer
     * @param int $websiteId
     * @param array $params
     * @param array $operator
     * @param array $value
     */
    public function testIsSatisfiedBy($customer, $websiteId, $params, $operator, $value)
    {
        $select = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->stepResourcesExpects($operator, $value, $select);
        $select->expects($this->exactly(2))->method('from')->withConsecutive(
            [
                ['sales_order' => $this->salesOrderTable],
                $this->callback(function ($value) {
                    $this->assertArrayHasKey(0, $value);
                    $this->assertInstanceOf('\Zend_Db_Expr', $value[0]);
                    return true;
                })
            ],
            [
                ['store' => $this->storeTable],
                ['store.store_id']
            ]
        )->willReturn($select);
        $select->expects($this->exactly(3))->method('where')
            ->withConsecutive(
                ['store.website_id IN (?)', $websiteId],
                ['sales_order.store_id IN (?)', implode(',', $this->storeIds)],
                ['sales_order.customer_id = :customer_id', null, null]
            )->willReturnSelf();
        $this->stepOrderAdapterPreparation()->expects($this->once())->method('fetchOne')->willReturn(1);
        $this->assertTrue($this->model->isSatisfiedBy($customer, $websiteId, $params));
    }

    /**
     * @dataProvider websiteIdsDataProvider
     * @param $websiteId
     * @param array $operator
     * @param array $value
     */
    public function testGetSatisfiedIds($websiteId, $operator, $value)
    {
        $params = [];
        $expectedSatisfiedIds = [1];
        $select = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->stepResourcesExpects($operator, $value, $select);
        $select->expects($this->exactly(2))->method('from')->withConsecutive(
            [
                ['sales_order' => $this->salesOrderTable],
                $this->callback(function ($value) {
                    $this->assertArrayHasKey(0, $value);
                    $this->assertEquals('sales_order.customer_id', $value[0]);
                    return true;
                })
            ],
            [
                ['store' => $this->storeTable],
                ['store.store_id']
            ]
        )->willReturn($select);
        $select->expects($this->exactly(3))->method('where')
            ->withConsecutive(
                ['sales_order.customer_id IS NOT NULL'],
                ['store.website_id IN (?)', $websiteId],
                ['sales_order.store_id IN (?)', implode(',', $this->storeIds)]
            )->willReturnSelf();
        $select->expects($this->once())->method('group')->with(['sales_order.customer_id'])->willReturnSelf();
        $select->expects($this->once())->method('having')
            ->with($this->callback(function ($internalValue) use ($operator, $value) {
                $object = new \Zend_Db_Expr("COUNT(*) $operator $value");
                $this->assertEquals($object, $internalValue);
                return true;
            }))->willReturnSelf();
        $this->stepOrderAdapterPreparation()->expects($this->once())
            ->method('fetchCol')
            ->with($select, $params)
            ->willReturn($expectedSatisfiedIds);
        $this->assertEquals($expectedSatisfiedIds, $this->model->getSatisfiedIds($websiteId));
    }

    public function satisfiedIdsDataProvider()
    {
        return [
            ['aaa', 1, [], '=', 2],
            ['ddd', 2, [], '>', 2]
        ];
    }

    public function websiteIdsDataProvider()
    {
        return [
            [1, '=', 2],
            [2, '=', 2]
        ];
    }

    /**
     * @param $operator
     * @param $select
     * @param $connection
     */
    protected function stepResourceSegmentPreparation($operator, $select, $connection)
    {
        $this->resourceSegment->expects($this->atLeastOnce())->method('createSelect')->willReturn($select);
        $this->resourceSegment->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($connection);
        $this->resourceSegment->expects($this->once())->method('getSqlOperator')
            ->with($operator)
            ->willReturn($operator);
        $this->resourceSegment->expects($this->exactly(2))
            ->method('getTable')
            ->withConsecutive(
                [$this->salesOrderTable],
                [$this->storeTable]
            )->willReturnOnConsecutiveCalls($this->salesOrderTable, $this->storeTable);
    }

    /**
     * @param $operator
     * @param $value
     * @param $select
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function stepSegmentAdapterPreparation($operator, $value, $select)
    {
        $connection = $this->getMockForAbstractClass(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $connection->expects($this->once())->method('quote')->willReturn($value);
        $connection->expects($this->once())->method('getCheckSql')
            ->with("COUNT(*) $operator $value", 1, 0)
            ->willReturn("COUNT(*) $operator $value");
        $connection->expects($this->once())->method('fetchCol')->with($select)->willReturn($this->storeIds);
        return $connection;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function stepOrderAdapterPreparation()
    {
        $orderAdapter = $this->getMockForAbstractClass(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->orderResourceMock->expects($this->once())->method('getConnection')
            ->willReturn($orderAdapter);
        return $orderAdapter;
    }

    /**
     * @param $operator
     * @param $value
     * @param $select
     */
    protected function stepResourcesExpects($operator, $value, $select)
    {
        $this->stepResourceSegmentPreparation(
            $operator,
            $select,
            $this->stepSegmentAdapterPreparation($operator, $value, $select)
        );
        $this->model->setData('operator', $operator);
        $this->model->setData('value', $value);
    }

    public function conditionProvider()
    {
        return [
            ['>', null, 'total', 'COUNT(*) > 0'],
            ['=', 0, 'total', 'COUNT(*) = 0'],
            ['<', 1, 'total', 'COUNT(*) < 1']
        ];
    }
}
