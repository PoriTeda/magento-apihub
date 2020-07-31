<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Observer;

class RevertRewardPointsForAllOrdersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $reverterMock;

    /**
     * @var \Magento\Reward\Observer\RevertRewardPointsForAllOrders
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->reverterMock = $this->createMock(\Magento\Reward\Model\Reward\Reverter::class);
        $this->subject = $objectManager->getObject(
            \Magento\Reward\Observer\RevertRewardPointsForAllOrders::class,
            ['reverter' => $this->reverterMock]
        );
    }

    public function testRevertRewardPointsIfNoOrders()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrders']);
        $eventMock->expects($this->once())->method('getOrders')->will($this->returnValue([]));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testRevertRewardPoints()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrders']);
        $eventMock->expects($this->once())->method('getOrders')->will($this->returnValue([$orderMock]));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $this->reverterMock->expects($this->once())
            ->method('revertRewardPointsForOrder')
            ->with($orderMock)
            ->will($this->returnSelf());

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
