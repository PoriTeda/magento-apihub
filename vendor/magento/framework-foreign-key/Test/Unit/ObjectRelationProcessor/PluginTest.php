<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\ForeignKey\Test\Unit\ObjectRelationProcessor;

use Magento\Framework\ForeignKey\ObjectRelationProcessor\EnvironmentConfig;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ForeignKey\ObjectRelationProcessor\Plugin
     */
    protected $model;

    /**
     * @var \Magento\Framework\ForeignKey\ConfigInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \Magento\Framework\ForeignKey\ConstraintProcessor | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $constraintProcessorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $transactionManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $constraintsMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentConfigMock;

    protected function setUp()
    {
        $this->configMock = $this->createMock(\Magento\Framework\ForeignKey\ConfigInterface::class);

        $this->constraintProcessorMock = $this->createMock(\Magento\Framework\ForeignKey\ConstraintProcessor::class);

        $this->subjectMock =
            $this->createMock(\Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor::class);
        $this->transactionManagerMock =
            $this->createMock(\Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface::class);
        $this->connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->constraintsMock = $this->createMock(\Magento\Framework\ForeignKey\ConstraintInterface::class);

        $this->environmentConfigMock = $this->createMock(EnvironmentConfig::class);

        $this->model = new \Magento\Framework\ForeignKey\ObjectRelationProcessor\Plugin(
            $this->configMock,
            $this->constraintProcessorMock,
            $this->environmentConfigMock
        );
    }

    public function testBeforeDelete()
    {
        $this->environmentConfigMock->expects($this->once())->method('isScalable')->willReturn(true);
        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->connectionMock->expects($this->once())->method('select')->willReturn($selectMock);
        $selectMock->expects($this->once())->method('forUpdate')->with(true)->willReturnSelf();
        $selectMock->expects($this->once())->method('from')->with('table_name')->willReturnSelf();
        $selectMock->expects($this->once())->method('where')->with('condition')->willReturnSelf();
        $this->connectionMock->expects($this->once())->method('fetchAssoc')->with($selectMock);
        $this->configMock->expects($this->once())
            ->method('getConstraintsByReferenceTableName')
            ->with('table_name')
            ->willReturn([$this->constraintsMock]);
        $this->constraintProcessorMock->expects($this->once())
            ->method('resolve')
            ->with($this->transactionManagerMock, $this->constraintsMock, [[]]);
        $this->model->beforeDelete(
            $this->subjectMock,
            $this->transactionManagerMock,
            $this->connectionMock,
            'table_name',
            'condition',
            []
        );
    }

    public function testBeforeValidateDataIntegrityForNativeDBConstraints()
    {
        $this->environmentConfigMock->expects($this->once())->method('isScalable')->willReturn(true);
        $this->configMock->expects($this->once())
            ->method('getConstraintsByTableName')
            ->with('table_name')
            ->willReturn([$this->constraintsMock]);
        $this->constraintsMock->expects($this->once())->method('getStrategy')->willReturn('DB ');

        $this->constraintProcessorMock->expects($this->never())->method('validate');
        $this->model->beforeValidateDataIntegrity($this->subjectMock, 'table_name', []);
    }

    public function testBeforeValidateDataIntegrity()
    {
        $this->environmentConfigMock->expects($this->once())->method('isScalable')->willReturn(true);
        $this->configMock->expects($this->once())
            ->method('getConstraintsByTableName')
            ->with('table_name')
            ->willReturn([$this->constraintsMock]);
        $this->constraintsMock->expects($this->once())->method('getStrategy')->willReturn('notDB');

        $this->constraintProcessorMock->expects($this->once())->method('validate')->with($this->constraintsMock, []);
        $this->model->beforeValidateDataIntegrity($this->subjectMock, 'table_name', []);
    }
}
