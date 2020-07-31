<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Reward\Observer\CreditmemoRefund;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class CreditmemoRefundTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Creditmemo|MockObject
     */
    private $creditMemo;

    /**
     * @var Order|MockObject
     */
    private $order;

    /**
     * @var Observer|MockObject
     */
    private $observer;

    /**
     * @var CreditmemoRefund
     */
    private $subject;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->creditMemo = $this->createPartialMock(
            Creditmemo::class,
            ['getBaseRewardCurrencyAmount', 'getOrder']
        );
        $this->order = $this->createPartialMock(
            Order::class,
            ['getBaseRwrdCrrncyAmntRefnded', 'getBaseRwrdCrrncyAmtInvoiced', 'setForcedCanCreditmemo']
        );
        $this->observer = $this->createPartialMock(Observer::class, ['getEvent']);

        /** @var Event|MockObject $event */
        $event = $this->createPartialMock(Event::class, ['getCreditmemo']);
        $event->method('getCreditmemo')
            ->willReturn($this->creditMemo);
        $this->observer->method('getEvent')
            ->willReturn($event);
        $this->creditMemo->method('getOrder')
            ->willReturn($this->order);

        $this->subject = new CreditmemoRefund();
    }

    public function testCreditMemoRefund()
    {
        $this->order->method('getBaseRwrdCrrncyAmntRefnded')
            ->willReturn(10);
        $this->order->method('getBaseRwrdCrrncyAmtInvoiced')
            ->willReturn(25);
        $this->order->method('setForcedCanCreditmemo')
            ->with(false)
            ->willReturnSelf();

        $this->creditMemo->method('getBaseRewardCurrencyAmount')
            ->willReturn(15);

        $this->subject->execute($this->observer);
    }
}
