<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\ForeignKey\Test\Unit;

use Magento\Framework\ForeignKey\ConstraintProcessor;

class ConstraintProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConstraintProcessor
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $transactionManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $constraintMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $constraintConnectionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $strategyMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $selectMock;

    /**
     * @var array
     */
    protected $involvedData;

    protected function setUp()
    {
        $this->involvedData = [
            'item' => ['reference_field' => 'value']
        ];

        $this->transactionManagerMock =
            $this->createMock(\Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface::class);
        $this->constraintMock = $this->createMock(\Magento\Framework\ForeignKey\ConstraintInterface::class);
        $this->constraintConnectionMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->strategyMock = $this->createMock(\Magento\Framework\ForeignKey\StrategyInterface::class);
        $this->selectMock = $this->createMock(\Magento\Framework\DB\Select::class);

        $this->model = new \Magento\Framework\ForeignKey\ConstraintProcessor(['strategy' => $this->strategyMock]);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage The "strategy" strategy code is unknown. Verify the code and try again.
     */
    public function testResolveWithException()
    {
        $this->model = new \Magento\Framework\ForeignKey\ConstraintProcessor([]);
        $this->constraintMock->expects($this->once())->method('getStrategy')->willReturn('strategy');
        $this->model->resolve($this->transactionManagerMock, $this->constraintMock, $this->involvedData);
    }

    public function testResolveWithEmptySubConstraints()
    {
        $this->constraintMock->expects($this->once())->method('getStrategy')->willReturn('strategy');
        $this->constraintMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->constraintConnectionMock);
        $this->constraintMock->expects($this->once())->method('getTableName')->willReturn('table_name');
        $this->constraintMock->expects($this->once())->method('getReferenceField')->willReturn('reference_field');
        $this->constraintMock->expects($this->once())
            ->method('getCondition')
            ->with(['value'])
            ->willReturn('constraint_condition');
        $this->constraintMock->expects($this->once())->method('getSubConstraints');
        $this->transactionManagerMock->expects($this->once())
            ->method('start')
            ->with($this->constraintConnectionMock)
            ->willReturn($this->constraintConnectionMock);
        $this->strategyMock->expects($this->once())
            ->method('process')
            ->with($this->constraintConnectionMock, $this->constraintMock, 'constraint_condition');
        $this->model->resolve($this->transactionManagerMock, $this->constraintMock, $this->involvedData);
    }

    public function testResolveWithEmptyLockedData()
    {
        $this->constraintMock->expects($this->once())->method('getStrategy')->willReturn('strategy');
        $this->constraintMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->constraintConnectionMock);
        $this->constraintMock->expects($this->once())->method('getTableName')->willReturn('table_name');
        $this->constraintMock->expects($this->once())->method('getReferenceField')->willReturn('reference_field');
        $this->constraintMock->expects($this->once())
            ->method('getCondition')
            ->with(['value'])
            ->willReturn('constraint_conditions');
        $this->constraintMock->expects($this->once())->method('getSubConstraints')->willReturnSelf();
        $this->transactionManagerMock->expects($this->once())
            ->method('start')
            ->with($this->constraintConnectionMock)
            ->willReturn($this->constraintConnectionMock);
        $this->constraintMock->expects($this->once())
            ->method('getSubConstraintsAffectedFields')
            ->willReturn(['SubConstraintsAffectedFields']);
        $this->strategyMock->expects($this->once())
            ->method('lockAffectedData')
            ->with(
                $this->constraintConnectionMock,
                'table_name',
                'constraint_conditions',
                ['SubConstraintsAffectedFields']
            );
        $this->strategyMock->expects($this->never())->method('process');
        $this->model->resolve($this->transactionManagerMock, $this->constraintMock, $this->involvedData);
    }

    public function testResolve()
    {
        $this->constraintMock->expects($this->once())->method('getStrategy')->willReturn('strategy');
        $this->constraintMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->constraintConnectionMock);
        $this->constraintMock->expects($this->once())->method('getTableName')->willReturn('table_name');
        $this->constraintMock->expects($this->once())->method('getReferenceField')->willReturn('reference_field');
        $this->constraintMock->expects($this->once())
            ->method('getCondition')
            ->with(['value'])
            ->willReturn('constraint_conditions');
        $this->constraintMock->expects($this->once())->method('getSubConstraints')->willReturnSelf();
        $this->transactionManagerMock->expects($this->once())
            ->method('start')
            ->with($this->constraintConnectionMock)
            ->willReturn($this->constraintConnectionMock);
        $this->constraintMock->expects($this->once())
            ->method('getSubConstraintsAffectedFields')
            ->willReturn(['SubConstraintsAffectedFields']);
        $this->strategyMock->expects($this->once())
            ->method('lockAffectedData')
            ->with(
                $this->constraintConnectionMock,
                'table_name',
                'constraint_conditions',
                ['SubConstraintsAffectedFields']
            )->willReturn(['locked_data']);
        $this->strategyMock->expects($this->once())
            ->method('process')
            ->with($this->constraintConnectionMock, $this->constraintMock, 'constraint_conditions');
        $this->model->resolve($this->transactionManagerMock, $this->constraintMock, $this->involvedData);
    }

    public function testValidateWithNullValue()
    {
        $data = ['null' => null];
        $this->constraintMock->expects($this->once())->method('getReferenceField')->willReturn('reference_field');
        $this->constraintMock->expects($this->once())->method('getFieldName')->willReturn('null');
        $this->model->validate($this->constraintMock, $data);
    }

    public function testValidate()
    {
        $data = ['data' => 'value'];
        $this->constraintMock->expects($this->once())->method('getReferenceField')->willReturn('reference_field');
        $this->constraintMock->expects($this->exactly(2))->method('getFieldName')->willReturn('data');
        $this->constraintMock->expects($this->once())
            ->method('getReferenceConnection')
            ->willReturn($this->constraintConnectionMock);
        $this->constraintConnectionMock->expects($this->once())->method('select')->willReturn($this->selectMock);
        $this->constraintMock->expects($this->once())
            ->method('getReferenceTableName')
            ->willReturn('reference_table_name');
        $this->selectMock->expects($this->once())
            ->method('from')
            ->with('reference_table_name')
            ->willReturn($this->selectMock);
        $this->selectMock->expects($this->once())->method('columns')->with(['reference_field'])->willReturnSelf();
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('reference_field' . ' = ?', 'value')
            ->willReturnSelf();
        $this->constraintConnectionMock->expects($this->once())
            ->method('fetchAssoc')
            ->with($this->selectMock)
            ->willReturn(['not empty result']);
        $this->model->validate($this->constraintMock, $data);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testValidateException()
    {
        $data = ['data' => 'value'];
        $this->constraintMock->expects($this->once())->method('getReferenceField')->willReturn('reference_field');
        $this->constraintMock->expects($this->exactly(2))->method('getFieldName')->willReturn('data');
        $this->constraintMock->expects($this->once())
            ->method('getReferenceConnection')
            ->willReturn($this->constraintConnectionMock);
        $this->constraintConnectionMock->expects($this->once())->method('select')->willReturn($this->selectMock);
        $this->constraintMock->expects($this->once())
            ->method('getReferenceTableName')
            ->willReturn('reference_table_name');
        $this->selectMock->expects($this->once())
            ->method('from')
            ->with('reference_table_name')
            ->willReturn($this->selectMock);
        $this->selectMock->expects($this->once())->method('columns')->with(['reference_field'])->willReturnSelf();
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('reference_field' . ' = ?', 'value')
            ->willReturnSelf();
        $this->constraintConnectionMock->expects($this->once())
            ->method('fetchAssoc')
            ->with($this->selectMock)
            ->willReturn([]);
        $this->model->validate($this->constraintMock, $data);

        $this->expectExceptionMessage(
            "The row couldn't be updated because a foreign key constraint failed. Verify the constraint and try again."
        );
    }
}
