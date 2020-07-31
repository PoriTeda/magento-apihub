<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Observer;

use Magento\Reward\Model\Reward;
use Magento\Reward\Model\SalesRule\RewardPointCounter;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EarnForOrderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Observer\EarnForOrder
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_restrictionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_modelFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_resourceFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_observerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardHelperMock;

    /**
     * @var RewardPointCounter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rewardPointCounterMock;

    protected function setUp()
    {
        $this->_restrictionMock = $this->createMock(\Magento\Reward\Observer\PlaceOrder\RestrictionInterface::class);
        $this->_storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManager::class);
        $this->rewardHelperMock = $this->createMock(\Magento\Reward\Helper\Data::class);
        $this->_modelFactoryMock = $this->createPartialMock(\Magento\Reward\Model\RewardFactory::class, ['create']);
        $this->_resourceFactoryMock = $this->createPartialMock(
            \Magento\Reward\Model\ResourceModel\RewardFactory::class,
            ['create']
        );

        $this->_observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $this->rewardPointCounterMock = $this->getMockBuilder(RewardPointCounter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_model = new \Magento\Reward\Observer\EarnForOrder(
            $this->_restrictionMock,
            $this->_storeManagerMock,
            $this->_modelFactoryMock,
            $this->_resourceFactoryMock,
            $this->rewardHelperMock,
            $this->rewardPointCounterMock
        );
    }

    public function testEarnForOrderRestricted()
    {
        $this->_restrictionMock->expects($this->once())->method('isAllowed')->willReturn(false);
        $this->_observerMock->expects($this->never())->method('getEvent');

        $this->_model->execute($this->_observerMock);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testEarnForOrder()
    {
        $apliedRuleIds = '1,2,1,1,3,4,3';
        $pointsDelta = 30;
        $customerId = 42;
        $websiteId = 1;
        $historyEntry = __('Customer earned promotion extra %1.', $pointsDelta);

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrder']);
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $historyMock = $this->createPartialMock(\Magento\Sales\Model\Order\Status\History::class, ['save']);
        $rewardModelMock = $this->createPartialMock(
            \Magento\Reward\Model\Reward::class,
            ['setCustomerId', 'setWebsiteId', 'setPointsDelta', 'setAction', 'setActionEntity', 'updateRewardPoints']
        );

        $this->_observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($apliedRuleIds);

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
            ->willReturn($pointsDelta);

        $this->_modelFactoryMock->expects($this->once())->method('create')->willReturn($rewardModelMock);
        $orderMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $rewardModelMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $this->_storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $rewardModelMock->expects($this->once())->method('setWebsiteId')->with($websiteId)->willReturnSelf();
        $rewardModelMock->expects($this->once())->method('setPointsDelta')->with($pointsDelta)->willReturnSelf();
        $rewardModelMock->expects($this->once())->method('setAction')->with(Reward::REWARD_ACTION_SALESRULE)
            ->willReturnSelf();
        $rewardModelMock->expects($this->once())->method('setActionEntity')->with($orderMock)->willReturnSelf();
        $rewardModelMock->expects($this->once())->method('updateRewardPoints');

        $this->rewardHelperMock->expects($this->once())->method('formatReward')->with($pointsDelta)
            ->willReturn($pointsDelta);
        $orderMock->expects($this->once())->method('addStatusHistoryComment')->with($historyEntry)
            ->willReturn($historyMock);

        $this->_model->execute($this->_observerMock);
    }

    public function testEarnForOrderWithNoSalesRule()
    {
        $apliedRuleIds = '';
        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrder']);
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);

        $this->_observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($apliedRuleIds);

        $this->_modelFactoryMock->expects($this->never())->method('create');

        $this->_model->execute($this->_observerMock);
    }
}
