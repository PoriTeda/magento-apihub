<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Observer\Backend;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class DeleteStoreObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\VersionsCms\Observer\Backend\CleanStoreFootprints|MockObject
     */
    protected $cleanStoreFootprintsMock;

    /**
     * @var \Magento\Framework\Event\Observer|MockObject
     */
    protected $eventObserverMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\VersionsCms\Observer\Backend\DeleteStoreObserver
     */
    protected $observer;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->cleanStoreFootprintsMock = $this->createMock(
            \Magento\VersionsCms\Observer\Backend\CleanStoreFootprints::class
        );
        $this->eventObserverMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $this->observer = $this->objectManagerHelper->getObject(
            \Magento\VersionsCms\Observer\Backend\DeleteStoreObserver::class,
            [
                'cleanStoreFootprints' => $this->cleanStoreFootprintsMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testDeleteStore()
    {
        $storeId = 2;

        /** @var \Magento\Store\Model\Store|MockObject $storeMock */
        $storeMock = $this->createPartialMock(\Magento\Store\Model\Store::class, ['getId']);
        $storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        /** @var \Magento\Framework\Event|MockObject $eventMock */
        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getStore']);
        $eventMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $this->eventObserverMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        $this->cleanStoreFootprintsMock->expects($this->once())->method('clean')->with($storeId);

        $this->observer->execute($this->eventObserverMock);
    }
}
