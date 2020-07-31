<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ForeignKey\Test\Unit;

use Magento\Framework\ForeignKey\Config;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Config
     */
    protected $model;

    /**
     * @var \Magento\Framework\ForeignKey\Config\Data | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataContainerMock;

    /**
     * @var \Magento\Framework\ForeignKey\ConstraintFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $constraintFactoryMock;

    protected function setUp()
    {
        $this->dataContainerMock = $this->createMock(\Magento\Framework\ForeignKey\Config\Data::class);
        $this->constraintFactoryMock = $this->createMock(\Magento\Framework\ForeignKey\ConstraintFactory::class);

        $this->model = new \Magento\Framework\ForeignKey\Config(
            $this->dataContainerMock,
            $this->constraintFactoryMock
        );
    }

    public function testGetConstraintsByReferenceTableNameReferenceIsSet()
    {
        $referenceTableName = 'reference_table_name';

        $constraintConfig = [$referenceTableName => ['value']];
        $this->dataContainerMock->expects($this->once())
            ->method('get')
            ->with('constraints_by_reference_table')
            ->willReturn($constraintConfig);
        $this->constraintFactoryMock->expects($this->once())
            ->method('get')
            ->with('value', $constraintConfig)->willReturn([]);

        $this->assertEquals([0 => []], $this->model->getConstraintsByReferenceTableName($referenceTableName));
    }

    public function testGetConstraintsByReferenceTableName()
    {
        $referenceTableName = 'reference_table_name';

        $constraintConfig = [$referenceTableName];
        $this->dataContainerMock->expects($this->once())
            ->method('get')
            ->with('constraints_by_reference_table')
            ->willReturn($constraintConfig);

        $this->constraintFactoryMock->expects($this->never())->method('get');
        $this->assertEquals([], $this->model->getConstraintsByReferenceTableName($referenceTableName));
    }

    public function testGetConstraintsByTableNameConstrainsIsSet()
    {
        $tableName = 'reference_table_name';
        $constraintsByTable = [$tableName => ['value']];
        $this->dataContainerMock->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap(
                [
                    ['constraints_by_reference_table', null, []],
                    ['constraints_by_table', null, $constraintsByTable],
                ]
            ));
        $this->constraintFactoryMock->expects($this->once())
            ->method('get')
            ->with('value', [])->willReturn([]);

        $this->assertEquals([0 => []], $this->model->getConstraintsByTableName($tableName));
    }

    public function testGetConstraintsByTableName()
    {
        $tableName = 'reference_table_name';
        $constraintsByTable = [];
        $this->dataContainerMock->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap(
                [
                    ['constraints_by_reference_table', null, []],
                    ['constraints_by_table', null, $constraintsByTable],
                ]
            ));
        $this->constraintFactoryMock->expects($this->never())->method('get');

        $this->assertEquals([], $this->model->getConstraintsByTableName($tableName));
    }
}
