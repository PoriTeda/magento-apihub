<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

class EnterpriseCustomerAttributeSaveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerCustomAttributes\Observer\EnterpriseCustomerAttributeSave
     */
    protected $observer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteFactory;

    protected function setUp()
    {
        $this->quoteFactory = $this->getMockBuilder(
            \Magento\CustomerCustomAttributes\Model\Sales\QuoteFactory::class
        )->disableOriginalConstructor()->setMethods(['create'])->getMock();

        $this->orderFactory = $this->getMockBuilder(
            \Magento\CustomerCustomAttributes\Model\Sales\OrderFactory::class
        )->disableOriginalConstructor()->setMethods(['create'])->getMock();

        $this->observer = new \Magento\CustomerCustomAttributes\Observer\EnterpriseCustomerAttributeSave(
            $this->orderFactory,
            $this->quoteFactory
        );
    }

    public function testEnterpriseCustomerAttributeSave()
    {
        $observer = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->setMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(\Magento\Customer\Model\Attribute::class)
            ->setMethods(['__wakeup', 'isObjectNew'])
            ->disableOriginalConstructor()
            ->getMock();

        $order = $this->getMockBuilder(\Magento\CustomerCustomAttributes\Model\Sales\Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quote = $this->getMockBuilder(\Magento\CustomerCustomAttributes\Model\Sales\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel->expects($this->once())->method('isObjectNew')->will($this->returnValue(true));
        $observer->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $event->expects($this->once())->method('getAttribute')->will($this->returnValue($dataModel));
        $quote->expects($this->once())->method('saveNewAttribute')->with($dataModel)->will($this->returnSelf());
        $this->quoteFactory->expects($this->once())->method('create')->will($this->returnValue($quote));
        $order->expects($this->once())->method('saveNewAttribute')->with($dataModel)->will($this->returnSelf());
        $this->orderFactory->expects($this->once())->method('create')->will($this->returnValue($order));
        /** @var \Magento\Framework\Event\Observer $observer */

        $this->assertInstanceOf(
            \Magento\CustomerCustomAttributes\Observer\EnterpriseCustomerAttributeSave::class,
            $this->observer->execute($observer)
        );
    }
}
