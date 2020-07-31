<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\ConcreteCondition\Address;

use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\CountryId;

/**
 * Class CountryIdTest
 */
class CountryIdTest extends \PHPUnit\Framework\TestCase
{
    const CLASS_NAME = \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\CountryId::class;

    const EXPECTED_CLASS_NAME =
        \Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Address\CountryId::class;

    /**
     * @var CountryId
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
            self::CLASS_NAME,
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
            'array_value_equal_not_filterable' => ['country_id', '==', [3], false],
            'obj_value_equal_not_filterable' => ['country_id', '==', new \stdClass, false],
            'null_equal_not_filterable' => ['country_id', '==', null, false],
            'string_equal_value_filterable' => ['country_id', '==', 'string', true],

            'array_value_not_equal_not_filterable' => ['country_id', '!=', [3], false],
            'obj_value_not_equal_not_filterable' => ['country_id', '!=', new \stdClass, false],
            'null_value_not_equal_not_filterable' => ['country_id', '!=', null, false],
            'string_value_not_equal_filterable' => ['country_id', '!=', 'string', true],

            'string_value_greater_equal_not_filterable' => ['country_id', '>=', 'string', false],
            'array_value_greater_equal_not_filterable' => ['country_id', '>=', [3], false]
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
        $this->abstractCondition->setData('attribute', 'address');
        $this->abstractCondition->setData('value_parsed', '1');

        $this->model = $this->objectManager->getObject(
            self::CLASS_NAME,
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
            ->with('quote_address:address:1')
            ->willReturnSelf();

        $filter->expects($this->any())
            ->method('setWeight')
            ->willReturnSelf();

        //test getFilterTextGeneratorClass
        $filter->expects($this->any())
            ->method('setFilterTextGeneratorClass')
            ->with(self::EXPECTED_CLASS_NAME)
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
