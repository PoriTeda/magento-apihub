<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Test\Unit\Model;

class ConditionFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConditionFactory
     */
    protected $model;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Rule\Model\Condition\AbstractCondition
     */
    protected $abstractCondition;

    /**
     * @var \Magento\Rule\Model\Condition\Context
     */
    protected $context;

    protected function setUp()
    {
        $this->objectManager = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);

        $this->context = $this->getMockBuilder(\Magento\Rule\Model\Condition\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->abstractCondition = $this->getMockForAbstractClass(
            \Magento\Rule\Model\Condition\AbstractCondition::class,
            [$this->context]
        );

        $this->model = new \Magento\CustomerSegment\Model\ConditionFactory(
            $this->objectManager
        );
    }

    protected function tearDown()
    {
        unset(
            $this->model,
            $this->objectManager,
            $this->abstractCondition,
            $this->context
        );
    }

    public function testCreate()
    {
        $className = 'TestClass';
        $classNamePrefix = 'Magento\CustomerSegment\Model\Segment\Condition\\';

        $this->objectManager
            ->expects($this->once())
            ->method('create')
            ->with($classNamePrefix . $className)
            ->will($this->returnValue($this->abstractCondition));

        $result = $this->model->create($classNamePrefix . $className);

        $this->assertInstanceOf(\Magento\Rule\Model\Condition\AbstractCondition::class, $result);
    }

    public function testCreateWithError()
    {
        $className = 'TestClass';
        $classNamePrefix = 'Magento\CustomerSegment\Model\Segment\Condition\\';

        $this->objectManager
            ->expects($this->once())
            ->method('create')
            ->with($classNamePrefix . $className)
            ->will($this->returnValue(new \StdClass()));

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            $classNamePrefix . $className . ' doesn\'t extends \Magento\Rule\Model\Condition\AbstractCondition'
        );

        $this->model->create($className);
    }
}
