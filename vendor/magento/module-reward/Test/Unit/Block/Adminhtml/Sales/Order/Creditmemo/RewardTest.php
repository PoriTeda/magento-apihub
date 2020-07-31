<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Block\Adminhtml\Sales\Order\Creditmemo;

class RewardTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Block\Adminhtml\Sales\Order\Creditmemo\Reward
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardHelperMock;

    protected function setUp()
    {
        $this->registryMock = $this->createMock(\Magento\Framework\Registry::class);
        $this->rewardHelperMock = $this->createMock(\Magento\Reward\Helper\Data::class);
        $contextMock = $this->createMock(\Magento\Backend\Block\Template\Context::class);

        $this->model = new \Magento\Reward\Block\Adminhtml\Sales\Order\Creditmemo\Reward(
            $contextMock,
            $this->registryMock,
            $this->rewardHelperMock
        );
    }

    public function testGetCreditmemo()
    {
        $creditmemoMock = $this->createMock(\Magento\Sales\Model\Order\Creditmemo::class);
        $this->registryMock->expects($this->once())->method('registry')->with('current_creditmemo')
            ->willReturn($creditmemoMock);

        $this->assertEquals($creditmemoMock, $this->model->getCreditmemo());
    }

    /**
     * Check that refund is not possible for guest.
     */
    public function testCanRefundRewardPointsWithGuest()
    {
        $creditmemoMock = $this->createMock(\Magento\Sales\Model\Order\Creditmemo::class);
        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['getRewardCurrencyAmount', 'getCustomerIsGuest']
        );
        $this->registryMock->expects($this->atLeastOnce())->method('registry')->with('current_creditmemo')
            ->willReturn($creditmemoMock);

        $creditmemoMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getCustomerIsGuest')->willReturn(true);

        $orderMock->expects($this->never())->method('getRewardCurrencyAmount');
        $this->assertFalse($this->model->canRefundRewardPoints());
    }

    /**
     * Check that refund is not possible when order has no used reward points.
     */
    public function testCanRefundRewardPointsWithNoReward()
    {
        $creditmemoMock = $this->createMock(\Magento\Sales\Model\Order\Creditmemo::class);
        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['getRewardCurrencyAmount', 'getCustomerIsGuest']
        );
        $this->registryMock->expects($this->atLeastOnce())->method('registry')->with('current_creditmemo')
            ->willReturn($creditmemoMock);

        $creditmemoMock->expects($this->atLeastOnce())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getCustomerIsGuest')->willReturn(false);
        $orderMock->expects($this->once())->method('getRewardCurrencyAmount')->willReturn(0);

        $this->assertFalse($this->model->canRefundRewardPoints());
    }

    /**
     * Check that it is possible to refund reward points.
     */
    public function testCanRefundRewardPoints()
    {
        $creditmemoMock = $this->createMock(\Magento\Sales\Model\Order\Creditmemo::class);
        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['getRewardCurrencyAmount', 'getCustomerIsGuest']
        );
        $this->registryMock->expects($this->atLeastOnce())->method('registry')->with('current_creditmemo')
            ->willReturn($creditmemoMock);

        $creditmemoMock->expects($this->atLeastOnce())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getCustomerIsGuest')->willReturn(false);
        $orderMock->expects($this->once())->method('getRewardCurrencyAmount')->willReturn(75);

        $this->assertTrue($this->model->canRefundRewardPoints());
    }

    public function testGetRefundRewardPointsBalance()
    {
        $refundPointsBalance = "75";
        $creditmemoMock = $this->createMock(\Magento\Sales\Model\Order\Creditmemo::class);
        $orderMock = $this->createPartialMock(\Magento\Sales\Model\Order::class, ['getRewardPointsBalance']);
        $this->registryMock->expects($this->once())->method('registry')->with('current_creditmemo')
            ->willReturn($creditmemoMock);

        $creditmemoMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getRewardPointsBalance')->willReturn($refundPointsBalance);

        $this->assertEquals((int)$refundPointsBalance, $this->model->getRefundRewardPointsBalance());
    }

    public function testIsAutoRefundEnabled()
    {
        $this->rewardHelperMock->expects($this->once())->method('isAutoRefundEnabled')->willReturn(true);
        $this->assertTrue($this->model->isAutoRefundEnabled());
    }
}
