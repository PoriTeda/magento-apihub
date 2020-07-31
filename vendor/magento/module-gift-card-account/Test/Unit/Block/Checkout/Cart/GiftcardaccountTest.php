<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Test\Unit\Block\Checkout\Cart;

use Magento\GiftCardAccount\Block\Checkout\Cart\Giftcardaccount;

class GiftcardaccountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSessionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutSession;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlBuilderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManagerMock;

    /**
     * @var Giftcardaccount
     */
    protected $model;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(\Magento\Framework\View\Element\Template\Context::class);
        $this->customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->checkoutSession = $this->createMock(\Magento\Checkout\Model\Session::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->urlBuilderMock = $this->createMock(\Magento\Framework\UrlInterface::class);
        $this->eventManagerMock = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $this->contextMock->expects($this->atLeastOnce())->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->expects($this->atLeastOnce())->method('getUrlBuilder')->willReturn($this->urlBuilderMock);
        $this->contextMock->expects($this->any())->method('getEventManager')->willReturn($this->eventManagerMock);
        $this->model = new Giftcardaccount(
            $this->contextMock,
            $this->customerSessionMock,
            $this->checkoutSession
        );
    }

    public function testGetUrlNoParam()
    {
        $route = 'someroute';
        $params = [];
        $secureFlag = true;
        $builderResult = 'secureURL';

        $this->requestMock->expects($this->once())->method('isSecure')->willReturn($secureFlag);
        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with($route, ['_secure' => $secureFlag])
            ->willReturn($builderResult);
        $url = $this->model->getUrl($route, $params);
        $this->assertEquals($builderResult, $url);
    }

    public function testGetUrlWithParam()
    {
        $route = 'someroute';
        $params = ['_secure' => true];
        $builderResult = 'secureURL';

        $this->requestMock->expects($this->never())->method('isSecure');
        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with($route, $params)
            ->willReturn($builderResult);
        $url = $this->model->getUrl($route, $params);
        $this->assertEquals($builderResult, $url);
    }
}
