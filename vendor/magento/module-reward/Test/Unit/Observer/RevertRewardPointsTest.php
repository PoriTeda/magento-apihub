<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Observer;

class RevertRewardPointsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $reverterMock;

    /**
     * @var \Magento\Reward\Observer\RevertRewardPoints
     */
    protected $model;

    protected function setUp()
    {
        $this->reverterMock = $this->createMock(\Magento\Reward\Model\Reward\Reverter::class);
        $this->model = new \Magento\Reward\Observer\RevertRewardPoints($this->reverterMock);
    }

    public function testRevertRewardPointsIfOrderIsNull()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrder']);
        $eventMock->expects($this->once())->method('getOrder')->will($this->returnValue(null));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }

    public function testRevertRewardPoints()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrder']);
        $eventMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $this->reverterMock->expects($this->once())
            ->method('revertRewardPointsForOrder')
            ->with($orderMock)
            ->will($this->returnSelf());
        $this->reverterMock->expects($this->once())->method('revertEarnedRewardPointsForOrder')->with($orderMock)
            ->willReturnSelf();

        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }
}
