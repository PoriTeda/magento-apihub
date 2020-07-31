<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftWrapping\Test\Unit\Observer;

class SalesEventOrderItemToQuoteItemTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GiftWrapping\Observer\SalesEventOrderItemToQuoteItem */
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
        $this->eventMock = $this->createPartialMock(
            \Magento\Framework\Event::class,
            [
                'getOrderItem',
                'getQuoteItem',
                '__wakeup'
            ]
        );
        $this->_model = $objectManagerHelper->getObject(
            \Magento\GiftWrapping\Observer\SalesEventOrderItemToQuoteItem::class,
            [
                'giftWrappingData' => $this->helperDataMock
            ]
        );
        $this->_event = new \Magento\Framework\DataObject();
        $this->_observer = new \Magento\Framework\Event\Observer(['event' => $this->_event]);
    }

    public function testSalesEventOrderItemToQuoteItemWithReorderedOrder()
    {
        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['getStore', 'getReordered', '__wakeup']
        );
        $orderItemMock = $this->createPartialMock(\Magento\Sales\Model\Order\Item::class, ['getOrder', '__wakeup']);
        $this->observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($this->eventMock));
        $this->eventMock->expects($this->once())->method('getOrderItem')->will($this->returnValue($orderItemMock));
        $orderItemMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));
        $orderMock->expects($this->once())->method('getReordered')->will($this->returnValue(true));

        $this->_model->execute($this->observerMock);
    }

    public function testSalesEventOrderItemToQuoteItemWithGiftWrappingThatNotAllowedForItems()
    {
        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['getStore', 'getReordered', '__wakeup']
        );
        $orderItemMock = $this->createPartialMock(\Magento\Sales\Model\Order\Item::class, ['getOrder', '__wakeup']);
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($this->eventMock));
        $this->eventMock->expects($this->once())->method('getOrderItem')->will($this->returnValue($orderItemMock));
        $orderItemMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));
        $orderMock->expects($this->once())->method('getReordered')->will($this->returnValue(false));

        $storeId = 12;
        $orderMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));
        $storeMock->expects($this->once())->method('getId')->will($this->returnValue($storeId));
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForItems')
            ->with($storeId)
            ->will($this->returnValue(null));

        $this->_model->execute($this->observerMock);
    }

    public function testSalesEventOrderItemToQuoteItem()
    {
        $orderItemMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Item::class,
            [
                'getOrder',
                'getGwId',
                'getGwBasePrice',
                'getGwPrice',
                'getGwBaseTaxAmount',
                'getGwTaxAmount',
                '__wakeup'
            ]
        );
        $quoteItemMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote\Item::class,
            [
                'setGwId',
                'setGwBasePrice',
                'setGwPrice',
                'setGwBaseTaxAmount',
                'setGwTaxAmount',
                '__wakeup'
            ]
        );
        $this->observerMock->expects($this->exactly(2))->method('getEvent')->will($this->returnValue($this->eventMock));
        $this->eventMock->expects($this->once())->method('getOrderItem')->will($this->returnValue($orderItemMock));
        $orderItemMock->expects($this->once())->method('getOrder')->will($this->returnValue(null));
        $this->eventMock->expects($this->once())->method('getQuoteItem')->will($this->returnValue($quoteItemMock));
        $orderItemMock->expects($this->once())->method('getGwId')->will($this->returnValue(11));
        $orderItemMock->expects($this->once())->method('getGwBasePrice')->will($this->returnValue(22));
        $orderItemMock->expects($this->once())->method('getGwPrice')->will($this->returnValue(33));
        $orderItemMock->expects($this->once())->method('getGwBaseTaxAmount')->will($this->returnValue(44));
        $orderItemMock->expects($this->once())->method('getGwTaxAmount')->will($this->returnValue(55));
        $quoteItemMock->expects($this->once())
            ->method('setGwId')
            ->with(11)
            ->will($this->returnValue($quoteItemMock));
        $quoteItemMock->expects($this->once())
            ->method('setGwBasePrice')
            ->with(22)
            ->will($this->returnValue($quoteItemMock));
        $quoteItemMock->expects($this->once())
            ->method('setGwPrice')
            ->with(33)
            ->will($this->returnValue($quoteItemMock));
        $quoteItemMock->expects($this->once())
            ->method('setGwBaseTaxAmount')
            ->with(44)->will($this->returnValue($quoteItemMock));
        $quoteItemMock->expects($this->once())
            ->method('setGwTaxAmount')
            ->with(55)
            ->will($this->returnValue($quoteItemMock));

        $this->_model->execute($this->observerMock);
    }
}
