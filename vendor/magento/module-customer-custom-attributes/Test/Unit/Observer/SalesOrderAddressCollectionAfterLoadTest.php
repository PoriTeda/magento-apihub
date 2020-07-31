<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

class SalesOrderAddressCollectionAfterLoadTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerCustomAttributes\Observer\SalesOrderAddressCollectionAfterLoad
     */
    protected $observer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderAddressFactory;

    protected function setUp()
    {
        $this->orderAddressFactory = $this->getMockBuilder(
            \Magento\CustomerCustomAttributes\Model\Sales\Order\AddressFactory::class
        )->disableOriginalConstructor()->setMethods(['create'])->getMock();

        $this->observer = new \Magento\CustomerCustomAttributes\Observer\SalesOrderAddressCollectionAfterLoad(
            $this->orderAddressFactory
        );
    }

    public function testSalesOrderAddressCollectionAfterLoad()
    {
        $items = ['test', 'data'];
        $observer = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->setMethods(['getOrderAddressCollection'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(\Magento\Framework\Data\Collection\AbstractDb::class)
            ->setMethods(['getItems', '__wakeup'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $orderAddress = $this->getMockBuilder(\Magento\CustomerCustomAttributes\Model\Sales\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel->expects($this->once())->method('getItems')->will($this->returnValue($items));
        $observer->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $event->expects($this->once())->method('getOrderAddressCollection')->will($this->returnValue($dataModel));
        $orderAddress->expects($this->once())->method('attachDataToEntities')->with($items)->will($this->returnSelf());
        $this->orderAddressFactory->expects($this->once())->method('create')->will($this->returnValue($orderAddress));
        /** @var \Magento\Framework\Event\Observer $observer */

        $this->assertInstanceOf(
            \Magento\CustomerCustomAttributes\Observer\SalesOrderAddressCollectionAfterLoad::class,
            $this->observer->execute($observer)
        );
    }
}
