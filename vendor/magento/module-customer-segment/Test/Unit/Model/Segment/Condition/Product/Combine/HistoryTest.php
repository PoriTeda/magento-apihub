<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\Product\Combine;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Unit test of Product History condition model.
 */
class HistoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Subject of testing.
     *
     * @var \Magento\CustomerSegment\Model\Segment\Condition\Product\Combine\History
     */
    protected $subject;

    /**
     * Sales resource model mock.
     *
     * @var \Magento\Sales\Model\ResourceModel\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceOrder;

    /**
     * Segment resource model mock.
     *
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceSegment;

    /**
     * Database adapter mock.
     *
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->resourceOrder = $this->createPartialMock(
            \Magento\Sales\Model\ResourceModel\Order::class,
            ['getConnection']
        );

        $this->connectionMock = $this->createPartialMock(
            \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
            ['fetchOne', 'fetchCol']
        );

        $this->connectionMock->expects($this->any())->method('fetchCol')->willReturn([1, 2, 3]);

        $this->resourceOrder->expects($this->any())->method('getConnection')->willReturn($this->connectionMock);

        $this->resourceSegment = $this->createPartialMock(
            \Magento\CustomerSegment\Model\ResourceModel\Segment::class,
            ['createSelect', 'getTable', 'getConnection']
        );

        $select = $this->createPartialMock(
            \Magento\Framework\DB\Select::class,
            ['from', 'where', 'limit', 'reset', 'columns', '__toString']
        );

        $select->expects($this->any())->method('from')->willReturnSelf();
        $select->expects($this->any())->method('where')->willReturnSelf();
        $select->expects($this->any())->method('limit')->willReturnSelf();

        $this->resourceSegment->expects($this->any())->method('createSelect')->willReturn($select);
        $this->resourceSegment->expects($this->any())->method('getConnection')->willReturn($this->connectionMock);

        $this->subject = $objectManager->getObject(
            \Magento\CustomerSegment\Model\Segment\Condition\Product\Combine\History::class,
            [
                'resourceOrder' => $this->resourceOrder,
                'resourceSegment' => $this->resourceSegment
            ]
        );
    }

    /**
     * @param bool $isSatisfied
     * @dataProvider isSatisfiedByDataProvider
     * @return void
     */
    public function testIsSatisfiedBy($isSatisfied)
    {
        $this->connectionMock->expects($this->once())->method('fetchOne')->willReturn($isSatisfied);

        if ($isSatisfied) {
            $this->assertTrue($this->subject->isSatisfiedBy(1, 1, []));
        } else {
            $this->assertFalse($this->subject->isSatisfiedBy(1, 1, []));
        }

        $this->assertEquals([1, 2, 3], $this->subject->getData('product_ids'));
    }

    /**
     * @return array
     */
    public function isSatisfiedByDataProvider()
    {
        return [[1], [0]];
    }

    public function testGetSatisfiedIds()
    {
        $this->assertEquals([1, 2, 3], $this->subject->getSatisfiedIds(1));
        $this->assertEquals([1, 2, 3], $this->subject->getData('product_ids'));
    }

    /**
     * @param bool $smartMode
     * @param string|null $conditionValue
     * @param string $expectedResource
     * @dataProvider getResourceDataProvider
     * @return void
     */
    public function testGetResource($smartMode, $conditionValue, $expectedResource)
    {
        $this->subject->setValue($conditionValue);

        if ($expectedResource == 'segment') {
            $this->assertEquals($this->resourceSegment, $this->subject->getResource($smartMode));
        } else {
            $this->assertEquals($this->resourceOrder, $this->subject->getResource($smartMode));
        }
    }

    /**
     * @return array
     */
    public function getResourceDataProvider()
    {
        return [
            [false, null, 'segment'],
            [true, \Magento\CustomerSegment\Model\Segment\Condition\Product\Combine\History::VIEWED, 'segment'],
            [true, \Magento\CustomerSegment\Model\Segment\Condition\Product\Combine\History::ORDERED, 'order']
        ];
    }
}
