<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedCheckout\Test\Unit\Model\Observer;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\AdvancedCheckout\Model\Cart;
use Magento\AdvancedCheckout\Model\Observer\CartProvider;
use Magento\AdvancedCheckout\Model\Observer\UploadSkuCsv;
use Magento\Backend\Model\Session\Quote as BackendQuoteSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Store\Model\Store;

class UploadSkuCsvTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UploadSkuCsv
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $cartProviderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutDataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $cartMock;

    /**
     * @var Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $observerMock;

    protected function setUp()
    {
        $this->checkoutDataMock = $this->createMock(Data::class);
        $this->cartProviderMock =
            $this->createMock(CartProvider::class);
        $this->cartMock = $this->createPartialMock(Cart::class, [
                'prepareAddProductsBySku',
                'saveAffectedProducts',
                'setCurrentStore',
                '__wakeup'
            ]);
        $this->observerMock = $this->createPartialMock(Observer::class, [
                'getRequestModel',
                'getOrderCreateModel',
                '__wakeup'
            ]);

        $this->model = new UploadSkuCsv($this->checkoutDataMock, $this->cartProviderMock);
    }

    public function testExecuteWhenSkuFileIsNotUploaded()
    {
        $requestInterfaceMock = $this->createMock(RequestInterface::class);
        $this->observerMock->expects($this->once())
            ->method('getRequestModel')->willReturn($requestInterfaceMock);
        $this->checkoutDataMock->expects($this->once())
            ->method('isSkuFileUploaded')
            ->with($requestInterfaceMock)
            ->willReturn(false);
        $this->checkoutDataMock->expects($this->never())
            ->method('processSkuFileUploading');

        $this->model->execute($this->observerMock);
    }

    public function testExecute()
    {
        $currentStoreId = 1;
        $requestInterfaceMock = $this->createMock(RequestInterface::class);
        $this->observerMock->expects($this->once())
            ->method('getRequestModel')->will($this->returnValue($requestInterfaceMock));
        $this->checkoutDataMock->expects($this->once())
            ->method('isSkuFileUploaded')->with($requestInterfaceMock)->will($this->returnValue(true));
        $this->checkoutDataMock->expects($this->once())
            ->method('processSkuFileUploading')->will($this->returnValue(['one']));

        $quoteStore = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteStore->method('getId')->willReturn($currentStoreId);
        $backendSession = $this->getMockBuilder(BackendQuoteSession::class)
            ->disableOriginalConstructor()
            ->getMock();
        $backendSession->method('getStore')
            ->willReturn($quoteStore);

        $orderCreateModelMock = $this->getMockBuilder(Create::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderCreateModelMock->method('getSession')
            ->willReturn($backendSession);

        $this->observerMock->expects($this->once())
            ->method('getOrderCreateModel')
            ->willReturn($orderCreateModelMock);
        $this->cartProviderMock->expects($this->once())
            ->method('get')->with($this->observerMock)
            ->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())
            ->method('setCurrentStore')
            ->with($quoteStore);
        $this->cartMock->expects($this->once())
            ->method('prepareAddProductsBySku')
            ->with(['one']);
        $this->cartMock->expects($this->once())
            ->method('saveAffectedProducts')
            ->with($orderCreateModelMock, false);

        $this->model->execute($this->observerMock);
    }
}
