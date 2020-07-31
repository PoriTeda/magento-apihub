<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition;

use Magento\CustomerSegment\Model\Segment\Condition\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\ConcreteCondition\Factory;

/**
 * Class SegmentTest
 */
class SegmentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Segment
     */
    protected $model;

    /**
     * @var Factory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $concreteConditionFactory;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * Setup the test
     */
    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $className = \Magento\CustomerSegment\Model\Segment\Condition\ConcreteCondition\Factory::class;
        $this->concreteConditionFactory = $this->createPartialMock($className, ['create']);

        $className = \Magento\CustomerSegment\Model\Segment\Condition\Segment::class;
        $this->model = $this->objectManager->getObject(
            $className,
            [
                'concreteConditionFactory' => $this->concreteConditionFactory,
            ]
        );
    }

    /**
     * test isFilterable
     */
    public function testIsFilterable()
    {
        $className = \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface::class;
        $interface = $this->createMock($className);
        $interface->expects($this->any())
            ->method('isFilterable')
            ->willReturn(true);

        $this->concreteConditionFactory->expects($this->once())
            ->method('create')
            ->willReturn($interface);

        $this->assertTrue($this->model->isFilterable());
    }

    /**
     * test getFilterGroups
     */
    public function testGetFilterGroups()
    {
        $className = \Magento\AdvancedRule\Model\Condition\FilterGroupInterface::class;
        $filterGroupInterface = $this->createMock($className);

        $className = \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface::class;
        $interface = $this->createMock($className);
        $interface->expects($this->any())
            ->method('getFilterGroups')
            ->willReturn([$filterGroupInterface]);

        $this->concreteConditionFactory->expects($this->once())
            ->method('create')
            ->willReturn($interface);

        $this->assertEquals([$filterGroupInterface], $this->model->getFilterGroups());
    }
}
