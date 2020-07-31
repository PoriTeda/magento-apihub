<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Block\Checkout\Payment;

class AdditionalTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var \Magento\Reward\Block\Checkout\Payment\Additional
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $rewardMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteMock;

    protected function setUp()
    {
        $this->helperMock = $this->createMock(\Magento\Reward\Helper\Data::class);
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->rewardMock = $this->createPartialMock(
            \Magento\Reward\Model\Reward::class,
            ['getCurrencyAmount', 'getPointsBalance']
        );
        $checkoutSessionMock = $this->createMock(\Magento\Checkout\Model\Session::class);
        $this->quoteMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            ['getBaseGrandTotal', 'getBaseRewardCurrencyAmount']
        );
        $checkoutSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $contextMock = $this->createMock(\Magento\Framework\View\Element\Template\Context::class);
        $contextMock->expects($this->once())->method('getStoreManager')->willReturn($this->storeManagerMock);
        $data = ['reward' => $this->rewardMock];
        $this->model = $objectManager->getObject(\Magento\Reward\Block\Checkout\Payment\Additional::class, [
            'context' => $contextMock,
            'checkoutSession' => $checkoutSessionMock,
            'rewardData' => $this->helperMock,
            'data' => $data
        ]);
    }

    public function testGetCanUseRewardPoints()
    {
        $websiteMock = $this->createMock(\Magento\Store\Api\Data\WebsiteInterface::class);
        $this->helperMock->expects($this->once())->method('getHasRates')->willReturn(true);
        $this->helperMock->expects($this->once())->method('isEnabledOnFront')->willReturn(true);
        $websiteMock->expects($this->once())->method('getId')->willReturn('1');
        $this->storeManagerMock->expects($this->once())->method('getWebsite')->willReturn($websiteMock);
        $this->helperMock->expects($this->once())
            ->method('getGeneralConfig')
            ->with('min_points_balance', 1)
            ->willReturn('10');
        $this->rewardMock->expects($this->once())->method('getCurrencyAmount')->willReturn(5);
        $this->rewardMock->expects($this->once())->method('getPointsBalance')->willReturn(15);
        $this->quoteMock->expects($this->once())->method('getBaseGrandTotal')->willReturn(0);
        $this->assertFalse($this->model->getCanUseRewardPoints());
    }
}
