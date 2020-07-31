<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Observer;

class OrderLoadAfterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Observer\OrderLoadAfter
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->subject = $objectManager->getObject(\Magento\Reward\Observer\OrderLoadAfter::class);
    }

    public function testSetForcedCreditmemoFlagIfOrderCanUnhold()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $orderMock = $this->createPartialMock(\Magento\Sales\Model\Order::class, ['canUnhold', '__wakeup']);
        $orderMock->expects($this->once())->method('canUnhold')->will($this->returnValue(true));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrder']);
        $eventMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));

        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testSetForcedCreditmemoFlagIfOrderIsCanceled()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['canUnhold', '__wakeup', 'isCanceled']
        );
        $orderMock->expects($this->once())->method('canUnhold')->will($this->returnValue(false));
        $orderMock->expects($this->once())->method('isCanceled')->will($this->returnValue(true));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrder']);
        $eventMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));

        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testSetForcedCreditmemoFlagIfOrderStateIsClosed()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['canUnhold', '__wakeup', 'isCanceled', 'getState']
        );
        $orderMock->expects($this->once())->method('canUnhold')->will($this->returnValue(false));
        $orderMock->expects($this->once())->method('isCanceled')->will($this->returnValue(false));
        $orderMock->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(\Magento\Sales\Model\Order::STATE_CLOSED));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrder']);
        $eventMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));

        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testSetForcedCreditmemoFlagIfRewardAmountIsZero()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $orderMock = $this->createPartialMock(\Magento\Sales\Model\Order::class, [
                'canUnhold',
                '__wakeup',
                'isCanceled',
                'getState',
                'getBaseRwrdCrrncyAmntRefnded',
                'getBaseRwrdCrrncyAmtInvoiced'
            ]);
        $orderMock->expects($this->once())->method('canUnhold')->will($this->returnValue(false));
        $orderMock->expects($this->once())->method('isCanceled')->will($this->returnValue(false));
        $orderMock->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(\Magento\Sales\Model\Order::STATE_PROCESSING));

        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmtInvoiced')->will($this->returnValue(100));
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmntRefnded')->will($this->returnValue(100));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrder']);
        $eventMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));

        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testSetForcedCreditmemoFlagSuccess()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            [
                'canUnhold',
                '__wakeup',
                'isCanceled',
                'getState',
                'getBaseRwrdCrrncyAmntRefnded',
                'getBaseRwrdCrrncyAmtInvoiced',
                'setForcedCanCreditmemo'
            ]
        );
        $orderMock->expects($this->once())->method('canUnhold')->will($this->returnValue(false));
        $orderMock->expects($this->once())->method('isCanceled')->will($this->returnValue(false));
        $orderMock->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(\Magento\Sales\Model\Order::STATE_PROCESSING));

        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmtInvoiced')->will($this->returnValue(150));
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmntRefnded')->will($this->returnValue(100));
        $orderMock->expects($this->once())->method('setForcedCanCreditmemo')->with(true)->will($this->returnSelf());

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrder']);
        $eventMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));

        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
