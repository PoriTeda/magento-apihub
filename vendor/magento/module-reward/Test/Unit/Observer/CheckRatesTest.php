<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Observer;

class CheckRatesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rateFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardDataMock;

    /**
     * @var \Magento\Reward\Observer\CheckRates
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->rateFactoryMock = $this->createPartialMock(\Magento\Reward\Model\Reward\RateFactory::class, ['create']);
        $this->rewardDataMock = $this->createMock(\Magento\Reward\Helper\Data::class);
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);

        $this->subject = $objectManager->getObject(
            \Magento\Reward\Observer\CheckRates::class,
            [
                'rewardData' => $this->rewardDataMock,
                'storeManager' => $this->storeManagerMock,
                'rateFactory' => $this->rateFactoryMock
            ]
        );
    }

    public function testCheckRatesIfRewardsEnabled()
    {
        $groupId = 1;
        $websiteId = 2;

        $storeMock = $this->createPartialMock(\Magento\Store\Model\Store::class, ['getWebsiteId', '__wakeup']);
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $this->storeManagerMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));

        $this->rewardDataMock->expects($this->once())->method('isEnabledOnFront')->will($this->returnValue(true));
        $this->rewardDataMock->expects($this->once())->method('setHasRates')->with(true)->will($this->returnSelf());

        $customerSession = $this->createMock(\Magento\Customer\Model\Session::class);
        $customerSession->expects($this->once())->method('getCustomerGroupId')->will($this->returnValue($groupId));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getCustomerSession']);
        $eventMock->expects($this->once())->method('getCustomerSession')->will($this->returnValue($customerSession));

        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $rateMock = $this->createPartialMock(
            \Magento\Reward\Model\Reward\Rate::class,
            ['fetch', '__wakeup', 'getId', 'reset']
        );
        $this->rateFactoryMock->expects($this->once())->method('create')->will($this->returnValue($rateMock));

        $valueMap = [
            [$groupId, $websiteId, \Magento\Reward\Model\Reward\Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY, $rateMock],
            [$groupId, $websiteId, \Magento\Reward\Model\Reward\Rate::RATE_EXCHANGE_DIRECTION_TO_POINTS, $rateMock],
        ];

        $rateMock->expects($this->exactly(2))->method('fetch')->will($this->returnValueMap($valueMap));
        $rateMock->expects($this->once())->method('reset')->will($this->returnSelf());
        $rateMock->expects($this->exactly(2))->method('getId')->will($this->returnValue(1));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testCheckRatesIfRewardsDisabled()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->rewardDataMock->expects($this->once())->method('isEnabledOnFront')->will($this->returnValue(false));
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
