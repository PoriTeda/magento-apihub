<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedCheckout\Test\Unit\Model\Observer;

class AddCartLinkTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AddCartLink
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $cartMock;

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
        $this->cartMock = $this->createMock(\Magento\AdvancedCheckout\Model\Cart::class);
        $this->observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->eventMock = $this->createMock(\Magento\Framework\Event::class);

        $this->model = new \Magento\AdvancedCheckout\Model\Observer\AddCartLink($this->cartMock);
    }

    public function testExecuteWhenBlockIsNotSidebar()
    {
        $this->observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($this->eventMock));
        $blockMock = $this->createPartialMock(\Magento\Checkout\Block\Cart\Totals::class, [
            'setAllowCartLink',
            'setCartEmptyMessage',
            '__wakeup'
        ]);
        $this->eventMock->expects($this->once())->method('getBlock')->will($this->returnValue($blockMock));
        $this->cartMock->expects($this->never())->method('getFailedItems');

        $this->model->execute($this->observerMock);
    }

    public function testExecuteWhenFailedItemsCountIsZero()
    {
        $this->observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($this->eventMock));
        $blockMock = $this->createPartialMock(\Magento\Checkout\Block\Cart\Sidebar::class, [
            'setAllowCartLink',
            'setCartEmptyMessage',
            '__wakeup'
        ]);
        $this->eventMock->expects($this->once())->method('getBlock')->will($this->returnValue($blockMock));
        $this->cartMock->expects($this->once())->method('getFailedItems')->will($this->returnValue([]));
        $blockMock->expects($this->never())->method('setAllowCartLink');

        $this->model->execute($this->observerMock);
    }

    public function testExecute()
    {
        $this->observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($this->eventMock));
        $blockMock = $this->createPartialMock(\Magento\Checkout\Block\Cart\Sidebar::class, [
                'setAllowCartLink',
                'setCartEmptyMessage',
                '__wakeup'
            ]);
        $this->eventMock->expects($this->once())->method('getBlock')->will($this->returnValue($blockMock));
        $this->cartMock->expects($this->once())->method('getFailedItems')->will($this->returnValue(['one', 'two']));
        $blockMock->expects($this->once())->method('setAllowCartLink')->with(true);
        $blockMock->expects($this->once())->method('setCartEmptyMessage')->with('2 item(s) need your attention.');

        $this->model->execute($this->observerMock);
    }
}
