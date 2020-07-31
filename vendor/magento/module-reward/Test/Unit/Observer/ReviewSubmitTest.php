<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Observer;

class ReviewSubmitTest extends \PHPUnit\Framework\TestCase
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
     * @var \Magento\Reward\Observer\ReviewSubmit
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->rewardFactoryMock = $this->createPartialMock(\Magento\Reward\Model\RewardFactory::class, ['create']);
        $this->rewardDataMock = $this->createMock(\Magento\Reward\Helper\Data::class);

        $this->subject = $objectManager->getObject(
            \Magento\Reward\Observer\ReviewSubmit::class,
            [
                'storeManager' => $this->storeManagerMock,
                'rewardFactory' => $this->rewardFactoryMock,
                'rewardData' => $this->rewardDataMock
            ]
        );
    }

    public function testUpdateRewardPointsWhenRewardDisabledInFront()
    {
        $websiteId = 2;

        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $reviewMock = $this->createMock(\Magento\Review\Model\Review::class);

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getObject']);
        $eventMock->expects($this->once())->method('getObject')->will($this->returnValue($reviewMock));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $this->storeManagerMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->will($this->returnValue(false));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testUpdateRewardPointsIfReviewNotApproved()
    {
        $websiteId = 2;

        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $reviewMock = $this->createMock(\Magento\Review\Model\Review::class);
        $reviewMock->expects($this->once())->method('isApproved')->will($this->returnValue(false));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getObject']);
        $eventMock->expects($this->once())->method('getObject')->will($this->returnValue($reviewMock));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $this->storeManagerMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->will($this->returnValue(true));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testUpdateRewardPointsIfCustomerIdNotSet()
    {
        $websiteId = 2;

        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $reviewMock = $this->createPartialMock(
            \Magento\Review\Model\Review::class,
            ['getCustomerId', 'isApproved', '__wakeup']
        );
        $reviewMock->expects($this->once())->method('isApproved')->will($this->returnValue(true));
        $reviewMock->expects($this->once())->method('getCustomerId')->will($this->returnValue(null));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getObject']);
        $eventMock->expects($this->once())->method('getObject')->will($this->returnValue($reviewMock));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $this->storeManagerMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->will($this->returnValue(true));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testUpdateRewardPoints()
    {
        $storeId = 1;
        $websiteId = 2;
        $customerId = 100;

        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $reviewMock = $this->createPartialMock(
            \Magento\Review\Model\Review::class,
            ['getCustomerId', 'isApproved', '__wakeup', 'getStoreId']
        );

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getObject']);
        $eventMock->expects($this->once())->method('getObject')->will($this->returnValue($reviewMock));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $this->storeManagerMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->will($this->returnValue(true));

        $reviewMock->expects($this->once())->method('isApproved')->will($this->returnValue(true));
        $reviewMock->expects($this->exactly(2))->method('getCustomerId')->will($this->returnValue($customerId));
        $reviewMock->expects($this->exactly(2))->method('getStoreId')->will($this->returnValue($storeId));

        $rewardMock = $this->createPartialMock(
            \Magento\Reward\Model\Reward::class,
            ['setCustomerId', 'setActionEntity', 'setStore', 'setAction', 'updateRewardPoints', '__wakeup']
        );
        $this->rewardFactoryMock->expects($this->once())->method('create')->will($this->returnValue($rewardMock));

        $rewardMock->expects($this->once())->method('setCustomerId')->with($customerId)->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('setStore')->with($storeId)->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('setActionEntity')->with($reviewMock)->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('setAction')
            ->with(\Magento\Reward\Model\Reward::REWARD_ACTION_REVIEW)
            ->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('updateRewardPoints')->will($this->returnSelf());

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
