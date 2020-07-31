<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\ConcreteCondition\Address;

use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\CountryId;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\Factory;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\PaymentMethod;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\Postcode;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\Region;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\RegionId;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\ShippingMethod;

/**
 * Class FactoryTest
 */
class FactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\Factory
     */
    protected $model;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerInterface;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\AdvancedSalesRule\Model\Rule\Condition\Address|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressCondition;

    /**
     * Setup the test
     */
    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $className = \Magento\Framework\ObjectManagerInterface::class;
        $this->objectManagerInterface = $this->createMock($className);

        $className = \Magento\AdvancedSalesRule\Model\Rule\Condition\Address::class;
        $this->addressCondition = $this->createPartialMock($className, ['get', 'getAttribute']);

        $this->model = $this->objectManager->getObject(
            \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\Factory::class,
            [
                'objectManager' => $this->objectManagerInterface,
            ]
        );
    }

    /**
     * test Create Default
     * @param string $attribute
     * @dataProvider createDefaultDataProvider
     */
    public function testCreateDefault($attribute)
    {
        $this->addressCondition->expects($this->any())
            ->method('getAttribute')
            ->willReturn($attribute);

        $this->objectManagerInterface->expects($this->any())
            ->method('create')
            ->with(\Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\DefaultCondition::class)
            ->willReturn(
                $this->createMock(
                    \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\DefaultCondition::class
                )
            );

        $object = $this->model->create($this->addressCondition);
        $this->assertNotNull($object);
    }

    /**
     * @return array
     */
    public function createDefaultDataProvider()
    {
        return [
            'attribute_default' => ['default'],
            'attribute_sku' => ['sku'],
            'attribute_address' => ['address'],
        ];
    }

    /**
     * test Create Default
     * @param string $attribute
     * @param string $class
     * @dataProvider createAddressDataProvider
     */
    public function testCreateAddress($attribute, $class)
    {
        $this->addressCondition->expects($this->any())
            ->method('getAttribute')
            ->willReturn($attribute);

        $this->objectManagerInterface->expects($this->any())
            ->method('create')
            ->with($class, ['condition' => $this->addressCondition])
            ->willReturn(
                $this->createMock(
                    \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\DefaultCondition::class
                )
            );

        $object = $this->model->create($this->addressCondition);
        $this->assertNotNull($object);
    }

    /**
     * @return array
     */
    public function createAddressDataProvider()
    {
        return [
            'payment_method' => [
                'payment_method',
                PaymentMethod::class
            ],
            'shipping_method' => [
                'shipping_method',
                ShippingMethod::class
            ],
            'country_id' => [
                'country_id',
                CountryId::class
            ],
            'region_id' => [
                'region_id',
                RegionId::class
            ],
            'postcode' => [
                'postcode',
                Postcode::class
            ],
            'region' => [
                'region',
                Region::class
            ]
        ];
    }
}
