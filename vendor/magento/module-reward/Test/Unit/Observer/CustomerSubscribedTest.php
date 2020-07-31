<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Observer;

class CustomerSubscribedTest extends \PHPUnit\Framework\TestCase
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
     * @var \Magento\Reward\Observer\CustomerSubscribed
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->rewardDataMock = $this->createMock(\Magento\Reward\Helper\Data::class);
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->rewardFactoryMock = $this->createPartialMock(\Magento\Reward\Model\RewardFactory::class, ['create']);

        $this->subject = $objectManager->getObject(
            \Magento\Reward\Observer\CustomerSubscribed::class,
            [
                'rewardData' => $this->rewardDataMock,
                'storeManager' => $this->storeManagerMock,
                'rewardFactory' => $this->rewardFactoryMock
            ]
        );
    }

    public function testUpdateRewardsAfterSubscribtionIfSubscriberExist()
    {
        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent', '__wakeup']);
        $subscriberMock = $this->createPartialMock(
            \Magento\Newsletter\Model\Subscriber::class,
            ['isObjectNew', '__wakeup']
        );

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getSubscriber']);
        $eventMock->expects($this->once())->method('getSubscriber')->will($this->returnValue($subscriberMock));

        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));
        $subscriberMock->expects($this->once())->method('isObjectNew')->will($this->returnValue(false));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testUpdateRewardsAfterSubscribtionIfCustomerNotExist()
    {
        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent', '__wakeup']);
        $subscriberMock = $this->createPartialMock(
            \Magento\Newsletter\Model\Subscriber::class,
            ['isObjectNew', '__wakeup', 'getCustomerId']
        );

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getSubscriber']);
        $eventMock->expects($this->once())->method('getSubscriber')->will($this->returnValue($subscriberMock));

        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));
        $subscriberMock->expects($this->once())->method('isObjectNew')->will($this->returnValue(true));
        $subscriberMock->expects($this->once())->method('getCustomerId')->will($this->returnValue(null));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testUpdateRewardsAfterSubscribtionIfRewardDisabledOnFront()
    {
        $customerId = 10;
        $storeId = 2;
        $websiteId = 1;
        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent', '__wakeup']);
        $subscriberMock = $this->createPartialMock(
            \Magento\Newsletter\Model\Subscriber::class,
            ['isObjectNew', '__wakeup', 'getCustomerId', 'getStoreId']
        );

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getSubscriber']);
        $eventMock->expects($this->once())->method('getSubscriber')->will($this->returnValue($subscriberMock));

        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));
        $subscriberMock->expects($this->once())->method('isObjectNew')->will($this->returnValue(true));
        $subscriberMock->expects($this->once())->method('getCustomerId')->will($this->returnValue($customerId));
        $subscriberMock->expects($this->once())->method('getStoreId')->will($this->returnValue($storeId));

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

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testUpdateRewardsAfterSubscribtionSuccess()
    {
        $customerId = 10;
        $storeId = 2;
        $websiteId = 1;
        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent', '__wakeup']);
        $subscriberMock = $this->createPartialMock(
            \Magento\Newsletter\Model\Subscriber::class,
            ['isObjectNew', '__wakeup', 'getCustomerId', 'getStoreId']
        );

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getSubscriber']);
        $eventMock->expects($this->once())->method('getSubscriber')->will($this->returnValue($subscriberMock));

        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));
        $subscriberMock->expects($this->once())->method('isObjectNew')->will($this->returnValue(true));
        $subscriberMock->expects($this->exactly(2))->method('getCustomerId')->will($this->returnValue($customerId));
        $subscriberMock->expects($this->exactly(2))->method('getStoreId')->will($this->returnValue($storeId));

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

        $rewardMock = $this->createPartialMock(
            \Magento\Reward\Model\Reward::class,
            ['setCustomerId', 'setActionEntity', 'setStore', 'setAction', 'updateRewardPoints', '__wakeup']
        );
        $this->rewardFactoryMock->expects($this->once())->method('create')->will($this->returnValue($rewardMock));

        $rewardMock->expects($this->once())->method('setCustomerId')->with($customerId)->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('setStore')->with($storeId)->will($this->returnSelf());
        $rewardMock->expects($this->once())
            ->method('setAction')
            ->with(\Magento\Reward\Model\Reward::REWARD_ACTION_NEWSLETTER)
            ->will($this->returnSelf());
        $rewardMock->expects($this->once())
            ->method('setActionEntity')
            ->with($subscriberMock)
            ->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('updateRewardPoints')->will($this->returnSelf());

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
