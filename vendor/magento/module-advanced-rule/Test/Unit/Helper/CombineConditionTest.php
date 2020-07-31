<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedRule\Test\Unit\Helper;

use Magento\AdvancedRule\Model\Condition\Filter;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\AdvancedRule\Model\Condition\FilterInterface;
use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;

class CombineConditionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedRule\Helper\CombineCondition
     */
    private $combineConditionHelper;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\AdvancedRule\Helper\Filter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filterHelperMock;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->filterHelperMock = $this->getMockBuilder(\Magento\AdvancedRule\Helper\Filter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->combineConditionHelper = $this->objectManager->getObject(
            \Magento\AdvancedRule\Helper\CombineCondition::class,
            [
                'filterHelper' => $this->filterHelperMock,
            ]
        );
    }

    /**
     * @param array $conditions
     * @param bool $expected
     * @dataProvider hasFilterableConditionDataProvider
     */
    public function testHasFilterableCondition(array $conditions, $expected)
    {
        $result = $this->combineConditionHelper->hasFilterableCondition($conditions);
        $this->assertEquals($expected, $result);
    }

    public function hasFilterableConditionDataProvider()
    {
        $data = [
            'three_conditions_one_filterable' => [
                'conditions' => [
                    new \Magento\Framework\DataObject(),
                    $this->getFilterableConditionMock(true),
                    $this->getFilterableConditionMock(false),
                ],
                'expected' => true,
            ],
            'three_conditions_no_filterable' => [
                'conditions' => [
                    new \Magento\Framework\DataObject(),
                    $this->getFilterableConditionMock(false),
                    $this->getFilterableConditionMock(false),
                ],
                'expected' => false,
            ],
        ];
        return $data;
    }

    /**
     * @param array $conditions
     * @param bool $expected
     * @dataProvider hasNonFilterableConditionDataProvider
     */
    public function testHasNonFilterableCondition($conditions, $expected)
    {
        $result = $this->combineConditionHelper->hasNonFilterableCondition($conditions);
        $this->assertEquals($expected, $result);
    }

    public function hasNonFilterableConditionDataProvider()
    {
        $data = [
            'two_conditions_one_filterable' => [
                'conditions' => [
                    new \Magento\Framework\DataObject(),
                    $this->getFilterableConditionMock(true),
                ],
                'expected' => true,
            ],
            'non_filterable_condition' => [
                'conditions' => [
                    $this->getFilterableConditionMock(false),
                    $this->getFilterableConditionMock(true),
                ],
                'expected' => true,
            ],
            'three_conditions_all_filterable' => [
                'conditions' => [
                    $this->getFilterableConditionMock(true),
                    $this->getFilterableConditionMock(true),
                ],
                'expected' => false,
            ],
        ];
        return $data;
    }

    public function testLogicalAndConditions()
    {
        $conditions = [];

        $filterGroups1 = [$this->getFilterGroupMock()];
        $conditions[] = $this->getFilterableConditionMock(true, $filterGroups1);

        $filterGroups2 = [$this->getFilterGroupMock()];
        $conditions[] = $this->getFilterableConditionMock(true, $filterGroups2);

        $returnedFilterGroups = [$this->getFilterGroupMock()];
        $this->filterHelperMock->expects($this->once())
            ->method('logicalAndFilterGroupArray')
            ->with($filterGroups1, $filterGroups2)
            ->willReturn($returnedFilterGroups);
        $result = $this->combineConditionHelper->logicalAndConditions($conditions);
        $this->assertEquals($returnedFilterGroups, $result);
    }

    public function testLogicalOrConditions()
    {
        $conditions = [];

        $filterGroups1 = [$this->getFilterGroupMock()];
        $conditions[] = $this->getFilterableConditionMock(true, $filterGroups1);

        $filterGroups2 = [$this->getFilterGroupMock()];
        $conditions[] = $this->getFilterableConditionMock(true, $filterGroups2);

        $returnedFilterGroups = array_merge($filterGroups1, $filterGroups2);

        $result = $this->combineConditionHelper->logicalOrConditions($conditions);
        $this->assertEquals($returnedFilterGroups, $result);
    }

    protected function getFilterGroupMock()
    {
        return $this->createMock(\Magento\AdvancedRule\Model\Condition\FilterGroupInterface::class);
    }

    protected function getFilterableConditionMock($isFilterable, $filterGroups = null)
    {
        $mock = $this->createMock(\Magento\AdvancedRule\Model\Condition\FilterableConditionInterface::class);

        $mock->expects($this->any())
            ->method('isFilterable')
            ->willReturn($isFilterable);

        $mock->expects($this->any())
            ->method('getFilterGroups')
            ->willReturn($filterGroups);

        return $mock;
    }
}
