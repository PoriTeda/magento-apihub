<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Model\Total\Creditmemo;

class RewardTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Model\Total\Creditmemo\Reward
     */
    protected $model;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(\Magento\Reward\Model\Total\Creditmemo\Reward::class);
    }

    /**
     * baseRewardCurrecnyAmountLeft == 0
     */
    public function testCollectIfBaseRewardCurrencyAmountLeftIsZero()
    {
        $creditMemoMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Creditmemo::class,
            ['getOrder', 'getBaseGrandTotal']
        );

        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            [
                'getRwrdCurrencyAmountInvoiced',
                'getRwrdCrrncyAmntRefunded',
                'getBaseRwrdCrrncyAmtInvoiced',
                'getBaseRwrdCrrncyAmntRefnded'
            ]
        );
        $creditMemoMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $creditMemoMock->expects($this->never())->method('getBaseGrandTotal');

        $orderMock->expects($this->once())->method('getRwrdCurrencyAmountInvoiced')->willReturn(1);
        $orderMock->expects($this->once())->method('getRwrdCrrncyAmntRefunded')->willReturn(1);
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmtInvoiced')->willReturn(1);
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmntRefnded')->willReturn(1);

        $this->assertEquals($this->model, $this->model->collect($creditMemoMock));
    }

    /**
     *  baseRewardCurrencyAmount == 0
     */
    public function testCollectIfBaseRewardCurrencyAmountIsZero()
    {
        $creditMemoMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Creditmemo::class,
            ['getOrder', 'getBaseGrandTotal']
        );

        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            [
                'getRwrdCurrencyAmountInvoiced',
                'getRwrdCrrncyAmntRefunded',
                'getBaseRwrdCrrncyAmtInvoiced',
                'getBaseRwrdCrrncyAmntRefnded',
                'getBaseRewardCurrencyAmount'
            ]
        );
        $creditMemoMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $creditMemoMock->expects($this->never())->method('getBaseGrandTotal');

        $orderMock->expects($this->once())->method('getRwrdCurrencyAmountInvoiced')->willReturn(1);
        $orderMock->expects($this->once())->method('getRwrdCrrncyAmntRefunded')->willReturn(1);
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmtInvoiced')->willReturn(2);
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmntRefnded')->willReturn(1);
        $orderMock->expects($this->once())->method('getBaseRewardCurrencyAmount')->willReturn(0);

        $this->assertEquals($this->model, $this->model->collect($creditMemoMock));
    }

    /**
     *  baseRewardCurrecnyAmountLeft >= baseGrandTotal
     */
    public function testCollectIfBaseRewardCurrencyAmountLeftGreaterThanZero()
    {
        $creditMemoMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Creditmemo::class,
            [
                'getOrder',
                'getBaseGrandTotal',
                'getGrandTotal',
                'setGrandTotal',
                'setBaseGrandTotal',
                'setAllowZeroGrandTotal',
                'setRewardPointsBalance',
                'setRewardCurrencyAmount',
                'setBaseRewardCurrencyAmount'
            ]
        );
        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            [
                'getRwrdCurrencyAmountInvoiced',
                'getRwrdCrrncyAmntRefunded',
                'getBaseRwrdCrrncyAmtInvoiced',
                'getBaseRwrdCrrncyAmntRefnded',
                'getRewardPointsBalance',
                'getBaseRewardCurrencyAmount',
                'getRewardPointsBalanceRefunded'
            ]
        );
        $creditMemoMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $creditMemoMock->expects($this->exactly(2))->method('getBaseGrandTotal')->willReturn(1);
        $creditMemoMock->expects($this->once())->method('getGrandTotal')->willReturn(10);

        $creditMemoMock->expects($this->once())->method('setGrandTotal')->with(0)->willReturnSelf();
        $creditMemoMock->expects($this->once())->method('setBaseGrandTotal')->with(0)->willReturnSelf();
        $creditMemoMock->expects($this->once())->method('setAllowZeroGrandTotal')->with(true)->willReturnSelf();

        $creditMemoMock->expects($this->once())->method('setRewardPointsBalance')->with(2)->willReturnSelf();
        $creditMemoMock->expects($this->once())->method('setRewardCurrencyAmount')->with(10)->willReturnSelf();
        $creditMemoMock->expects($this->once())->method('setBaseRewardCurrencyAmount')->with(1)->willReturnSelf();

        $orderMock->expects($this->once())->method('getRwrdCurrencyAmountInvoiced')->willReturn(1);
        $orderMock->expects($this->once())->method('getRwrdCrrncyAmntRefunded')->willReturn(1);
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmtInvoiced')->willReturn(20);
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmntRefnded')->willReturn(1);
        $orderMock->expects($this->exactly(2))->method('getBaseRewardCurrencyAmount')->willReturn(1);
        $orderMock->expects($this->exactly(2))->method('getRewardPointsBalance')->willReturn(5);
        $orderMock->expects($this->once())->method('getRewardPointsBalanceRefunded')->willReturn(3);

        $this->assertEquals($this->model, $this->model->collect($creditMemoMock));
    }

    /**
     *  baseRewardCurrecnyAmountLeft < baseGrandTotal
     */
    public function testCollectIfBaseRewardCurrencyAmountLeftLessThanZero()
    {
        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            [
                'getRwrdCurrencyAmountInvoiced',
                'getRwrdCrrncyAmntRefunded',
                'getBaseRwrdCrrncyAmtInvoiced',
                'getBaseRwrdCrrncyAmntRefnded',
                'getRewardPointsBalance',
                'getBaseRewardCurrencyAmount',
                'getRewardPointsBalanceRefunded'
            ]
        );
        $creditMemoMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Creditmemo::class,
            [
                'getOrder',
                'getBaseGrandTotal',
                'getGrandTotal',
                'setGrandTotal',
                'setBaseGrandTotal',
                'setAllowZeroGrandTotal',
                'setRewardPointsBalance',
                'setRewardCurrencyAmount',
                'setBaseRewardCurrencyAmount'
            ]
        );

        $creditMemoMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $creditMemoMock->expects($this->exactly(2))->method('getBaseGrandTotal')->willReturn(30);
        $creditMemoMock->expects($this->once())->method('getGrandTotal')->willReturn(10);

        $creditMemoMock->expects($this->once())->method('setGrandTotal')->with(10)->willReturnSelf();
        $creditMemoMock->expects($this->once())->method('setBaseGrandTotal')->with(11)->willReturnSelf();
        $creditMemoMock->expects($this->never())->method('setAllowZeroGrandTotal');

        $creditMemoMock->expects($this->once())->method('setRewardPointsBalance')->with(2)->willReturnSelf();
        $creditMemoMock->expects($this->once())->method('setRewardCurrencyAmount')->with(0)->willReturnSelf();
        $creditMemoMock->expects($this->once())->method('setBaseRewardCurrencyAmount')->with(19)->willReturnSelf();

        $orderMock->expects($this->once())->method('getRwrdCurrencyAmountInvoiced')->willReturn(1);
        $orderMock->expects($this->once())->method('getRwrdCrrncyAmntRefunded')->willReturn(1);
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmtInvoiced')->willReturn(20);
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmntRefnded')->willReturn(1);
        $orderMock->expects($this->exactly(2))->method('getBaseRewardCurrencyAmount')->willReturn(1);
        $orderMock->expects($this->exactly(2))->method('getRewardPointsBalance')->willReturn(5);
        $orderMock->expects($this->once())->method('getRewardPointsBalanceRefunded')->willReturn(3);

        $this->assertEquals($this->model, $this->model->collect($creditMemoMock));
    }
}
