<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Observer;

class InvitationToCustomerTest extends \PHPUnit\Framework\TestCase
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
    protected $rewardDataMock;

    /**
     * @var \Magento\Reward\Observer\InvitationToCustomer
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->rewardDataMock = $this->createPartialMock(\Magento\Reward\Helper\Data::class, ['isEnabledOnFront']);
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->rewardFactoryMock = $this->createPartialMock(\Magento\Reward\Model\RewardFactory::class, ['create']);

        $this->subject = $objectManager->getObject(
            \Magento\Reward\Observer\InvitationToCustomer::class,
            [
                'rewardData' => $this->rewardDataMock,
                'storeManager' => $this->storeManagerMock,
                'rewardFactory' => $this->rewardFactoryMock
            ]
        );
    }

    public function testUpdateRewardsIfRewardsDisabledOnFront()
    {
        $storeId = 1;
        $websiteId = 2;
        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent', '__wakeup']);
        $invitationMock = $this->createPartialMock(
            \Magento\Invitation\Model\Invitation::class,
            ['getStoreId', '__wakeup']
        );

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getInvitation']);
        $eventMock->expects($this->once())->method('getInvitation')->will($this->returnValue($invitationMock));
        $invitationMock->expects($this->once())->method('getStoreId')->will($this->returnValue($storeId));

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->will($this->returnValue($storeMock));

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->will($this->returnValue(false));

        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testUpdateRewardsIfCustomerIdNotSet()
    {
        $storeId = 1;
        $websiteId = 2;
        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent', '__wakeup']);
        $invitationMock = $this->createPartialMock(
            \Magento\Invitation\Model\Invitation::class,
            ['getStoreId', '__wakeup', 'getCustomerId']
        );

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getInvitation']);
        $eventMock->expects($this->once())->method('getInvitation')->will($this->returnValue($invitationMock));
        $invitationMock->expects($this->once())->method('getStoreId')->will($this->returnValue($storeId));

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->will($this->returnValue($storeMock));

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->will($this->returnValue(true));

        $invitationMock->expects($this->once())->method('getCustomerId')->will($this->returnValue(null));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testUpdateRewardsIfReferralIdNotSet()
    {
        $customerId = 100;
        $storeId = 1;
        $websiteId = 2;
        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent', '__wakeup']);
        $invitationMock = $this->createPartialMock(
            \Magento\Invitation\Model\Invitation::class,
            ['getStoreId', '__wakeup', 'getCustomerId', 'getReferralId']
        );

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getInvitation']);
        $eventMock->expects($this->once())->method('getInvitation')->will($this->returnValue($invitationMock));
        $invitationMock->expects($this->once())->method('getStoreId')->will($this->returnValue($storeId));

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->will($this->returnValue($storeMock));

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->will($this->returnValue(true));

        $invitationMock->expects($this->once())->method('getCustomerId')->will($this->returnValue($customerId));
        $invitationMock->expects($this->once())->method('getReferralId')->will($this->returnValue(null));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testUpdateRewardsSuccess()
    {
        $customerId = 100;
        $storeId = 1;
        $websiteId = 2;
        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent', '__wakeup']);
        $invitationMock = $this->createPartialMock(
            \Magento\Invitation\Model\Invitation::class,
            ['getStoreId', '__wakeup', 'getCustomerId', 'getReferralId']
        );

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getInvitation']);
        $eventMock->expects($this->once())->method('getInvitation')->will($this->returnValue($invitationMock));
        $invitationMock->expects($this->once())->method('getStoreId')->will($this->returnValue($storeId));

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->will($this->returnValue($storeMock));

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->will($this->returnValue(true));

        $invitationMock->expects($this->exactly(2))->method('getCustomerId')->will($this->returnValue($customerId));
        $invitationMock->expects($this->once())->method('getReferralId')->will($this->returnValue(200));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $rewardMock = $this->createPartialMock(
            \Magento\Reward\Model\Reward::class,
            ['setCustomerId', 'setActionEntity', 'setWebsiteId', 'setAction', 'updateRewardPoints', '__wakeup']
        );
        $this->rewardFactoryMock->expects($this->once())->method('create')->will($this->returnValue($rewardMock));

        $rewardMock->expects($this->once())->method('setCustomerId')->with($customerId)->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('setWebsiteId')->with($websiteId)->will($this->returnSelf());
        $rewardMock->expects($this->once())
            ->method('setAction')
            ->with(\Magento\Reward\Model\Reward::REWARD_ACTION_INVITATION_CUSTOMER)
            ->will($this->returnSelf());
        $rewardMock->expects($this->once())
            ->method('setActionEntity')
            ->with($invitationMock)
            ->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('updateRewardPoints')->will($this->returnSelf());

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
