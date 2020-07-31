<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Test\Unit\Model\Indexer\SalesRule\Action;

use Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action\Rows;
use Magento\AdvancedRule\Model\Condition\Filter;

/**
 * Class RowsTest
 */
class RowsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action\Rows
     */
    protected $model;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $ruleCollection;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $ruleFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $filterResourceModel;

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

        $className = \Magento\SalesRule\Model\ResourceModel\Rule\Collection::class;
        $this->ruleCollection = $this->createMock($className);

        $className = \Magento\SalesRule\Model\RuleFactory::class;
        $this->ruleFactory = $this->createPartialMock($className, ['create']);

        $className = \Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter::class;
        $this->filterResourceModel = $this->createMock($className);

        $this->model = $this->objectManager->getObject(
            \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action\Rows::class,
            [
                'ruleCollection' => $this->ruleCollection,
                'ruleFactory' => $this->ruleFactory,
                'filterResourceModel' => $this->filterResourceModel,
            ]
        );
    }

    /**
     * test Execute
     */
    public function testExecuteNoInstance()
    {
        $rows = [1];

        $className = \Magento\SalesRule\Model\Rule::class;
        $rule = $this->createMock($className);

        $this->ruleFactory->expects($this->any())
            ->method('create')
            ->willReturn($rule);

        $rule->expects($this->any())
            ->method('load')
            ->willReturn($rule);

        $rule->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $rule->expects($this->any())
            ->method('getConditions')
            ->willReturn(false);

        $this->filterResourceModel->expects($this->once())
            ->method('deleteRuleFilters')
            ->with([1]);

        $expectArray = [
            'rule_id' => 1,
            'group_id' => 1,
            'weight' => 1,
            Filter::KEY_FILTER_TEXT => 'true',
            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => null,
            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null
        ];

        $this->filterResourceModel->expects($this->once())
            ->method('insertFilters')
            ->with($expectArray);

        $this->model->execute($rows);
    }

    /**
     * test Execute
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithInstance()
    {
        $rows = [1, 2];

        $className = \Magento\SalesRule\Model\Rule::class;
        $rule = $this->createMock($className);

        $this->ruleFactory->expects($this->any())
            ->method('create')
            ->willReturn($rule);

        $rule->expects($this->any())
            ->method('load')
            ->willReturn($rule);

        $rule->expects($this->any())
            ->method('getId')
            ->will($this->onConsecutiveCalls(1, 2));

        $className = \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface::class;
        $conditions = $this->createPartialMock($className, ['isFilterable', 'asArray', 'getFilterGroups']);

        $conditions->expects($this->any())
            ->method('isFilterable')
            ->willReturn(true);

        $rule->expects($this->exactly(2))
            ->method('getConditions')
            ->willReturn($conditions);

        $className = \Magento\AdvancedRule\Model\Condition\FilterGroupInterface::class;
        $filterGroupInterface = $this->createPartialMock(
            $className,
            [
                'getWeight',
                'getFilterText',
                'getFilterTextGeneratorClass',
                'getFilterTextGeneratorArguments',
                'getFilters',
                'setFilters'
            ]
        );

        $filterGroupInterface->expects($this->any())
            ->method('getWeight')
            ->willReturn(3);

        $filterGroupInterface->expects($this->any())
            ->method('getFilterText')
            ->willReturn('class');

        $filterGroupInterface->expects($this->any())
            ->method('getFilterTextGeneratorClass')
            ->willReturn(4);

        $className = \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface::class;
        $filterableConditionInterface = $this->createPartialMock(
            $className,
            ['getFilters', 'isFilterable', 'getFilterGroups']
        );

        $filterableConditionInterface->expects($this->any())
            ->method('getFilters')
            ->willReturn([$filterGroupInterface]);

        $conditions->expects($this->any())
            ->method('getFilterGroups')
            ->willReturn([$filterableConditionInterface, $filterableConditionInterface]);

        $expectArray1 = [
            [
                'rule_id' => 1,
                'group_id' => 1,
                'weight' => 3,
                Filter::KEY_FILTER_TEXT => 'class',
                Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => 4,
                Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null
            ],
            [
                'rule_id' => 1,
                'group_id' => 2,
                'weight' => 3,
                Filter::KEY_FILTER_TEXT => 'class',
                Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => 4,
                Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null
            ]
        ];

        $expectArray2 = [
            [
                'rule_id' => 2,
                'group_id' => 1,
                'weight' => 3,
                Filter::KEY_FILTER_TEXT => 'class',
                Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => 4,
                Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null
            ],
            [
                'rule_id' => 2,
                'group_id' => 2,
                'weight' => 3,
                Filter::KEY_FILTER_TEXT => 'class',
                Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => 4,
                Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null
            ]
        ];

        $this->filterResourceModel->expects($this->any())
            ->method('insertFilters')
            ->withConsecutive([$expectArray1], [$expectArray2]);

        $this->model->execute($rows);
    }

    /**
     * test Execute
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithInstanceNotFilterable()
    {
        $rows = [1, 2, 3];

        $className = \Magento\SalesRule\Model\Rule::class;
        $rule = $this->createMock($className);

        $this->ruleFactory->expects($this->any())
            ->method('create')
            ->willReturn($rule);

        $rule->expects($this->any())
            ->method('load')
            ->willReturn($rule);

        $rule->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $className = \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface::class;
        $conditions = $this->createPartialMock($className, ['isFilterable', 'asArray', 'getFilterGroups']);

        $conditions->expects($this->any())
            ->method('isFilterable')
            ->willReturn(false);

        $rule->expects($this->exactly(3))
            ->method('getConditions')
            ->willReturn($conditions);

        $className = \Magento\AdvancedRule\Model\Condition\FilterGroupInterface::class;
        $filterGroupInterface =$this->createPartialMock(
            $className,
            [
                'getWeight',
                'getFilterText',
                'getFilterTextGeneratorClass',
                'getFilterTextGeneratorArguments',
                'getFilters',
                'setFilters'
            ]
        );

        $filterGroupInterface->expects($this->any())
            ->method('getWeight')
            ->willReturn(3);

        $filterGroupInterface->expects($this->any())
            ->method('getFilterText')
            ->willReturn('class');

        $filterGroupInterface->expects($this->any())
            ->method('getFilterTextGeneratorClass')
            ->willReturn(4);

        $className = \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface::class;
        $filterableConditionInterface = $this->createPartialMock(
            $className,
            ['getFilters', 'isFilterable', 'getFilterGroups']
        );

        $filterableConditionInterface->expects($this->any())
            ->method('getFilters')
            ->willReturn([$filterGroupInterface]);

        $conditions->expects($this->any())
            ->method('getFilterGroups')
            ->willReturn([$filterableConditionInterface, $filterableConditionInterface]);

        $expectArray = [
            'rule_id' => 1,
            'group_id' => 1,
            'weight' => 1,
            Filter::KEY_FILTER_TEXT => 'true',
            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => null,
            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null
        ];

        $this->filterResourceModel->expects($this->exactly(3))
            ->method('insertFilters')
            ->with($expectArray);

        $this->model->execute($rows);
    }
}
