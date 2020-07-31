<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Model\Reward;

use Magento\Reward\Model\Reward;
use Magento\Reward\Model\SalesRule\RewardPointCounter;

class ReverterTest extends \PHPUnit\Framework\TestCase
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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardResourceFactoryMock;

    /**
     * @var \Magento\Reward\Model\Reward\Reverter
     */
    protected $model;

    /**
     * @var RewardPointCounter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rewardPointCounterMock;

    protected function setUp()
    {
        $this->rewardFactoryMock = $this->createPartialMock(\Magento\Reward\Model\RewardFactory::class, ['create']);
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->rewardResourceFactoryMock = $this->createPartialMock(
            \Magento\Reward\Model\ResourceModel\RewardFactory::class,
            ['create']
        );
        $this->rewardPointCounterMock = $this->getMockBuilder(RewardPointCounter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new \Magento\Reward\Model\Reward\Reverter(
            $this->storeManagerMock,
            $this->rewardFactoryMock,
            $this->rewardResourceFactoryMock,
            $this->rewardPointCounterMock
        );
    }

    public function testRevertRewardPointsForOrderPositive()
    {
        $customerId = 1;
        $storeId = 2;
        $websiteId = 100;

        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['__wakeup', 'getCustomerId', 'getStoreId', 'getRewardPointsBalance']
        );

        $storeMock = $this->createPartialMock(\Magento\Store\Model\Store::class, ['getWebsiteId', '__wakeup']);
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->will($this->returnValue($storeMock));

        $rewardMock = $this->createPartialMock(
            \Magento\Reward\Model\Reward::class,
            [
                '__wakeup',
                'setCustomerId',
                'setWebsiteId',
                'setPointsDelta',
                'setAction',
                'setActionEntity',
                'updateRewardPoints'
            ]
        );
        $this->rewardFactoryMock->expects($this->once())->method('create')->will($this->returnValue($rewardMock));

        $rewardMock->expects($this->once())->method('setCustomerId')->with($customerId)->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('setWebsiteId')->with($websiteId)->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('setPointsDelta')->with(500)->will($this->returnSelf());
        $rewardMock->expects($this->once())
            ->method('setAction')
            ->with(\Magento\Reward\Model\Reward::REWARD_ACTION_REVERT)
            ->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('setActionEntity')->with($orderMock)->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('updateRewardPoints')->will($this->returnSelf());

        $orderMock->expects($this->exactly(2))->method('getCustomerId')->will($this->returnValue($customerId));
        $orderMock->expects($this->once())->method('getStoreId')->will($this->returnValue($storeId));
        $orderMock->expects($this->once())->method('getRewardPointsBalance')->will($this->returnValue(500));

        $this->assertEquals($this->model, $this->model->revertRewardPointsForOrder($orderMock));
    }

    public function testRevertRewardPointsIfNoCustomerId()
    {
        $orderMock = $this->createPartialMock(\Magento\Sales\Model\Order::class, ['__wakeup', 'getCustomerId']);
        $orderMock->expects($this->once())->method('getCustomerId')->will($this->returnValue(null));
        $this->assertEquals($this->model, $this->model->revertRewardPointsForOrder($orderMock));
    }

    public function testRevertEarnedPointsForOrder()
    {
        $appliedRuleIds = '1,2,1,1,3,4,3';
        $pointsDelta = -30;
        $customerId = 42;
        $storeId = 1;
        $websiteId = 1;

        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $rewardMock = $this->createPartialMock(
            \Magento\Reward\Model\Reward::class,
            ['setCustomerId', 'setWebsiteId', 'setPointsDelta', 'setAction', 'setActionEntity', 'updateRewardPoints']
        );

        $orderMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($appliedRuleIds);
        $this->rewardPointCounterMock->expects(self::any())
            ->method('getPointsForRules')
            ->with(
                [
                    0 => '1',
                    1 => '2',
                    4 => '3',
                    5 => '4',
                ]
            )
            ->willReturn(-$pointsDelta);

        $orderMock->expects($this->once())->method('getCustomerIsGuest')->willReturn(false);

        $this->rewardFactoryMock->expects($this->once())->method('create')->willReturn($rewardMock);
        $orderMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $rewardMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $orderMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $this->storeManagerMock->expects($this->once())->method('getStore')->with($storeId)->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $rewardMock->expects($this->once())->method('setWebsiteId')->with($websiteId)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setPointsDelta')->with($pointsDelta)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setAction')->with(Reward::REWARD_ACTION_REVERT)->willReturnSelf();
        $rewardMock->expects($this->once())->method('setActionEntity')->with($orderMock)->willReturnSelf();
        $rewardMock->expects($this->once())->method('updateRewardPoints');

        $this->assertEquals($this->model, $this->model->revertEarnedRewardPointsForOrder($orderMock));
    }
}
