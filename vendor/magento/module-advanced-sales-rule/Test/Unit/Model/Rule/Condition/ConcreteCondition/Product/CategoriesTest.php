<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\ConcreteCondition\Product;

use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Categories;

/**
 * Class CategoriesTest
 */
class CategoriesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Categories
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
     * @var array
     */
    protected $data;

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

        $className = \Magento\AdvancedRule\Model\Condition\FilterGroupInterfaceFactory::class;
        $this->filterGroupFactory = $this->createPartialMock($className, ['create']);

        $className = \Magento\AdvancedRule\Helper\Filter::class;
        $this->filterHelper = $this->createMock($className);
    }

    /**
     * test testIsFilterable
     * @param string $operator
     * @param bool $expected
     * @dataProvider isFilterableDataProvider
     */
    public function testIsFilterable($operator, $expected)
    {
        $this->data = ['operator' => $operator, 'categories'=> null];
        $this->model = $this->objectManager->getObject(
            \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Categories::class,
            [
                'filterGroupFactory' => $this->filterGroupFactory,
                'filterHelper' => $this->filterHelper,
                'data' => $this->data,
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
            ['()', true],
            ['==', true],
            ['!=', false],
            ['!()', false],
            ['>=', false],
            ['>=', false],
        ];
    }

    /**
     * test GetFilterGroups
     * @param string $operator
     * @dataProvider getFilterGroupsDataProvider
     */
    public function testGetFilterGroups($operator)
    {
        $this->data = ['operator' => $operator, 'categories'=> [1]];

        $this->model = $this->objectManager->getObject(
            \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Categories::class,
            [
                'filterGroupFactory' => $this->filterGroupFactory,
                'filterHelper' => $this->filterHelper,
                'data' => $this->data,
            ]
        );

        $className = \Magento\AdvancedRule\Model\Condition\Filter::class;
        $filter =
            $this->createPartialMock($className, ['setFilterText', 'setWeight', 'setFilterTextGeneratorClass']);

        //test getFilterTextPrefix
        $filter->expects($this->once())
            ->method('setFilterText')
            ->with('product:category:1')
            ->willReturnSelf();

        $filter->expects($this->any())
            ->method('setWeight')
            ->willReturnSelf();

        //test getFilterTextGeneratorClass
        $filter->expects($this->any())
            ->method('setFilterTextGeneratorClass')
            ->with(\Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Category::class)
            ->willReturnSelf();

        $className = \Magento\AdvancedRule\Model\Condition\FilterGroup::class;
        $filterGroup = $this->createMock($className);

        $this->filterHelper->expects($this->once())
            ->method('createFilter')
            ->willReturn($filter);

        $this->filterGroupFactory->expects($this->once())
            ->method('create')
            ->willReturn($filterGroup);

        $this->filterHelper->expects($this->any())
            ->method('negateFilter')
            ->with($filter);

        $return = $this->model->getFilterGroups();
        $this->assertTrue(is_array($return));
        $this->assertSame([$filterGroup], $return);

        //test caching if (create should be called only once)
        $this->model->getFilterGroups();
    }

    /**
     * @return array
     */
    public function getFilterGroupsDataProvider()
    {

        return [
            'equal' => [
                'operator' => '==',
            ],
            'in' => [
                'operator' => '()',
            ],
        ];
    }

    /**
     * test GetFilterGroups when the condition is not filterable
     * @param string $operator
     * @dataProvider getFilterGroupsNonFilterableDataProvider
     */
    public function testGetFilterGroupsNonFilterable($operator)
    {
        $this->data = ['operator' => $operator, 'categories'=> [1]];

        $this->model = $this->objectManager->getObject(
            \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Categories::class,
            [
                'filterGroupFactory' => $this->filterGroupFactory,
                'filterHelper' => $this->filterHelper,
                'data' => $this->data,
            ]
        );

        $return = $this->model->getFilterGroups();
        $this->assertTrue(is_array($return));
        $this->assertSame([], $return);

        //test caching if (create should be called only once)
        $this->model->getFilterGroups();
    }

    /**
     * @return array
     */
    public function getFilterGroupsNonFilterableDataProvider()
    {
        return [
            'not_equal' => ['!='],
            'not_group' => ['!()'],
            'greater_equal' => ['>='],
        ];
    }
}
