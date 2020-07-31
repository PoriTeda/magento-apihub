<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Model\ResourceModel;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Class RmaTest
 */
class RmaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Rma\Model\ResourceModel\Rma
     */
    protected $rma;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\Context | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \Magento\Rma\Model\GridFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $gridFactory;

    /**
     * @var \Magento\SalesSequence\Model\Manager | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sequenceManager;

    /**
     * @var \Magento\Framework\App\ResourceConnection | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Rma\Model\Rma | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaMock;

    /**
     * @var \Magento\SalesSequence\Model\Sequence | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sequenceMock;

    /**
     * @var \Magento\Rma\Model\Grid | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $gridModelMock;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectRelationProcessorMock;

    protected function setUp()
    {
        $this->context = $this->createMock(\Magento\Framework\Model\ResourceModel\Db\Context::class);
        $this->gridFactory = $this->getMockBuilder(\Magento\Rma\Model\GridFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->sequenceManager = $this->createMock(\Magento\SalesSequence\Model\Manager::class);
        $this->connectionMock = $this->createPartialMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class, [
                'describeTable',
                'insert',
                'lastInsertId',
                'beginTransaction',
                'rollback',
                'commit',
                'quoteInto',
                'update'
            ]);
        $this->resourceMock = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $this->objectRelationProcessorMock = $this->createMock(
            \Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor::class
        );
        $this->sequenceMock = $this->createMock(\Magento\SalesSequence\Model\Sequence::class);
        $this->gridModelMock = $this->createMock(\Magento\Rma\Model\Grid::class);
        $this->gridFactory->expects($this->once())->method('create')->willReturn($this->gridModelMock);
        $this->rmaMock = $this->createMock(\Magento\Rma\Model\Rma::class);
        $this->context->expects($this->once())->method('getResources')->willReturn($this->resourceMock);
        $this->context->expects($this->once())
            ->method('getObjectRelationProcessor')
            ->willReturn($this->objectRelationProcessorMock);
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->rma = $this->objectManagerHelper->getObject(
            \Magento\Rma\Model\ResourceModel\Rma::class,
            [
                'context' => $this->context,
                'rmaGridFactory' => $this->gridFactory,
                'sequenceManager' => $this->sequenceManager
            ]
        );
    }

    public function testSave()
    {
        $nextValue = 2;
        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->any())
            ->method('quoteInto');
        $this->connectionMock->expects($this->any())
            ->method('describeTable')
            ->will($this->returnValue([]));
        $this->connectionMock->expects($this->any())
            ->method('update');
        $this->connectionMock->expects($this->any())
            ->method('lastInsertId');
        $this->rmaMock->expects($this->once())->method('isDeleted')->willReturn(false);
        $this->rmaMock->expects($this->once())->method('hasDataChanges')->willReturn(true);
        $this->rmaMock->expects($this->once())->method('validateBeforeSave');
        $this->rmaMock->expects($this->once())->method('beforeSave');
        $this->rmaMock->expects($this->once())->method('isSaveAllowed')->willReturn(true);
        $this->rmaMock->expects($this->atLeastOnce())->method('getData')->willReturn([]);
        $this->rmaMock->expects($this->once())->method('setData')->willReturn([]);
        $this->rmaMock->expects($this->once())->method('getIncrementId')->willReturn(false);
        $this->rmaMock->expects($this->once())->method('getStoreId')->willReturn(1);
        $this->rmaMock->expects($this->once())->method('setIncrementId')->with($nextValue);
        $this->sequenceMock->expects($this->once())->method('getNextValue')->willReturn($nextValue);
        $this->sequenceManager->expects($this->once())
            ->method('getSequence')
            ->with('rma_item', 1)
            ->willReturn($this->sequenceMock);
        $this->rma->save($this->rmaMock);
    }
}
