<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Model\Reward\Refund;

use Magento\Reward\Model\SalesRule\RewardPointCounter;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SalesRuleRefundTest extends \PHPUnit\Framework\TestCase
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
    protected $rewardHelperMock;

    /**
     * @var \Magento\Reward\Model\Reward\Refund\SalesRuleRefund
     */
    protected $subject;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var RewardPointCounter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rewardPointCounterMock;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->rewardFactoryMock = $this->createPartialMock(
            \Magento\Reward\Model\RewardFactory::class,
            ['create', '__wakeup']
        );
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->rewardHelperMock = $this->createMock(\Magento\Reward\Helper\Data::class);
        $this->rewardPointCounterMock = $this->getMockBuilder(RewardPointCounter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subject = $this->objectManager->getObject(
            \Magento\Reward\Model\Reward\Refund\SalesRuleRefund::class,
            [
                'rewardFactory' => $this->rewardFactoryMock,
                'storeManager' => $this->storeManagerMock,
                'rewardHelper' => $this->rewardHelperMock,
                'rewardPointCounter' => $this->rewardPointCounterMock,
            ]
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRefundSuccess()
    {
        $websiteId = 2;
        $customerId = 10;
        $appliedRuleIds = '1,2,1,1,3,4,3';

        $orderMock = $this->createPartialMock(\Magento\Sales\Model\Order::class, [
                '__wakeup',
                'getCreditmemosCollection',
                'getTotalQtyOrdered',
                'getStoreId',
                'getCustomerId',
                'getAppliedRuleIds'
            ]);
        $creditmemoMock = $this->createPartialMock(\Magento\Sales\Model\Order\Creditmemo::class, [
                '__wakeup',
                'getOrder',
                'getAutomaticallyCreated',
                'setRewardPointsBalanceRefund',
                'getRewardPointsBalance'
            ]);

        $creditmemoMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));
        $creditmemo = $this->createPartialMock(
            \Magento\Sales\Model\Order\Creditmemo::class,
            ['__wakeup', 'getData', 'getAllItems']
        );
        $creditmemoCollectionMock = $this->objectManager->getCollectionMock(
            \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection::class,
            [$creditmemo]
        );
        $orderMock->expects($this->atLeastOnce())
            ->method('getCreditmemosCollection')
            ->will($this->returnValue($creditmemoCollectionMock));
        $itemMock = $this->createPartialMock(\Magento\Sales\Model\Order\Creditmemo\Item::class, ['getQty', '__wakeup']);
        $creditmemo->expects($this->atLeastOnce())->method('getAllItems')->will($this->returnValue([$itemMock]));

        $itemMock->expects($this->atLeastOnce())->method('getQty')->will($this->returnValue(5));
        $creditmemoMock->expects($this->exactly(2))->method('getAutomaticallyCreated')->will($this->returnValue(true));
        $this->rewardHelperMock->expects($this->once())->method('isAutoRefundEnabled')->will($this->returnValue(true));

        $creditmemoMock->expects($this->once())->method('getRewardPointsBalance')->will($this->returnValue(100));
        $creditmemoMock->expects($this->once())
            ->method('setRewardPointsBalanceRefund')
            ->with(100)
            ->will($this->returnSelf());

        $orderMock->expects($this->once())->method('getTotalQtyOrdered')->will($this->returnValue(5));
        $rewardMock = $this->createPartialMock(
            \Magento\Reward\Model\Reward::class,
            ['__wakeup', 'setActionEntity', 'loadByCustomer', 'getPointsBalance', 'save', 'getResource']
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
            ->willReturn(100);

        $this->rewardFactoryMock->expects($this->exactly(2))->method('create')->will($this->returnValue($rewardMock));
        $orderMock->expects($this->exactly(2))->method('getStoreId')->will($this->returnValue(1));

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->storeManagerMock->expects($this->exactly(2))->method('getStore')->will($this->returnValue($storeMock));
        $storeMock->expects($this->exactly(2))->method('getWebsiteId')->will($this->returnValue($websiteId));
        $orderMock->expects($this->exactly(2))->method('getCustomerId')->will($this->returnValue($customerId));

        $rewardMock->expects($this->once())->method('loadByCustomer')->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('getPointsBalance')->will($this->returnValue(500));
        $rewardMock->expects($this->once())->method('setActionEntity')->with($orderMock)->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('save')->will($this->returnSelf());
        $this->subject->refund($creditmemoMock);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRefundWhenAutoRefundDisabled()
    {
        $appliedRuleIds = '1,2,1,1,3,4,3';
        $websiteId = 2;
        $customerId = 10;
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $creditmemoMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Creditmemo::class,
            [
                '__wakeup',
                'getOrder',
                'getAutomaticallyCreated',
                'getRewardPointsBalance',
                'setRewardPointsBalanceRefund'
            ]
        );
        $rewardMock = $this->createPartialMock(
            \Magento\Reward\Model\Reward::class,
            ['__wakeup', 'setActionEntity', 'loadByCustomer', 'getPointsBalance', 'save', 'getResource']
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
            ->willReturn(100);

        $this->rewardFactoryMock->expects($this->once())->method('create')->will($this->returnValue($rewardMock));
        $orderMock->expects($this->once())->method('getStoreId')->will($this->returnValue(1));

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->storeManagerMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $orderMock->expects($this->once())->method('getCustomerId')->will($this->returnValue($customerId));

        $rewardMock->expects($this->once())->method('loadByCustomer')->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('getPointsBalance')->will($this->returnValue(500));
        $creditmemoMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));
        $creditmemoMock->expects($this->exactly(2))->method('getAutomaticallyCreated')->will($this->returnValue(true));
        $creditmemoMock->expects($this->once())->method('getRewardPointsBalance')->will($this->returnValue(100));
        $creditmemoMock->expects($this->once())
            ->method('setRewardPointsBalanceRefund')
            ->with(100)
            ->will($this->returnSelf());

        $creditmemo = $this->createPartialMock(
            \Magento\Sales\Model\Order\Creditmemo::class,
            ['__wakeup', 'getData', 'getAllItems']
        );

        $creditmemoCollectionMock = $this->objectManager->getCollectionMock(
            \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection::class,
            [$creditmemo]
        );
        $orderMock->expects($this->atLeastOnce())
            ->method('getCreditmemosCollection')
            ->will($this->returnValue($creditmemoCollectionMock));

        $itemMock =
            $this->createPartialMock(\Magento\Sales\Model\Order\Creditmemo\Item::class, ['getQty', '__wakeup']);
        $itemMock->expects($this->atLeastOnce())->method('getQty')->will($this->returnValue(3));
        $creditmemo->expects($this->atLeastOnce())->method('getAllItems')->will($this->returnValue([$itemMock]));

        $this->rewardHelperMock->expects($this->once())->method('isAutoRefundEnabled')->will($this->returnValue(false));
        $this->subject->refund($creditmemoMock);
    }

    public function testPartialRefund()
    {
        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['__wakeup', 'getTotalQtyOrdered', 'getCreditmemosCollection', 'getAppliedRuleIds']
        );

        $appliedRuleIds = '1,1';
        $creditmemoMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Creditmemo::class,
            [
                '__wakeup',
                'getOrder',
                'getAutomaticallyCreated',
                'getRewardPointsBalance',
                'setRewardPointsBalanceRefund',
            ]
        );
        $rewardMock = $this->createPartialMock(
            \Magento\Reward\Model\Reward::class,
            ['__wakeup', 'setActionEntity', 'loadByCustomer', 'getPointsBalance', 'save', 'getResource']
        );
        $orderMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($appliedRuleIds);

        /** @var RuleExtensionInterface|\PHPUnit_Framework_MockObject_MockObject $attributesOneMock */
        $attributesOneMock = $this->getMockBuilder(RuleExtensionInterface::class)
            ->setMethods(['getRewardPointsDelta'])
            ->getMockForAbstractClass();
        $attributesOneMock->expects(self::any())
            ->method('getRewardPointsDelta')
            ->willReturn(100);

        $this->rewardPointCounterMock->expects(self::any())
            ->method('getPointsForRules')
            ->with(
                [
                    0 => '1',
                ]
            )
            ->willReturn(100);

        $this->rewardFactoryMock->expects($this->once())->method('create')->will($this->returnValue($rewardMock));

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->storeManagerMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));

        $rewardMock->expects($this->once())->method('loadByCustomer')->will($this->returnSelf());
        $rewardMock->expects($this->once())->method('getPointsBalance')->will($this->returnValue(500));
        $creditmemoMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));
        $creditmemoMock->expects($this->exactly(2))->method('getAutomaticallyCreated')->will($this->returnValue(true));
        $creditmemoMock->expects($this->once())->method('getRewardPointsBalance')->will($this->returnValue(100));
        $creditmemoMock->expects($this->once())
            ->method('setRewardPointsBalanceRefund')
            ->with(100)
            ->will($this->returnSelf());

        $this->rewardHelperMock->expects($this->once())->method('isAutoRefundEnabled')->will($this->returnValue(true));

        $orderMock->expects($this->once())->method('getTotalQtyOrdered')->will($this->returnValue(5));

        $creditmemo = $this->createPartialMock(
            \Magento\Sales\Model\Order\Creditmemo::class,
            ['__wakeup', 'getData', 'getAllItems']
        );
        $creditmemoCollectionMock = $this->objectManager->getCollectionMock(
            \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection::class,
            [$creditmemo]
        );
        $orderMock->expects($this->atLeastOnce())
            ->method('getCreditmemosCollection')
            ->will($this->returnValue($creditmemoCollectionMock));

        $itemMock = $this->createPartialMock(\Magento\Sales\Model\Order\Creditmemo\Item::class, ['getQty', '__wakeup']);
        $itemMock->expects($this->atLeastOnce())->method('getQty')->will($this->returnValue(3));
        $creditmemo->expects($this->atLeastOnce())->method('getAllItems')->will($this->returnValue([$itemMock]));

        $this->subject->refund($creditmemoMock);
    }
}
