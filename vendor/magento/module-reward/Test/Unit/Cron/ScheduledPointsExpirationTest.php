<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Cron;

class ScheduledPointsExpirationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardDataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $historyItemFactoryMock;

    /**
     * @var \Magento\Reward\Cron\ScheduledPointsExpiration
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->rewardDataMock = $this->createPartialMock(
            \Magento\Reward\Helper\Data::class,
            ['isEnabled', 'isEnabledOnFront', 'getGeneralConfig']
        );
        $this->historyItemFactoryMock = $this->createPartialMock(
            \Magento\Reward\Model\ResourceModel\Reward\HistoryFactory::class,
            ['create']
        );

        $this->subject = $objectManager->getObject(
            \Magento\Reward\Cron\ScheduledPointsExpiration::class,
            [
                'storeManager' => $this->storeManagerMock,
                '_historyItemFactory' => $this->historyItemFactoryMock,
                'rewardData' => $this->rewardDataMock
            ]
        );
    }

    public function testMakePointsExpiredIfRewardsDisabled()
    {
        $this->rewardDataMock->expects($this->once())->method('isEnabled')->will($this->returnValue(false));
        $this->assertEquals($this->subject, $this->subject->execute());
    }

    public function testMakePointsExpiredIfRewardsDisabledOnFront()
    {
        $websiteId = 1;

        $this->rewardDataMock->expects($this->once())->method('isEnabled')->will($this->returnValue(true));
        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->will($this->returnValue(false));

        $websiteMock = $this->createMock(\Magento\Store\Model\Website::class);
        $websiteMock->expects($this->once())->method('getId')->will($this->returnValue($websiteId));

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->will($this->returnValue([$websiteMock]));

        $this->assertEquals($this->subject, $this->subject->execute());
    }

    public function testMakePointsExpiredSuccess()
    {
        $websiteId = 1;
        $expireType = 'expire_type';

        $this->rewardDataMock->expects($this->once())->method('isEnabled')->will($this->returnValue(true));

        $websiteMock = $this->createMock(\Magento\Store\Model\Website::class);
        $websiteMock->expects($this->exactly(3))->method('getId')->will($this->returnValue($websiteId));

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->will($this->returnValue([$websiteMock]));

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->will($this->returnValue(true));

        $this->rewardDataMock->expects($this->once())
            ->method('getGeneralConfig')
            ->with('expiry_calculation', $websiteId)
            ->will($this->returnValue($expireType));

        $rewardHistoryMock = $this->createMock(\Magento\Reward\Model\ResourceModel\Reward\History::class);
        $this->historyItemFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($rewardHistoryMock));

        $rewardHistoryMock->expects($this->once())
            ->method('expirePoints')
            ->with($websiteId, $expireType, 100)
            ->will($this->returnSelf());

        $this->assertEquals($this->subject, $this->subject->execute());
    }
}
