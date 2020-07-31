<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\ConcreteCondition\Product;

use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Factory;

/**
 * Class FactoryTest
 */
class FactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Factory
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
     * @var \Magento\AdvancedSalesRule\Model\Rule\Condition\Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productCondition;

    /**
     * Setup the test
     */
    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $className = \Magento\Framework\ObjectManagerInterface::class;
        $this->objectManagerInterface = $this->createMock($className);

        $className = \Magento\AdvancedSalesRule\Model\Rule\Condition\Product::class;
        $this->productCondition = $this->createPartialMock(
            $className,
            ['get', 'getOperator', 'getAttribute', 'getValueParsed']
        );

        $this->model = $this->objectManager->getObject(
            \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Factory::class,
            [
                'objectManager' => $this->objectManagerInterface,
            ]
        );
    }

    /**
     * test Create Category
     */
    public function testCreateCategory()
    {
        $this->productCondition->expects($this->any())
            ->method('getAttribute')
            ->willReturn('category_ids');

        $this->productCondition->expects($this->any())
            ->method('getOperator')
            ->willReturn('==');

        $this->productCondition->expects($this->any())
            ->method('getValueParsed')
            ->willReturn([3, 4, 5]);

        $this->objectManagerInterface->expects($this->any())
            ->method('create')
            ->with(
                \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Categories::class,
                $this->arrayHasKey('data')
            )
            ->willReturn(
                $this->createMock(
                    \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Categories::class
                )
            );

        $object = $this->model->create($this->productCondition);
        $this->assertNotNull($object);
    }

    /**
     * test Create Default
     * @param string $attribute
     * @dataProvider createDefaultDataProvider
     */
    public function testCreateDefault($attribute)
    {
        $this->productCondition->expects($this->any())
            ->method('getAttribute')
            ->willReturn($attribute);

        $this->productCondition->expects($this->any())
            ->method('getOperator')
            ->willReturn('==');

        $this->objectManagerInterface->expects($this->any())
            ->method('create')
            ->with(\Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\DefaultCondition::class)
            ->willReturn(
                $this->createMock(
                    \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\DefaultCondition::class
                )
            );

        $object = $this->model->create($this->productCondition);
        $this->assertNotNull($object);
    }

    /**
     * @return array
     */
    public function createDefaultDataProvider()
    {
        return [
            'quote_item_qty' => ['quote_item_qty'],
            'quote_item_price' => ['quote_item_price'],
            'quote_item_row_total' => ['quote_item_row_total'],
        ];
    }

    /**
     * test Create Attribute
     */
    public function testCreateAttribute()
    {
        $this->productCondition->expects($this->any())
            ->method('getAttribute')
            ->willReturn('sku');

        $this->productCondition->expects($this->any())
            ->method('getOperator')
            ->willReturn('==');

        $this->objectManagerInterface->expects($this->any())
            ->method('create')
            ->with(
                \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Attribute::class,
                ['condition' => $this->productCondition]
            )
            ->willReturn(
                $this->createMock(
                    \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Attribute::class
                )
            );

        $object = $this->model->create($this->productCondition);
        $this->assertNotNull($object);
    }
}
