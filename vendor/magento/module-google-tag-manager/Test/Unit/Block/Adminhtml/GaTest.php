<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Test\Unit\Block\Adminhtml;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class GaTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GoogleTagManager\Block\Adminhtml\Ga */
    protected $ga;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \Magento\GoogleTagManager\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $googleTagManagerHelper;

    /** @var \Magento\Cookie\Helper\Cookie|\PHPUnit_Framework_MockObject_MockObject */
    protected $cookieCookieHelper;

    /** @var \Magento\Framework\Json\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $data;

    /** @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    /** @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $storeManager;

    protected function setUp()
    {
        $this->googleTagManagerHelper = $this->createMock(\Magento\GoogleTagManager\Helper\Data::class);
        $this->cookieCookieHelper = $this->createMock(\Magento\Cookie\Helper\Cookie::class);
        $this->data = $this->createMock(\Magento\Framework\Json\Helper\Data::class);
        $this->session = $this->createMock(\Magento\Backend\Model\Session::class);
        $this->storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->ga = $this->objectManagerHelper->getObject(
            \Magento\GoogleTagManager\Block\Adminhtml\Ga::class,
            [
                'googleAnalyticsData' => $this->googleTagManagerHelper,
                'cookieHelper' => $this->cookieCookieHelper,
                'jsonHelper' => $this->data,
                'backendSession' => $this->session,
                'storeManager' => $this->storeManager
            ]
        );
    }

    public function testGetOrderId()
    {
        $this->session->expects($this->any())->method('getData')->with('googleanalytics_creditmemo_order', false)
            ->willReturn(10);
        $this->assertEquals(10, $this->ga->getOrderId());
    }

    public function testGetStoreCurrencyCode()
    {
        $this->session->expects($this->any())->method('getData')->with('googleanalytics_creditmemo_store_id', false)
            ->willReturn(3);
        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $store->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->with(3)->willReturn($store);
        $this->assertEquals('USD', $this->ga->getStoreCurrencyCode());
    }

    public function testToHtml()
    {
        $this->googleTagManagerHelper->expects($this->atLeastOnce())->method('isGoogleAnalyticsAvailable')
            ->willReturn(true);
        $this->session->expects($this->atLeastOnce())
            ->method('getData')
            ->with('googleanalytics_creditmemo_order', false)
            ->willReturn(10);
        $this->ga->toHtml();
    }

    public function testToHtmlEmptyOrderId()
    {
        $this->googleTagManagerHelper->expects($this->never())->method('isGoogleAnalyticsAvailable');
        $this->session->expects($this->atLeastOnce())
            ->method('getData')
            ->with('googleanalytics_creditmemo_order', false)
            ->willReturn(null);
        $this->ga->toHtml();
    }
}
