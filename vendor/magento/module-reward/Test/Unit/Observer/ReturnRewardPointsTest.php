<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Observer;

class ReturnRewardPointsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \Magento\Reward\Observer\ReturnRewardPoints
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->rewardFactoryMock = $this->createPartialMock(\Magento\Reward\Model\RewardFactory::class, ['create']);
        $this->subject = $objectManager->getObject(
            \Magento\Reward\Observer\ReturnRewardPoints::class,
            ['storeManager' => $this->storeManagerMock, 'rewardFactory' => $this->rewardFactoryMock]
        );
    }

    public function testReturnRewardPointsIfPointsBalanceIsZero()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['getRewardPointsBalance', '__wakeup']
        );
        $orderMock->expects($this->once())->method('getRewardPointsBalance')->will($this->returnValue(0));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrder']);
        $eventMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testReturnRewardPoints()
    {
        $customerId = 100;
        $storeId = 1;
        $websiteId = 2;
        $pointsBalance = 100;

        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['getRewardPointsBalance', '__wakeup', 'getCustomerId', 'getStoreId']
        );
        $orderMock->expects($this->exactly(2))
            ->method('getRewardPointsBalance')
            ->will($this->returnValue($pointsBalance));
        $orderMock->expects($this->once())->method('getCustomerId')->will($this->returnValue($customerId));
        $orderMock->expects($this->once())->method('getStoreId')->will($this->returnValue($storeId));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrder']);
        $eventMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $rewardMock = $this->createPartialMock(
            \Magento\Reward\Model\Reward::class,
            [
                'setCustomerId',
                'setActionEntity',
                'setWebsiteId',
                'setAction',
                'updateRewardPoints',
                '__wakeup',
                'setPointsDelta'
            ]
        );
        $this->rewardFactoryMock->expects($this->once())->method('create')->will($this->returnValue($rewardMock));

        $rewardMock->expects($this->once())->method('setCustomerId')->with($customerId)->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('setWebsiteId')->with($websiteId)->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('setPointsDelta')->with($pointsBalance)->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('setActionEntity')->with($orderMock)->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('updateRewardPoints')->will($this->returnSelf());
        $rewardMock->expects($this->once())
            ->method('setAction')
            ->with(\Magento\Reward\Model\Reward::REWARD_ACTION_REVERT)
            ->will($this->returnSelf());

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->will($this->returnValue($storeMock));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
