<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ForeignKey\Test\Unit;

use Magento\Framework\ForeignKey\Constraint;

class ConstraintTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Constraint
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    protected function setUp()
    {
        $this->resourceMock = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $this->connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);

        $this->model = new \Magento\Framework\ForeignKey\Constraint(
            $this->resourceMock,
            'name',
            'connectionName',
            'referenceConnection',
            'tableName',
            'referenceTableName',
            'fieldName',
            'referenceFieldName',
            'deleteStrategy',
            ['subConstraints'],
            ['tableAffectedFields']
        );
    }

    public function testGetConnection()
    {
        $this->resourceMock->expects($this->once())
            ->method('getConnectionByName')
            ->with('connectionName')
            ->willReturn($this->connectionMock);

        $this->assertEquals($this->connectionMock, $this->model->getConnection());
    }

    public function testGetReferenceConnection()
    {
        $this->resourceMock->expects($this->once())
            ->method('getConnectionByName')
            ->with('referenceConnection')
            ->willReturn($this->connectionMock);
        $this->assertEquals($this->connectionMock, $this->model->getReferenceConnection());
    }

    public function testFetCondition()
    {
        $values = [];
        $this->resourceMock->expects($this->once())
            ->method('getConnectionByName')
            ->with('connectionName')
            ->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->once())
            ->method('quoteInto')
            ->with('fieldName IN(?)', $values)
            ->willReturn('string');

        $this->assertEquals('string', $this->model->getCondition($values));
    }

    /**
     * @dataProvider allowedStrategyDataProvider
     */
    public function testGetSubConstraints($strategy)
    {
        $this->model = new \Magento\Framework\ForeignKey\Constraint(
            $this->resourceMock,
            'name',
            'connectionName',
            'referenceConnection',
            'tableName',
            'referenceTableName',
            'fieldName',
            'referenceFieldName',
            $strategy,
            ['subConstraints'],
            ['tableAffectedFields']
        );

        $this->assertEquals(['subConstraints'], $this->model->getSubConstraints());
    }

    public function allowedStrategyDataProvider()
    {
        return [
            ['CASCADE'],
            ['DB CASCADE'],
        ];
    }

    public function testGetSubConstraintsStrategyIsNotAllowed()
    {
        $this->assertEquals([], $this->model->getSubConstraints());
    }

    public function testGetTableName()
    {
        $this->assertEquals('tableName', $this->model->getTableName());
    }

    public function testGetReferenceTableName()
    {
        $this->assertEquals('referenceTableName', $this->model->getReferenceTableName());
    }

    public function testGetFieldName()
    {
        $this->assertEquals('fieldName', $this->model->getFieldName());
    }

    public function testGetReferenceField()
    {
        $this->assertEquals('referenceFieldName', $this->model->getReferenceField());
    }

    public function testGetStrategy()
    {
        $this->assertEquals('deleteStrategy', $this->model->getStrategy());
    }

    public function testGetSubConstraintsAffectedFields()
    {
        $this->assertEquals(['tableAffectedFields'], $this->model->getSubConstraintsAffectedFields());
    }
}
