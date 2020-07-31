<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SalesArchive\Test\Unit\Observer;

class GridSyncInsertObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SalesArchive\Observer\GridSyncInsertObserver
     */
    private $observer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $entityGridMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $globalConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $archivalListMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $archiveFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $observerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $objectMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resourceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $archiveMock;

    protected function setUp()
    {
        $this->entityGridMock = $this->createMock(\Magento\Sales\Model\ResourceModel\GridInterface::class);
        $this->globalConfigMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->configMock = $this->createMock(\Magento\SalesArchive\Model\Config::class);
        $this->objectMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->archiveFactoryMock = $this->createPartialMock(
            \Magento\SalesArchive\Model\ArchiveFactory::class,
            ['create']
        );
        $this->archivalListMock = $this->createMock(\Magento\SalesArchive\Model\ArchivalList::class);
        $this->observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getObject']);
        $this->archiveMock = $this->createMock(\Magento\SalesArchive\Model\Archive::class);
        $this->resourceMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order::class);
        $this->observer = new \Magento\SalesArchive\Observer\GridSyncInsertObserver(
            $this->entityGridMock,
            $this->globalConfigMock,
            $this->configMock,
            $this->archiveFactoryMock,
            $this->archivalListMock
        );
    }

    public function testExecuteIfArchiveDisabled()
    {
        $this->configMock->expects($this->once())->method('isArchiveActive')->willReturn(false);
        $this->observerMock->expects($this->never())->method('getObject');
        $this->observer->execute($this->observerMock);
    }

    public function testExecuteIfArchiveEntityNotExist()
    {
        $this->configMock->expects($this->once())->method('isArchiveActive')->willReturn(true);
        $this->observerMock->expects($this->once())->method('getObject')->willReturn($this->objectMock);
        $this->archiveFactoryMock->expects($this->once())->method('create')->willReturn($this->archiveMock);
        $this->archivalListMock
            ->expects($this->once())
            ->method('getEntityByObject')
            ->with($this->resourceMock)
            ->willReturn(false);
        $this->objectMock->expects($this->once())->method('getResource')->willReturn($this->resourceMock);

        $this->observer->execute($this->observerMock);
    }

    public function testExecuteIfArchiveEntityExists()
    {
        $invoiceId = 1;
        $this->configMock->expects($this->once())->method('isArchiveActive')->willReturn(true);
        $this->observerMock->expects($this->once())->method('getObject')->willReturn($this->objectMock);
        $this->archiveFactoryMock->expects($this->once())->method('create')->willReturn($this->archiveMock);
        $this->archivalListMock
            ->expects($this->once())
            ->method('getEntityByObject')
            ->with($this->resourceMock)
            ->willReturn('invoice');
        $this->objectMock->expects($this->once())->method('getResource')->willReturn($this->resourceMock);
        $this->objectMock->expects($this->once())->method('getId')->willReturn($invoiceId);
        $this->archiveMock
            ->expects($this->once())
            ->method('getIdsInArchive')
            ->with('invoice', [$invoiceId])
            ->willReturn([]);
        $this->archiveMock
            ->expects($this->once())
            ->method('getRelatedIds')
            ->with('invoice', [$invoiceId])
            ->willReturn([$invoiceId]);
        $this->globalConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('dev/grid/async_indexing')
            ->willReturn(false);
        $this->entityGridMock->expects($this->once())->method('refresh')->with($invoiceId);
        $this->observer->execute($this->observerMock);
    }
}
