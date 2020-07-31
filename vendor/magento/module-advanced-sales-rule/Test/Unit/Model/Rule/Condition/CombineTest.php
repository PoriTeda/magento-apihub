<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition;

use Magento\AdvancedSalesRule\Model\Rule\Condition\Combine;

/**
 * Class CombineTest
 */
class CombineTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedSalesRule\Model\Rule\Condition\Combine
     */
    protected $model;

    /**
     * @var \Magento\Rule\Model\Condition\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManager;

    /**
     * @var \Magento\SalesRule\Model\Rule\Condition\Address|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $conditionAddress;

    /**
     * @var \Magento\AdvancedRule\Helper\CombineCondition|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $conditionHelper;

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

        $className = \Magento\Framework\Event\ManagerInterface::class;
        $this->eventManager = $this->createMock($className);

        $className = \Magento\SalesRule\Model\Rule\Condition\Address::class;
        $this->conditionAddress = $this->createMock($className);

        $className = \Magento\AdvancedRule\Helper\CombineCondition::class;
        $this->conditionHelper = $this->createMock($className);

        $this->model = $this->objectManager->getObject(
            \Magento\AdvancedSalesRule\Model\Rule\Condition\Combine::class,
            [
                'context' => $this->context,
                'eventManager' => $this->eventManager,
                'conditionAddress' => $this->conditionAddress,
                'conditionHelper' => $this->conditionHelper,
            ]
        );
    }

    /**
     * test IsFilterable
     * @param bool $value
     * @param string $aggregator
     * @param string $expect
     * @param string $return
     * @param string $result
     * @dataProvider isFilterableDataProvider
     */
    public function testIsFilterable($value, $aggregator, $expect, $return, $result)
    {
        $this->model->setValue($value);
        $this->model->setAggregator($aggregator);

        $this->conditionHelper->expects($this->any())
            ->method($expect)
            ->willReturn($return);

        $this->assertEquals($result, $this->model->isFilterable());
    }

    /**
     * @return array
     */
    public function isFilterableDataProvider()
    {
        return [
            'all_has_filterable_cond_filterable' => [true, 'all', 'hasFilterableCondition', true, true],
            'all_has_filterable_cond_non_filterable' => [false, 'all', 'hasFilterableCondition', false, false],
            'none_has_non_filterable_cond_filterable' => [true, 'none', 'hasNonFilterableCondition', false, true],
            'none_has_non_filterable_cond_non_filterable' => [false, 'none', 'hasNonFilterableCondition', false, false],
        ];
    }

    /**
     * test GetFilterGroups
     * @param string $aggregator
     * @param string $expect
     * @dataProvider getFilterGroupsDataProvider
     */
    public function testGetFilterGroups($aggregator, $expect)
    {
        $className = \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface::class;
        $interface =$this->createMock($className);

        $this->model->setAggregator($aggregator);

        $this->conditionHelper->expects($this->any())
            ->method($expect)
            ->willReturn($interface);

        $this->assertSame($interface, $this->model->getFilterGroups());
    }

    /**
     * @return array
     */
    public function getFilterGroupsDataProvider()
    {
        return [
            'all_logical_and_conditions' => ['all', 'logicalAndConditions'],
            'none_logical_or_conditions'=> ['none', 'logicalOrConditions']
        ];
    }
}
