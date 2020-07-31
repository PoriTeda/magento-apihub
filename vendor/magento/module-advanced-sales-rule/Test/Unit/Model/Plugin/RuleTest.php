<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Test\Unit\Model\Plugin;

/**
 * Class RuleTest
 */
class RuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexerProcessorMock;

    /**
     * @var \Magento\AdvancedSalesRule\Model\Plugin\Rule
     */
    protected $model;

    /**
     * @var \Closure
     */
    protected $closureMock;

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

        $className = \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor::class;
        $this->indexerProcessorMock = $this->createMock($className);
        $serializer = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->setMethods(null)
            ->getMock();

        $this->model = $this->objectManager->getObject(
            \Magento\AdvancedSalesRule\Model\Plugin\Rule::class,
            [
                'indexerProcessor' => $this->indexerProcessorMock,
                'serializer' => $serializer,
            ]
        );
    }

    /**
     * test AroundSave when the sales rule is a new object
     */
    public function testAroundSaveNewObject()
    {
        $className = \Magento\SalesRule\Model\Rule::class;
        /** @var \Magento\SalesRule\Model\Rule|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->createMock($className);

        $subject->expects($this->any())
            ->method('isObjectNew')
            ->willReturn(true);

        $subject->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->indexerProcessorMock->expects($this->once())
            ->method('reindexRow')
            ->with(1);

        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $this->assertSame($subject, $this->model->aroundSave($subject, $this->closureMock));
    }

    /**
     * test AroundSave when skip_save_filter flag is set
     */
    public function testAroundSaveSkipAfter()
    {
        $className = \Magento\SalesRule\Model\Rule::class;
        /** @var \Magento\SalesRule\Model\Rule|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->createMock($className);

        $subject->expects($this->once())
            ->method('getData')
            ->with('skip_save_filter')
            ->willReturn(true);

        $subject->expects($this->never())
            ->method('isObjectNew');

        $this->indexerProcessorMock->expects($this->never())
            ->method('reindexRow');

        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $this->assertSame($subject, $this->model->aroundSave($subject, $this->closureMock));
    }

    /**
     * test AroundSave
     */
    public function testAfterSave()
    {
        $className = \Magento\SalesRule\Model\Rule::class;
        /** @var \Magento\SalesRule\Model\Rule|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject =$this->createMock($className);

        $subject->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $className = \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface::class;
        /** @var  \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface $conditions */
        $conditions = $this->createPartialMock($className, ['isFilterable', 'asArray', 'getFilterGroups']);
        $subject->expects($this->any())
            ->method('getConditions')
            ->willReturn($conditions);

        $conditions->expects($this->once())
            ->method('asArray')
            ->willReturn(['a' => 'b']);

        $subject->expects($this->once())
            ->method('isObjectNew')
            ->willReturn(false);
        $subject->expects($this->once())
            ->method('getOrigData')
            ->with('conditions_serialized')
            ->willReturn('abc');

        $this->indexerProcessorMock->expects($this->once())
            ->method('reindexRow')
            ->with(1);

        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $this->assertSame($subject, $this->model->aroundSave($subject, $this->closureMock));
    }

    /**
     * test AroundSave when force_save_filter flag is set
     */
    public function testAfterSaveForced()
    {
        $className = \Magento\SalesRule\Model\Rule::class;
        /** @var \Magento\SalesRule\Model\Rule|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject =$this->createMock($className);

        $subject->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $className = \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface::class;
        /** @var  \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface $conditions */
        $conditions = $this->createPartialMock($className, ['isFilterable', 'asArray', 'getFilterGroups']);
        $subject->expects($this->any())
            ->method('getConditions')
            ->willReturn($conditions);

        $subject->expects($this->once())
            ->method('isObjectNew')
            ->willReturn(false);

        $subject->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturnCallback(
                function ($field) {
                    if ($field == 'skip_save_filter') {
                        return false;
                    } elseif ($field == 'force_save_filter') {
                        return true;
                    } else {
                        return true; // default
                    }
                }
            );

        $this->indexerProcessorMock->expects($this->once())
            ->method('reindexRow')
            ->with(1);

        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $this->assertSame($subject, $this->model->aroundSave($subject, $this->closureMock));
    }

    /**
     * test AroundSave when condition did not change
     */
    public function testAfterSaveConditionNotChanged()
    {
        $className = \Magento\SalesRule\Model\Rule::class;
        /** @var \Magento\SalesRule\Model\Rule|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject =$this->createPartialMock($className, ['getConditionsSerialized', 'getOrigData', 'isObjectNew']);

        $serializedCondition = 'serialized';
        $subject->expects($this->once())
            ->method('isObjectNew')
            ->willReturn(false);
        $subject->expects($this->once())
            ->method('getConditionsSerialized')
            ->willReturn($serializedCondition);
        $subject->expects($this->once())
            ->method('getOrigData')
            ->with('conditions_serialized')
            ->willReturn($serializedCondition);

        $this->indexerProcessorMock->expects($this->never())
            ->method('reindexRow');

        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $this->assertSame($subject, $this->model->aroundSave($subject, $this->closureMock));
    }

    /**
     * test AroundSave when condition did not change
     */
    public function testAfterSaveConditionNotChangedNoSerializedCondition()
    {
        $className = \Magento\SalesRule\Model\Rule::class;
        /** @var \Magento\SalesRule\Model\Rule|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->createPartialMock(
            $className,
            ['getConditionsSerialized', 'getOrigData', 'isObjectNew', 'getConditions']
        );

        $this->setupConditionNotChanged($subject);

        $this->indexerProcessorMock->expects($this->never())
            ->method('reindexRow');

        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $this->assertSame($subject, $this->model->aroundSave($subject, $this->closureMock));
    }

    /**
     * Setup method for testAfterSaveConditionNotChangedNoSerializedCondition
     * @param \Magento\SalesRule\Model\Rule|\PHPUnit_Framework_MockObject_MockObject $subject
     */
    protected function setupConditionNotChanged($subject)
    {
        $conditionArray = ['a' => 'b'];
        $originalConditionArray = ['a' => 'b'];
        $conditionMock = $this->getMockBuilder(\Magento\Rule\Model\Condition\Combine::class)
            ->disableOriginalConstructor()
            ->setMethods(['asArray'])
            ->getMock();
        $conditionMock->expects($this->once())
            ->method('asArray')
            ->willReturn($conditionArray);

        $subject->expects($this->once())
            ->method('isObjectNew')
            ->willReturn(false);
        $subject->expects($this->once())
            ->method('getConditionsSerialized')
            ->willReturn(null);
        $subject->expects($this->once())
            ->method('getOrigData')
            ->with('conditions_serialized')
            ->willReturn(json_encode($originalConditionArray));
        $subject->expects($this->once())
            ->method('getConditions')
            ->willReturn($conditionMock);
    }
}
