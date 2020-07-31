<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ForeignKey\Test\Unit;

use Magento\Framework\ForeignKey\ConstraintFactory;

class ConstraintFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConstraintFactory
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    protected function setUp()
    {
        $this->objectManagerMock = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);

        $this->model = new \Magento\Framework\ForeignKey\ConstraintFactory($this->objectManagerMock);
    }

    public function testGetConstraintConfigTableNameIsNotSet()
    {
        $constraintData = [
            'name' => 'name',
            'connection' => 'connectionName',
            'reference_connection' => 'referenceConnection',
            'table_name' => 'tableName',
            'reference_table_name' => 'referenceTableName',
            'field_name' => 'fieldName',
            'reference_field_name' => 'referenceFieldName',
            'delete_strategy' => 'deleteStrategy',
            'table_affected_fields' => 'tableAffectedFields'
        ];
        $constraintConfig = [];
        $this->objectManagerMock->expects($this->once())->method('create')->with(
            \Magento\Framework\ForeignKey\ConstraintInterface::class,
            [
                'name' => $constraintData['name'],
                'connectionName' => $constraintData['connection'],
                'referenceConnection' => $constraintData['reference_connection'],
                'tableName' => $constraintData['table_name'],
                'referenceTableName' => $constraintData['reference_table_name'],
                'fieldName' => $constraintData['field_name'],
                'referenceFieldName' => $constraintData['reference_field_name'],
                'deleteStrategy' => $constraintData['delete_strategy'],
                'subConstraints' => [],
                'tableAffectedFields' => $constraintData['table_affected_fields'],
            ]
        );
        $this->model->get($constraintData, [$constraintConfig]);
    }
}
