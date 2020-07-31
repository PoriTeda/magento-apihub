<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Test\Unit\Model\ResourceModel\Report\Invitation\Order;

/**
 * Class CollectionTest
 */
class CollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Invitation\Model\ResourceModel\Report\Invitation\Order\Collection
     */
    protected $collection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $select;

    /**
     * Initialization
     */
    protected function setUp()
    {
        $entityFactory = $this->getMockForAbstractClass(
            \Magento\Framework\Data\Collection\EntityFactoryInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $logger = $this->getMockForAbstractClass(
            \Psr\Log\LoggerInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $fetchStrategy = $this->getMockForAbstractClass(
            \Magento\Framework\Data\Collection\Db\FetchStrategyInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $this->connectionMock = $this->getMockForAbstractClass(
            \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
            [],
            '',
            false,
            false,
            true,
            ['select', 'fetchPairs', 'prepareSqlCondition', 'fetchAssoc']
        );
        $this->select = $this->getMockForAbstractClass(
            \Magento\Framework\DB\Select::class,
            [],
            '',
            false,
            false,
            true,
            ['from', 'columns', 'where', 'reset', 'group']
        );
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject $contextMock
         */
        $contextMock = $this->createPartialMock(
            \Magento\Framework\Model\ResourceModel\Db\Context::class,
            ['getResources']
        );
        $resource = $this->getMockForAbstractClass(
            \Magento\Framework\Model\ResourceModel\Db\AbstractDb::class,
            [],
            '',
            false,
            false,
            true,
            ['getConnection', 'getMainTable', 'getTable']
        );

        $resource->expects($this->any())->method('getConnection')->willReturn($this->connectionMock);
        $contextMock->expects($this->once())->method('getResources')->willReturn($resource);
        $this->resourceMock = $this->getMockForAbstractClass(
            \Magento\Invitation\Model\ResourceModel\Invitation::class,
            ['context' => $contextMock],
            '',
            true,
            false,
            true,
            ['getConnection', 'getMainTable', 'getTable']
        );

        $this->resourceMock->expects($this->any())->method('getConnection')->willReturn($this->connectionMock);
        $this->resourceMock->expects($this->any())->method('getMainTable')->willReturn('main_table');
        $this->connectionMock->expects($this->any())->method('select')->willReturn($this->select);

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->collection = $objectManager->getObject(
            \Magento\Invitation\Model\ResourceModel\Report\Invitation\Order\Collection::class,
            [
                'entityFactory' => $entityFactory,
                'logger' => $logger,
                'fetchStrategy' => $fetchStrategy,
                'connection' => $this->connectionMock,
                'resource' => $this->resourceMock
            ]
        );
    }

    /**
     * Checks getPurchasedNumber in Invitation conversion report
     *
     * @param array $customerToStoreMap
     * @param array $orderCountsByStoreMap
     * @param int $expectedCnt
     * @dataProvider getPurchasedNumberDataProvider
     */
    public function testGetPurchaseNumber($customerToStoreMap, $orderCountsByStoreMap, $expectedCnt)
    {
        $tableOrderName = 'sales_order';
        $expectedCondition = 'o.customer_id IN (' . implode(',', array_keys($customerToStoreMap)) . ')';
        $item = new \Magento\Framework\DataObject(['id' => 1]);
        $this->collection->addItem($item);
        $this->connectionMock->expects($this->once())->method('fetchPairs')->willReturn($customerToStoreMap);
        $this->resourceMock->expects($this->once())
            ->method('getTable')
            ->with($tableOrderName)
            ->willReturn($tableOrderName);
        $this->select->expects($this->any())->method('reset')->willReturnSelf();
        $this->select->expects($this->once())->method('columns')->willReturnSelf();
        $this->select->expects($this->at(1))->method('where')->willReturnSelf();
        $this->select->expects($this->once())->method('from')->with(
            ['o' => $tableOrderName],
            ['o.store_id', 'COUNT(DISTINCT o.customer_id) as cnt']
        )->willReturnSelf();
        $this->select->expects($this->at(5))->method('where')->with(
            $expectedCondition
        )->willReturnSelf();
        $this->connectionMock->expects($this->once())
            ->method('prepareSqlCondition')
            ->with('o.customer_id', ['in' => array_keys($customerToStoreMap)])
            ->willReturn($expectedCondition);
        $this->select->expects($this->once())->method('group')->with(['o.store_id'])->willReturnSelf();
        $this->connectionMock->expects($this->once())->method('fetchAssoc')->willReturn($orderCountsByStoreMap);
        $this->collection->load();
        $this->assertEquals($expectedCnt, $item->getPurchased());
    }

    public function getPurchasedNumberDataProvider()
    {
        return [
            [
                'customerToStoreMap' => [
                    1 => 3,
                    2 => 4
                ],
                'orderCountsByStoreMap' => [
                    3 => [
                        'cnt' => 35
                    ],
                    4 => [
                        'cnt' => 25
                    ],
                ],
                'expectedCnt' => 60
            ],
            [
                'customerToStoreMap' => [
                    1 => 3,
                    2 => 3
                ],
                'orderCountsByStoreMap' => [
                    3 => [
                        'cnt' => 20
                    ],
                    4 => [
                        'cnt' => 25
                    ],
                ],
                'expectedCnt' => 20
            ],
        ];
    }
}
