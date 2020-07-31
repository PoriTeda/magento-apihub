<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Test\Unit\Model\Rule;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ConditionFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reminder\Model\Rule\ConditionFactory
     */
    private $model;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectManager;

    protected function setUp()
    {
        $helper = new ObjectManager($this);

        $this->objectManager = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->setMethods(['create', 'get', 'configure'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $helper->getObject(
            \Magento\Reminder\Model\Rule\ConditionFactory::class,
            ['objectManager' => $this->objectManager]
        );
    }

    public function testCreate()
    {
        $type = \Magento\Reminder\Model\Rule\Condition\Cart\Amount::class;

        $amount = $this->getMockBuilder($type)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager->expects($this->once())->method('create')->will($this->returnValue($amount));

        $result = $this->model->create($type);

        $this->assertInstanceOf("\\$type", $result);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Condition type is unexpected
     */
    public function testCreateInvalidArgumentException()
    {
        $type = 'someInvalidType';

        $this->model->create($type);
    }
}
