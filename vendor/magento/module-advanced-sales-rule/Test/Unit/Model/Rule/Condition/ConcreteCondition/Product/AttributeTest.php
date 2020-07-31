<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\ConcreteCondition\Product;

use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Attribute;

/**
 * Class AttributeTest
 */
class AttributeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Attribute
     */
    protected $model;

    /**
     * @var \Magento\AdvancedRule\Model\Condition\FilterGroupInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filterGroupFactory;

    /**
     * @var \Magento\AdvancedRule\Helper\Filter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filterHelper;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Rule\Model\Condition\AbstractCondition|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $abstractCondition;

    /**
     * Setup the test
     */
    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $className = \Magento\AdvancedRule\Model\Condition\FilterGroupInterfaceFactory::class;
        $this->filterGroupFactory = $this->createPartialMock($className, ['create']);

        $className = \Magento\AdvancedRule\Helper\Filter::class;
        $this->filterHelper = $this->createMock($className);

        $className = \Magento\Rule\Model\Condition\AbstractCondition::class;
        $this->abstractCondition = $this->getMockForAbstractClass($className, [], '', false);
    }

    /**
     * test testIsFilterable
     * @param string $attribute
     * @param string $operator
     * @param array|object|null $valueParsed
     * @param bool $expected
     * @dataProvider isFilterableDataProvider
     */
    public function testIsFilterable($attribute, $operator, $valueParsed, $expected)
    {
        $this->abstractCondition->setData('attribute', $attribute);
        $this->abstractCondition->setData('operator', $operator);
        $this->abstractCondition->setData('value_parsed', $valueParsed);

        $this->model = $this->objectManager->getObject(
            \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Attribute::class,
            [
                'filterGroupFactory' => $this->filterGroupFactory,
                'filterHelper' => $this->filterHelper,
                'condition' => $this->abstractCondition,
            ]
        );

        $this->assertEquals($expected, $this->model->isFilterable());
    }

    /**
     * @return array
     */
    public function isFilterableDataProvider()
    {
        return [
            'array_val_equal_not_filterable' => ['sku', '==', [3], false],
            'obj_val_equal_not_filterable' => ['sku', '==', new \stdClass, false],
            'null_val_equal_not_filterable' => ['sku', '==', null, false],
            'string_val_equal_filterable' => ['sku', '==', 'string', true],

            'array_val_not_equal_not_filterable' => ['sku', '!=', [3], false],
            'obj_val_not_equal_not_filterable' => ['sku', '!=', new \stdClass, false],
            'null_val_not_equal_not_filterable' => ['sku', '!=', null, false],
            'string_val_not_equal_not_filterable' => ['sku', '!=', 'string', false],

            'string_val_greater_equal_not_filterable' => ['sku', '>=', 'string', false],
            'array_val_greater_equal_not_filterable' => ['sku', '>=', [3], false],
        ];
    }

    /**
     * test GetFilterGroups
     * @param string $operator
     * @dataProvider getFilterGroupsDataProvider
     */
    public function testGetFilterGroups($operator)
    {
        $this->abstractCondition->setData('operator', $operator);
        $this->abstractCondition->setData('attribute', 'sku');
        $this->abstractCondition->setData('value_parsed', '1');

        $this->model = $this->objectManager->getObject(
            \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Attribute::class,
            [
                'filterGroupFactory' => $this->filterGroupFactory,
                'filterHelper' => $this->filterHelper,
                'condition' => $this->abstractCondition,
            ]
        );

        $className = \Magento\AdvancedRule\Model\Condition\Filter::class;
        $filter =
            $this->createPartialMock($className, ['setFilterText', 'setWeight', 'setFilterTextGeneratorClass']);

        //test getFilterTextPrefix
        $filter->expects($this->any())
            ->method('setFilterText')
            ->with('product:attribute:sku:1')
            ->willReturnSelf();

        $filter->expects($this->any())
            ->method('setWeight')
            ->willReturnSelf();

        //test getFilterTextGeneratorClass
        $filter->expects($this->any())
            ->method('setFilterTextGeneratorClass')
            ->with(\Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Attribute::class)
            ->willReturnSelf();

        $className = \Magento\AdvancedRule\Model\Condition\FilterGroup::class;
        $filterGroup = $this->createMock($className);

        $this->filterHelper->expects($this->once())
            ->method('createFilter')
            ->willReturn($filter);

        $this->filterGroupFactory->expects($this->any())
            ->method('create')
            ->willReturn($filterGroup);

        if ($operator == '==') {
            $return = $this->model->getFilterGroups();
            $this->assertTrue(is_array($return));
            $this->assertSame([$filterGroup], $return);
        } elseif ($operator == '!=') {
            $this->filterHelper->expects($this->any())
                ->method('negateFilter')
                ->with($filter);

            $return = $this->model->getFilterGroups();
            $this->assertTrue(is_array($return));
            $this->assertNotSame([$filterGroup], $return);
        }

        //test caching if (create should be called only once)
        $this->model->getFilterGroups();
    }

    /**
     * @return array
     */
    public function getFilterGroupsDataProvider()
    {
        return [
            'equal' => ['=='],
            'not_equal' => ['!='],
            'greater_than' => ['>=']
        ];
    }
}
