<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedCheckout\Test\Unit\Model\Observer;

use Magento\AdvancedCheckout\Model\Cart;

class CartProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CartProvider
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
    protected $requestInterfaceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionMock;

    /**
     * @var int
     */
    protected $soreId = 12;

    protected function setUp()
    {
        $this->cartMock = $this->createMock(\Magento\AdvancedCheckout\Model\Cart::class);
        $this->observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, [
                'getRequestModel',
                'getSession',
                '__wakeup'
            ]);
        $this->requestInterfaceMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->sessionMock =
            $this->createPartialMock(\Magento\Checkout\Model\Session::class, ['getStoreId', '_wakeup']);

        $this->model = new \Magento\AdvancedCheckout\Model\Observer\CartProvider($this->cartMock);
    }

    public function testGetStoreIdFromSession()
    {
        $this->observerMock->expects($this->exactly(2))
            ->method('getRequestModel')->will($this->returnValue($this->requestInterfaceMock));
        $this->requestInterfaceMock->expects($this->exactly(2))
            ->method('getParam')->will($this->returnValue(null));
        $this->observerMock->expects($this->exactly(2))
            ->method('getSession')->will($this->returnValue($this->sessionMock));
        $this->sessionMock->expects($this->once())->method('getStoreId')->will($this->returnValue($this->soreId));
        $this->cartMock->expects($this->once())->method('setSession')
            ->with($this->sessionMock)->will($this->returnValue($this->cartMock));
        $this->cartMock->expects($this->once())->method('setContext')
            ->with(Cart::CONTEXT_ADMIN_ORDER)->will($this->returnSelf());
        $this->cartMock->expects($this->once())
            ->method('setCurrentStore')
            ->with($this->soreId)
            ->will($this->returnSelf());

        $this->model->get($this->observerMock);
    }

    public function testGet()
    {
        $this->observerMock->expects($this->once())
            ->method('getRequestModel')->will($this->returnValue($this->requestInterfaceMock));
        $this->requestInterfaceMock->expects($this->once())
            ->method('getParam')->will($this->returnValue($this->soreId));
        $this->observerMock->expects($this->once())
            ->method('getSession')->will($this->returnValue($this->sessionMock));
        $this->cartMock->expects($this->once())->method('setSession')
            ->with($this->sessionMock)->will($this->returnValue($this->cartMock));
        $this->cartMock->expects($this->once())->method('setContext')
            ->with(Cart::CONTEXT_ADMIN_ORDER)->will($this->returnValue($this->cartMock));
        $this->cartMock->expects($this->once())->method('setCurrentStore')->with($this->soreId);

        $this->model->get($this->observerMock);
    }
}
