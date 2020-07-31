<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftWrapping\Test\Unit\Observer;

class SalesEventOrderToQuoteTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GiftWrapping\Observer\SalesEventOrderToQuote */
    protected $_model;

    /**
     * @var \Magento\Framework\Event\Observer
     */
    protected $_observer;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_event;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $helperDataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $observerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventMock;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->helperDataMock = $this->createMock(\Magento\GiftWrapping\Helper\Data::class);
        $this->observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->eventMock = $this->createPartialMock(\Magento\Framework\Event::class, [
                'getOrder',
                'getQuote',
                '__wakeup'
            ]);
        $this->_model = $objectManagerHelper->getObject(
            \Magento\GiftWrapping\Observer\SalesEventOrderToQuote::class,
            [
               'giftWrappingData' =>  $this->helperDataMock
            ]
        );
        $this->_event = new \Magento\Framework\DataObject();
        $this->_observer = new \Magento\Framework\Event\Observer(['event' => $this->_event]);
    }

    public function testSalesEventOrderToQuoteForReorderedOrder()
    {
        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['getStore', 'getReordered', '__wakeup']
        );
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($this->eventMock));
        $this->eventMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));
        $orderMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));
        $storeId = 12;
        $storeMock->expects($this->once())->method('getId')->will($this->returnValue($storeId));
        $orderMock->expects($this->once())->method('getReordered')->will($this->returnValue(true));

        $this->_model->execute($this->observerMock);
    }

    public function testSalesEventOrderToQuoteWithGiftWrappingThatNotAvailableForOrder()
    {
        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['getStore', 'getReordered', '__wakeup']
        );
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($this->eventMock));
        $this->eventMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));
        $orderMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));
        $storeId = 12;
        $storeMock->expects($this->once())->method('getId')->will($this->returnValue($storeId));
        $orderMock->expects($this->once())->method('getReordered')->will($this->returnValue(false));
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForOrder')
            ->with($storeId)
            ->will($this->returnValue(false));

        $this->_model->execute($this->observerMock);
    }

    public function testSalesEventOrderToQuote()
    {
        $orderMock = $this->createPartialMock(\Magento\Sales\Model\Order::class, [
                'getStore',
                'getReordered',
                'getGwId',
                'getGwAllowGiftReceipt',
                'getGwAddCard',
                '__wakeup'
            ]);
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, [
                'setGwId',
                'setGwAllowGiftReceipt',
                'setGwAddCard',
                '__wakeup',
            ]);
        $this->observerMock->expects($this->exactly(2))->method('getEvent')->will($this->returnValue($this->eventMock));
        $this->eventMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));
        $orderMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));
        $storeId = 12;
        $storeMock->expects($this->once())->method('getId')->will($this->returnValue($storeId));
        $orderMock->expects($this->once())->method('getReordered')->will($this->returnValue(false));
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForOrder')
            ->with($storeId)
            ->will($this->returnValue(true));
        $this->eventMock->expects($this->once())->method('getQuote')->will($this->returnValue($quoteMock));
        $orderMock->expects($this->once())->method('getGwId')->will($this->returnValue(1));
        $orderMock->expects($this->once())
            ->method('getGwAllowGiftReceipt')->will($this->returnValue('Gift_recipient'));
        $orderMock->expects($this->once())->method('getGwAddCard')->will($this->returnValue('add_cart'));
        $quoteMock->expects($this->once())->method('setGwId')->with(1)->will($this->returnValue($quoteMock));
        $quoteMock->expects($this->once())
            ->method('setGwAllowGiftReceipt')->with('Gift_recipient')->will($this->returnValue($quoteMock));
        $quoteMock->expects($this->once())
            ->method('setGwAddCard')->with('add_cart')->will($this->returnValue($quoteMock));

        $this->_model->execute($this->observerMock);
    }
}
