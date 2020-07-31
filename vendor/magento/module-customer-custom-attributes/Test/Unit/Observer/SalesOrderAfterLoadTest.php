<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

class SalesOrderAfterLoadTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerCustomAttributes\Observer\SalesOrderAfterLoad
     */
    protected $observer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderFactory;

    protected function setUp()
    {
        $this->orderFactory = $this->getMockBuilder(
            \Magento\CustomerCustomAttributes\Model\Sales\OrderFactory::class
        )->disableOriginalConstructor()->setMethods(['create'])->getMock();

        $this->observer = new \Magento\CustomerCustomAttributes\Observer\SalesOrderAfterLoad($this->orderFactory);
    }

    public function testSalesOrderAfterLoad()
    {
        $orderId = 1;
        $observer = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->setMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(\Magento\Framework\Model\AbstractModel::class)
            ->setMethods(['getId', '__wakeup'])
            ->disableOriginalConstructor()
            ->getMock();

        $order = $this->getMockBuilder(\Magento\CustomerCustomAttributes\Model\Sales\Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel->expects($this->once())->method('getId')->will($this->returnValue($orderId));
        $observer->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $event->expects($this->once())->method('getOrder')->will($this->returnValue($dataModel));
        $order->expects($this->once())->method('load')->with($orderId)->will($this->returnSelf());
        $order->expects($this->once())->method('attachAttributeData')->with($dataModel)->will($this->returnSelf());
        $this->orderFactory->expects($this->once())->method('create')->will($this->returnValue($order));
        /** @var \Magento\Framework\Event\Observer $observer */

        $this->assertInstanceOf(
            \Magento\CustomerCustomAttributes\Observer\SalesOrderAfterLoad::class,
            $this->observer->execute($observer)
        );
    }
}
