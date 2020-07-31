<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

class SalesQuoteAddressCollectionAfterLoadTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerCustomAttributes\Observer\SalesQuoteAddressCollectionAfterLoad
     */
    protected $observer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteAddressFactory;

    protected function setUp()
    {
        $this->quoteAddressFactory = $this->getMockBuilder(
            \Magento\CustomerCustomAttributes\Model\Sales\Quote\AddressFactory::class
        )->disableOriginalConstructor()->setMethods(['create'])->getMock();

        $this->observer = new \Magento\CustomerCustomAttributes\Observer\SalesQuoteAddressCollectionAfterLoad(
            $this->quoteAddressFactory
        );
    }

    public function testSalesQuoteAddressCollectionAfterLoad()
    {
        $items = ['test', 'data'];
        $observer = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->setMethods(['getQuoteAddressCollection'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(\Magento\Framework\Data\Collection\AbstractDb::class)
            ->setMethods(['getItems', '__wakeup'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $quoteAddress = $this->getMockBuilder(\Magento\CustomerCustomAttributes\Model\Sales\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel->expects($this->once())->method('getItems')->will($this->returnValue($items));
        $observer->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $event->expects($this->once())->method('getQuoteAddressCollection')->will($this->returnValue($dataModel));
        $quoteAddress->expects($this->once())->method('attachDataToEntities')->with($items)->will($this->returnSelf());
        $this->quoteAddressFactory->expects($this->once())->method('create')->will($this->returnValue($quoteAddress));
        /** @var \Magento\Framework\Event\Observer $observer */

        $this->assertInstanceOf(
            \Magento\CustomerCustomAttributes\Observer\SalesQuoteAddressCollectionAfterLoad::class,
            $this->observer->execute($observer)
        );
    }
}
