<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition;

use Magento\AdvancedSalesRule\Model\Rule\Condition\Address;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\Factory;

/**
 * Class AddressTest
 */
class AddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedSalesRule\Model\Rule\Condition\Address
     */
    protected $model;

    /**
     * @var \Magento\Rule\Model\Condition\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \Magento\Directory\Model\Config\Source\Country|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $directoryCountry;

    /**
     * @var \Magento\Directory\Model\Config\Source\Allregion|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $directoryAllregion;

    /**
     * @var \Magento\Shipping\Model\Config\Source\Allmethods|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingAllmethods;

    /**
     * @var \Magento\Payment\Model\Config\Source\Allmethods|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentAllmethods;

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

        $className = \Magento\Rule\Model\Condition\Context::class;
        $this->context = $this->createMock($className);

        $className = \Magento\Directory\Model\Config\Source\Country::class;
        $this->directoryCountry = $this->createMock($className);

        $className = \Magento\Directory\Model\Config\Source\Allregion::class;
        $this->directoryAllregion = $this->createMock($className);

        $className = \Magento\Shipping\Model\Config\Source\Allmethods::class;
        $this->shippingAllmethods = $this->createMock($className);

        $className = \Magento\Payment\Model\Config\Source\Allmethods::class;
        $this->paymentAllmethods = $this->createMock($className);

        $className = \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\Factory::class;
        $this->concreteConditionFactory = $this->createPartialMock($className, ['create']);

        $this->model = $this->objectManager->getObject(
            \Magento\AdvancedSalesRule\Model\Rule\Condition\Address::class,
            [
                'context' => $this->context,
                'directoryCountry' => $this->directoryCountry,
                'directoryAllregion' => $this->directoryAllregion,
                'shippingAllmethods' => $this->shippingAllmethods,
                'paymentAllmethods' => $this->paymentAllmethods,
                'concreteConditionFactory' => $this->concreteConditionFactory,
                'data' => [],
            ]
        );
    }

    /**
     * test IsFilterable
     */
    public function testIsFilterable()
    {
        $className = \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface::class;
        $interface =$this->createMock($className);

        $interface->expects($this->any())
            ->method('isFilterable')
            ->willReturn(true);

        $this->concreteConditionFactory->expects($this->any())
            ->method('create')
            ->willReturn($interface);

        $this->assertTrue($this->model->isFilterable());
    }

    /**
     * test GetFilterGroups
     */
    public function testGetFilterGroups()
    {
        $className = \Magento\AdvancedRule\Model\Condition\FilterGroupInterface::class;
        $filterGroupInterface =$this->createMock($className);

        $className = \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface::class;
        $interface =$this->createMock($className);

        $interface->expects($this->any())
            ->method('getFilterGroups')
            ->willReturn([$filterGroupInterface]);

        $this->concreteConditionFactory->expects($this->any())
            ->method('create')
            ->willReturn($interface);

        $this->assertEquals([$filterGroupInterface], $this->model->getFilterGroups());
    }
}
