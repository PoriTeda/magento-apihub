<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedRule\Test\Unit\Helper;

use Magento\AdvancedRule\Model\Condition\Filter;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\AdvancedRule\Model\Condition\FilterInterfaceFactory;
use Magento\AdvancedRule\Model\Condition\FilterInterface;
use Magento\AdvancedRule\Model\Condition\FilterGroupInterfaceFactory;
use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Category;
use Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Attribute;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedRule\Helper\Filter
     */
    private $filterHelper;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var FilterInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filterInterfaceFactoryMock;

    /**
     * @var FilterGroupInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filterGroupInterfaceFactoryMock;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $objManager = $this->objectManager;

        $this->filterInterfaceFactoryMock = $this->getMockBuilder(
            \Magento\AdvancedRule\Model\Condition\FilterInterfaceFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->filterInterfaceFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnCallback(function () use ($objManager) {
                return $objManager->getObject(\Magento\AdvancedRule\Model\Condition\Filter::class);
            });

        $this->filterGroupInterfaceFactoryMock = $this->getMockBuilder(
            \Magento\AdvancedRule\Model\Condition\FilterGroupInterfaceFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->filterGroupInterfaceFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnCallback(function () use ($objManager) {
                return $objManager->getObject(\Magento\AdvancedRule\Model\Condition\FilterGroup::class);
            });

        $this->filterHelper = $this->objectManager->getObject(
            \Magento\AdvancedRule\Helper\Filter::class,
            [
                'filterInterfaceFactory' => $this->filterInterfaceFactoryMock,
                'filterGroupInterfaceFactory' => $this->filterGroupInterfaceFactoryMock,
            ]
        );
    }

    /**
     * @param array $filterGroupData1
     * @param array $filterGroupData2
     * @param array $expected
     * @dataProvider logicalAndFilterGroupDataProvider
     */
    public function testLogicalAndFilterGroup($filterGroupData1, $filterGroupData2, $expected)
    {
        $filters = [];
        foreach ($filterGroupData1 as $filterData) {
            $filters[] = $this->setupFilter($filterData);
        }
        $filterGroupMock1 = $this->setupFilterGroup($filters);

        $filters = [];
        foreach ($filterGroupData2 as $filterData) {
            $filters[] = $this->setupFilter($filterData);
        }
        $filterGroupMock2 = $this->setupFilterGroup($filters);

        $combinedFilterGroup = $this->filterHelper->logicalAndFilterGroup($filterGroupMock1, $filterGroupMock2);

        $this->verifyFilterGroup($combinedFilterGroup, $expected);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function logicalAndFilterGroupDataProvider()
    {
        $data = [
            'one_positive_one_negative' => [
                //category is 3
                'filter_group_1' => [
                    [
                        Filter::KEY_FILTER_TEXT => 'product:category:3',
                        Filter::KEY_WEIGHT => 1,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS =>
                            \Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Category::class,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                    ],
                ],
                'filter_group_2' => [
                    //sku is NOT simple
                    [
                        Filter::KEY_FILTER_TEXT => 'product:sku:simple',
                        Filter::KEY_WEIGHT => -1,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Attribute::class,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode(['attribute' => 'sku']),
                    ],
                    [
                        Filter::KEY_FILTER_TEXT => 'true',
                        Filter::KEY_WEIGHT => 1,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => null,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null,
                    ],
                ],
                'expected' => [
                    [
                        Filter::KEY_FILTER_TEXT => 'product:category:3',
                        Filter::KEY_WEIGHT => 0.5,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                    ],
                    [
                        Filter::KEY_FILTER_TEXT => 'product:sku:simple',
                        Filter::KEY_WEIGHT => -1,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Attribute::class,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode(['attribute' => 'sku']),
                    ],
                    [
                        Filter::KEY_FILTER_TEXT => 'true',
                        Filter::KEY_WEIGHT => 0.5,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => null,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null,
                    ],

                ] ,
            ],
            'one_positive_one_negative_same_filter_text' => [
                //category is 3
                'filter_group_1' => [
                    [
                        Filter::KEY_FILTER_TEXT => 'product:category:3',
                        Filter::KEY_WEIGHT => 1,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                    ],
                ],
                'filter_group_2' => [
                    [
                        Filter::KEY_FILTER_TEXT => 'product:category:3',
                        Filter::KEY_WEIGHT => -1,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Attribute::class,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode(['attribute' => 'sku']),
                    ],
                    [
                        Filter::KEY_FILTER_TEXT => 'true',
                        Filter::KEY_WEIGHT => 1,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => null,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null,
                    ],
                ],
                'expected' => [
                    [
                        Filter::KEY_FILTER_TEXT => 'true',
                        Filter::KEY_WEIGHT => -1,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => null,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null,
                    ],
                ] ,
            ],
            'two_positive_same_filter_text' => [
                //category is 3
                'filter_group_1' => [
                    [
                        Filter::KEY_FILTER_TEXT => 'product:category:3',
                        Filter::KEY_WEIGHT => 1,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                    ],
                ],
                'filter_group_2' => [
                    [
                        Filter::KEY_FILTER_TEXT => 'product:category:3',
                        Filter::KEY_WEIGHT => 1,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                    ],
                ],
                'expected' => [
                    [
                        Filter::KEY_FILTER_TEXT => 'product:category:3',
                        Filter::KEY_WEIGHT => 1,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                    ],
                ] ,
            ],
            'two_positives' => [
                //category is 3
                'filter_group_1' => [
                    [
                        Filter::KEY_FILTER_TEXT => 'product:category:3',
                        Filter::KEY_WEIGHT => 1,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                    ],
                ],
                'filter_group_2' => [
                    //sku is simple
                    [
                        Filter::KEY_FILTER_TEXT => 'product:sku:simple',
                        Filter::KEY_WEIGHT => 1,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Attribute::class,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode(['attribute' => 'sku']),
                    ],
                ],
                'expected' => [
                    [
                        Filter::KEY_FILTER_TEXT => 'product:category:3',
                        Filter::KEY_WEIGHT => 0.5,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                    ],
                    [
                        Filter::KEY_FILTER_TEXT => 'product:sku:simple',
                        Filter::KEY_WEIGHT => 0.5,
                        Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Attribute::class,
                        Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode(['attribute' => 'sku']),
                    ],
                ] ,
            ],
        ];
        return $data;
    }

    /**
     * @param array $filterGroupArray1
     * @param array $filterGroupArray2
     * @param array $expected
     * @dataProvider logicalAndFilterGroupArrayDataProvider
     */
    public function testLogicalAndFilterGroupArray(array $filterGroupArray1, array $filterGroupArray2, array $expected)
    {
        $filterGroups1 = [];
        foreach ($filterGroupArray1 as $filterGroupData) {
            $filters = [];
            foreach ($filterGroupData as $filterData) {
                $filters[] = $this->setupFilter($filterData);
            }
            $filterGroups1[] = $this->setupFilterGroup($filters);
        }

        $filterGroups2 = [];
        foreach ($filterGroupArray2 as $filterGroupData) {
            $filters = [];
            foreach ($filterGroupData as $filterData) {
                $filters[] = $this->setupFilter($filterData);
            }
            $filterGroups2[] = $this->setupFilterGroup($filters);
        }

        $combinedFilterGroups = $this->filterHelper->logicalAndFilterGroupArray($filterGroups1, $filterGroups2);
        $this->assertEquals(count($expected), count($combinedFilterGroups));
        for ($i = 0; $i < count($combinedFilterGroups); $i++) {
            $this->verifyFilterGroup($combinedFilterGroups[$i], $expected[$i]);
        }
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function logicalAndFilterGroupArrayDataProvider()
    {
        $data = [
            'two_filter_groups' => [
                'filter_groups_1' => [
                    [
                        [
                            Filter::KEY_FILTER_TEXT => 'product:category:3',
                            Filter::KEY_WEIGHT => 1,
                            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                        ]
                    ],
                    [
                        [
                            Filter::KEY_FILTER_TEXT => 'product:category:4',
                            Filter::KEY_WEIGHT => 1,
                            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                        ]
                    ],
                ],
                'filter_groups_2' => [
                    [
                        [
                            Filter::KEY_FILTER_TEXT => 'product:category:5',
                            Filter::KEY_WEIGHT => 1,
                            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                        ]
                    ],
                    [
                        [
                            Filter::KEY_FILTER_TEXT => 'product:category:6',
                            Filter::KEY_WEIGHT => 1,
                            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                        ]
                    ],
                ],
                'expected' => [
                    [
                        [
                            Filter::KEY_FILTER_TEXT => 'product:category:3',
                            Filter::KEY_WEIGHT => 0.5,
                            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                        ],
                        [
                            Filter::KEY_FILTER_TEXT => 'product:category:5',
                            Filter::KEY_WEIGHT => 0.5,
                            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                        ]
                    ],
                    [
                        [
                            Filter::KEY_FILTER_TEXT => 'product:category:3',
                            Filter::KEY_WEIGHT => 0.5,
                            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                        ],
                        [
                            Filter::KEY_FILTER_TEXT => 'product:category:6',
                            Filter::KEY_WEIGHT => 0.5,
                            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                        ]
                    ],
                    [
                        [
                            Filter::KEY_FILTER_TEXT => 'product:category:4',
                            Filter::KEY_WEIGHT => 0.5,
                            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                        ],
                        [
                            Filter::KEY_FILTER_TEXT => 'product:category:5',
                            Filter::KEY_WEIGHT => 0.5,
                            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                        ]
                    ],
                    [
                        [
                            Filter::KEY_FILTER_TEXT => 'product:category:4',
                            Filter::KEY_WEIGHT => 0.5,
                            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                        ],
                        [
                            Filter::KEY_FILTER_TEXT => 'product:category:6',
                            Filter::KEY_WEIGHT => 0.5,
                            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Category::class,
                            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode([]),
                        ]
                    ],
                ],
            ],
        ];

        return $data;
    }

    public function testGetFilterGroupFalse()
    {
        $filterGroup = $this->filterHelper->getFilterGroupFalse();

        $this->verifyFilterGroup(
            $filterGroup,
            [
                [
                    Filter::KEY_FILTER_TEXT => 'true',
                    Filter::KEY_WEIGHT => -1,
                    Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => null,
                    Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null,
                ]
            ]
        );
    }

    public function testGetFilterTrue()
    {
        $filter = $this->filterHelper->getFilterTrue();
        $this->verifyFilter(
            $filter,
            [
                Filter::KEY_FILTER_TEXT => 'true',
                Filter::KEY_WEIGHT => 1,
                Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => null,
                Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null,
            ]
        );
    }

    public function testGetFilterFalse()
    {
        $filter = $this->filterHelper->getFilterFalse();
        $this->verifyFilter(
            $filter,
            [
                Filter::KEY_FILTER_TEXT => 'true',
                Filter::KEY_WEIGHT => -1,
                Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => null,
                Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null,
            ]
        );
    }

    public function testNegateFilter()
    {
        $filter = $this->setupFilter(
            [
                Filter::KEY_FILTER_TEXT => 'product:sku:simple',
                Filter::KEY_WEIGHT => 1,
                Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Attribute::class,
                Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode(['attribute' => 'sku']),
            ]
        );

        $filterGroup = $this->filterHelper->negateFilter($filter);
        $this->verifyFilterGroup(
            $filterGroup,
            [
                [
                    Filter::KEY_FILTER_TEXT => 'product:sku:simple',
                    Filter::KEY_WEIGHT => -1,
                    Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => Attribute::class,
                    Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode(['attribute' => 'sku']),
                ],
                [
                    Filter::KEY_FILTER_TEXT => 'true',
                    Filter::KEY_WEIGHT => 1,
                    Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => null,
                    Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null,
                ]
            ]
        );
    }

    protected function verifyFilterGroup(FilterGroupInterface $filterGroup, array $filtersData)
    {
        $filters = array_values($filterGroup->getFilters());
        $this->assertEquals(count($filters), count($filtersData));

        for ($i = 0; $i < count($filters); $i++) {
            $this->verifyFilter($filters[$i], $filtersData[$i]);
        }
    }

    protected function verifyFilter(FilterInterface $filter, array $filterData)
    {
        $this->assertEquals($filter->getFilterText(), $filterData[Filter::KEY_FILTER_TEXT]);
        $this->assertEquals($filter->getWeight(), $filterData[Filter::KEY_WEIGHT]);
        $this->assertEquals(
            $filter->getFilterTextGeneratorClass(),
            $filterData[Filter::KEY_FILTER_TEXT_GENERATOR_CLASS]
        );
        $this->assertEquals(
            $filter->getFilterTextGeneratorArguments(),
            $filterData[Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS]
        );
    }

    protected function setupFilterGroup($filters)
    {
        /** @var \Magento\AdvancedRule\Model\Condition\FilterGroup $filterGroup */
        $filterGroup = $this->objectManager->getObject(\Magento\AdvancedRule\Model\Condition\FilterGroup::class);
        $filterGroup->setFilters($filters);

        return $filterGroup;
    }

    /**
     * @param array $filterData
     * @return \Magento\AdvancedRule\Model\Condition\Filter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function setupFilter(array $filterData)
    {
        $filter = $this->objectManager->getObject(
            \Magento\AdvancedRule\Model\Condition\Filter::class,
            [
                'data' => $filterData
            ]
        );
        return $filter;
    }
}
