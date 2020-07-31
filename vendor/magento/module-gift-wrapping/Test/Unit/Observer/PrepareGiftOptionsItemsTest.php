<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftWrapping\Test\Unit\Observer;

class PrepareGiftOptionsItemsTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GiftWrapping\Observer\PrepareGiftOptionsItems */
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
                'getItems',
                '__wakeup'
            ]
        );
        $this->_model = $objectManagerHelper->getObject(
            \Magento\GiftWrapping\Observer\PrepareGiftOptionsItems::class,
            [
                'giftWrappingData' => $this->helperDataMock
            ]
        );
        $this->_event = new \Magento\Framework\DataObject();
        $this->_observer = new \Magento\Framework\Event\Observer(['event' => $this->_event]);
    }

    public function testPrepareGiftOptionsItems()
    {
        $itemMock = $this->createPartialMock(
            \Magento\Framework\DataObject::class,
            [
                'getProduct',
                'getIsVirtual',
                'setIsGiftOptionsAvailable',
                '__wakeup'
            ]
        );
        $productMock = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            [
                'getGiftWrappingAvailable',
                '__wakeup'
            ]
        );
        $this->observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($this->eventMock));
        $this->eventMock->expects($this->once())->method('getItems')->will($this->returnValue([$itemMock]));
        $itemMock->expects($this->once())->method('getProduct')->will($this->returnValue($productMock));
        $productMock->expects($this->once())->method('getGiftWrappingAvailable')->will($this->returnValue(true));
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForProduct')->with(true)->will($this->returnValue(true));
        $itemMock->expects($this->once())->method('getIsVirtual')->will($this->returnValue(false));
        $itemMock->expects($this->once())->method('setIsGiftOptionsAvailable')->with(true);

        $this->_model->execute($this->observerMock);
    }

    public function testPrepareGiftOptionsItemsWithVirtualProduct()
    {
        $itemMock = $this->createPartialMock(
            \Magento\Framework\DataObject::class,
            [
                'getProduct',
                'getIsVirtual',
                'setIsGiftOptionsAvailable',
                '__wakeup'
            ]
        );
        $productMock = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            [
                'getGiftWrappingAvailable',
                '__wakeup'
            ]
        );
        $this->observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($this->eventMock));
        $this->eventMock->expects($this->once())->method('getItems')->will($this->returnValue([$itemMock]));
        $itemMock->expects($this->once())->method('getProduct')->will($this->returnValue($productMock));
        $productMock->expects($this->once())->method('getGiftWrappingAvailable')->will($this->returnValue(true));
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForProduct')->with(true)->will($this->returnValue(true));
        $itemMock->expects($this->once())->method('getIsVirtual')->will($this->returnValue(true));
        $itemMock->expects($this->never())->method('setIsGiftOptionsAvailable');

        $this->_model->execute($this->observerMock);
    }

    public function testPrepareGiftOptionsItemsWithNotAllowedProduct()
    {
        $itemMock = $this->createPartialMock(
            \Magento\Framework\DataObject::class,
            [
                'getProduct',
                'getIsVirtual',
                'setIsGiftOptionsAvailable',
                '__wakeup'
            ]
        );
        $productMock = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            [
                'getGiftWrappingAvailable',
                '__wakeup'
            ]
        );
        $this->observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($this->eventMock));
        $this->eventMock->expects($this->once())->method('getItems')->will($this->returnValue([$itemMock]));
        $itemMock->expects($this->once())->method('getProduct')->will($this->returnValue($productMock));
        $productMock->expects($this->once())->method('getGiftWrappingAvailable')->will($this->returnValue(false));
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForProduct')->with(false)->will($this->returnValue(false));
        $itemMock->expects($this->never())->method('getIsVirtual');
        $itemMock->expects($this->never())->method('setIsGiftOptionsAvailable');

        $this->_model->execute($this->observerMock);
    }
}
