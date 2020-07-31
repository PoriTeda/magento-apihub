<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

class SalesQuoteAddressAfterSaveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerCustomAttributes\Observer\SalesQuoteAddressAfterSave
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

        $this->observer = new \Magento\CustomerCustomAttributes\Observer\SalesQuoteAddressAfterSave(
            $this->quoteAddressFactory
        );
    }

    public function testSalesQuoteAddressAfterSave()
    {
        $observer = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->setMethods(['getQuoteAddress'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(\Magento\Framework\Model\AbstractModel::class)
            ->setMethods(['__wakeup'])
            ->disableOriginalConstructor()
            ->getMock();

        $quoteAddress = $this->getMockBuilder(\Magento\CustomerCustomAttributes\Model\Sales\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $observer->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $event->expects($this->once())->method('getQuoteAddress')->will($this->returnValue($dataModel));
        $quoteAddress->expects($this->once())->method('saveAttributeData')->with($dataModel)->will($this->returnSelf());
        $this->quoteAddressFactory->expects($this->once())->method('create')->will($this->returnValue($quoteAddress));
        /** @var \Magento\Framework\Event\Observer $observer */

        $this->assertInstanceOf(
            \Magento\CustomerCustomAttributes\Observer\SalesQuoteAddressAfterSave::class,
            $this->observer->execute($observer)
        );
    }
}
