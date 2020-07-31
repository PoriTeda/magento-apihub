<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\ConcreteCondition;

use Magento\CustomerSegment\Model\Segment\Condition\ConcreteCondition\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\ConcreteCondition\Factory;

/**
 * Class SegmentTest
 */
class SegmentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedRule\Model\Condition\FilterGroupInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filterGroupFactory;

    /**
     * @var \Magento\AdvancedRule\Helper\Filter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filterHelper;

    /**
     * @var int
     */
    protected $filterCounter = 0;

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
     * test isFilterable
     *
     * @param string $operator
     * @param bool $expected
     * @dataProvider isFilterableDataProvider
     */
    public function testIsFilterable($operator, $expected)
    {
        $data = ['operator' => $operator, 'values' => 'n/a'];
        $model = $this->objectManager->getObject(
            Factory::CONCRETE_CONDITION_CLASS,
            [
                'data' => $data,
            ]
        );

        $this->assertEquals($expected, $model->isFilterable());
    }

    /**
     * @return array
     */
    public function isFilterableDataProvider()
    {
        return [
            'matches'        => ['==', true],
            'is_one_of'      => ['()', true],
            'does_not_match' => ['!=', true],
            'is_not_one_of'  => ['!()', true],
            'greater_or_equal_to' => ['>=', false],
            'lesser_or_equal_to'  => ['<=', false],
        ];
    }

    /**
     * test getFilterGroups
     *
     * @param string $operator
     * @param string $values - comma delimited
     * @param int $expectedCount
     * @dataProvider getFilterGroupsDataProvider
     */
    public function testGetFilterGroups($operator, $values, $expectedCount)
    {
        $data = ['operator' => $operator, 'values' => $values];
        $model = $this->objectManager->getObject(
            Factory::CONCRETE_CONDITION_CLASS,
            [
                'filterGroupFactory' => $this->filterGroupFactory,
                'filterHelper' => $this->filterHelper,
                'data' => $data,
            ]
        );

        // flesh out the test environment
        $this->buildMocks($operator, $values);

        // test 1st time
        $filterGroups = $model->getFilterGroups();
        $this->assertNotEmpty($filterGroups);
        $this->assertEquals(
            $expectedCount,
            count($filterGroups),
            'Expected "filterGroups[]" to have ' . $expectedCount . ' elements'
        );

        // test caching (since certain methods should only be called once)
        $model->getFilterGroups();
    }

    /**
     * @return array
     */
    public function getFilterGroupsDataProvider()
    {
        return [
            'matches_with_single_value' => [
                '==',
                '12',
                1,
            ],

            'does_not_match_with_single_value' => [
                '!=',
                '13',
                1,
            ],

            'is_one_of_with_multi_values' => [
                '()',
                '41, 42',
                2,
            ],

            'is_not_one_of_with_multi_values' => [
                '!()',
                '99,100,101',
                1,
            ],

            'matches_with_multi_values' => [
                '==',
                '21,  22,  23,  24,  25',
                5,
            ],

            'does_not_match_with_multi_values' => [
                '!=',
                '36, 37, 38, 39',
                1,
            ],
        ];
    }

    /**
     * @param string $operator
     * @param string $values - comma delimited
     */
    protected function buildMocks($operator, $values)
    {
        // determine basic expected runtime values
        $haveNegativeOperator = (substr($operator, 0, 1) === '!') ? true : false;
        $segmentIds = explode(',', str_replace(' ', '', $values));

        $weight = 1;
        if ($haveNegativeOperator) {
            $weight = -1;
        }

        // create the filter mocks
        $filters = [];
        foreach ($segmentIds as $id) {
            $className = \Magento\AdvancedRule\Model\Condition\Filter::class;
            $filter = $this->createPartialMock(
                $className,
                ['setFilterText', 'setWeight', 'setFilterTextGeneratorClass']
            );
            $filter->expects($this->once())
                ->method('setFilterText')
                ->with(Segment::FILTER_TEXT_PREFIX . $id)
                ->willReturnSelf();
            $filter->expects($this->once())
                ->method('setWeight')
                ->with($weight)
                ->willReturnSelf();
            $filter->expects($this->once())
                ->method('setFilterTextGeneratorClass')
                ->with(SEGMENT::FILTER_TEXT_GENERATOR_CLASS)
                ->willReturnSelf();
            $filters[] = $filter;
        }

        // returns the next filter
        $this->filterCounter = 0;
        $this->filterHelper->expects($this->atLeastOnce())
            ->method('createFilter')
            ->will($this->returnCallback(
                function () use ($filters) {
                    return $filters[$this->filterCounter++];
                }
            ));

        // for a negative operator, also mock out a 'true' filter
        if ($haveNegativeOperator) {
            $className = \Magento\AdvancedRule\Model\Condition\Filter::class;
            $trueFilter = $this->createMock($className);
            $this->filterHelper->expects($this->once())
                ->method('getFilterTrue')
                ->willReturn($trueFilter);
        }

        // mock out a filter group
        $className = \Magento\AdvancedRule\Model\Condition\FilterGroup::class;
        $filterGroup = $this->createPartialMock($className, ['setFilters']);
        if ($haveNegativeOperator) {
            $filterGroup->expects($this->once())
                ->method('setFilters')
                ->willReturnSelf();
            $this->filterGroupFactory->expects($this->once())
                ->method('create')
                ->willReturn($filterGroup);
        } else {
            $filterGroup->expects($this->atLeastOnce())
                ->method('setFilters')
                ->willReturnSelf();
            $this->filterGroupFactory->expects($this->atLeastOnce())
                ->method('create')
                ->willReturn($filterGroup);
        }
    }
}
