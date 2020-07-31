<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Observer;

use Magento\Reward\Model\Reward;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RedeemForOrderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Observer\RedeemForOrder
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
    protected $_validatorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_observerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardHelperMock;

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
        $this->_validatorMock = $this->createMock(\Magento\Reward\Model\Reward\Balance\Validator::class);

        $this->_observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $this->_model = new \Magento\Reward\Observer\RedeemForOrder(
            $this->_restrictionMock,
            $this->_storeManagerMock,
            $this->_modelFactoryMock,
            $this->_validatorMock
        );
    }

    public function testRedeemForOrderIfRestrictionNotAllowed()
    {
        $this->_restrictionMock->expects($this->once())->method('isAllowed')->will($this->returnValue(false));
        $this->_observerMock->expects($this->never())->method('getEvent');
        $this->_model->execute($this->_observerMock);
    }

    public function testRedeemForOrderIfRewardCurrencyAmountAboveNull()
    {
        $baseRewardCurrencyAmount = 1;
        $rewardPointsBalance = 100;
        $this->_restrictionMock->expects($this->once())->method('isAllowed')->will($this->returnValue(true));
        $order = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['__wakeup', 'setBaseRewardCurrencyAmount', 'setRewardPointsBalance']
        );
        $quote = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            ['getBaseRewardCurrencyAmount', 'getRewardPointsBalance']
        );
        $event = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrder', 'getQuote']);
        $this->_observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $event->expects($this->once())->method('getOrder')->will($this->returnValue($order));
        $event->expects($this->once())->method('getQuote')->will($this->returnValue($quote));
        $quote->expects($this->atLeastOnce())->method('getBaseRewardCurrencyAmount')
            ->willReturn($baseRewardCurrencyAmount);
        $model = $this->createMock(\Magento\Reward\Model\Reward::class);
        $this->_modelFactoryMock->expects($this->once())->method('create')->will($this->returnValue($model));
        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $this->_storeManagerMock->expects($this->once())->method('getStore')->will($this->returnValue($store));
        $store->expects($this->once())->method('getWebsiteId');
        $quote->expects($this->atLeastOnce())->method('getRewardPointsBalance')->willReturn($rewardPointsBalance);
        $order->expects($this->once())->method('setBaseRewardCurrencyAmount')->with($baseRewardCurrencyAmount);
        $order->expects($this->once())->method('setRewardPointsBalance')->with($rewardPointsBalance);
        $this->_model->execute($this->_observerMock);
    }

    public function testRedeemForOrderIfRewardCurrencyAmountBelowNull()
    {
        $baseRewardCurrencyAmount = -1;
        $this->_restrictionMock->expects($this->once())->method('isAllowed')->will($this->returnValue(true));
        $order = $this->createPartialMock(\Magento\Sales\Model\Order::class, ['__wakeup']);
        $quote = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getBaseRewardCurrencyAmount']);
        $event = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrder', 'getQuote']);
        $this->_observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $event->expects($this->once())->method('getOrder')->will($this->returnValue($order));
        $event->expects($this->once())->method('getQuote')->will($this->returnValue($quote));
        $quote->expects($this->once())->method('getBaseRewardCurrencyAmount')->willReturn($baseRewardCurrencyAmount);
        $this->_model->execute($this->_observerMock);
    }

    public function testRedeemForOrderPlacedViaMultyshipping()
    {
        $baseRewardCurrencyAmount = 1;
        $rewardPointsBalance = 100;
        $this->_restrictionMock->expects($this->once())->method('isAllowed')->will($this->returnValue(true));
        $order = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['__wakeup', 'setBaseRewardCurrencyAmount', 'setRewardPointsBalance']
        );
        $quote = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            ['getBaseRewardCurrencyAmount', 'getRewardPointsBalance', 'getIsMultiShipping']
        );
        $addressMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote\Address::class,
            ['getBaseRewardCurrencyAmount', 'getRewardPointsBalance']
        );
        $event = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrder', 'getQuote', 'getAddress']);
        $quote->expects($this->once())->method('getIsMultiShipping')->willReturn(true);
        $this->_observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($event));
        $event->expects($this->once())->method('getOrder')->will($this->returnValue($order));
        $event->expects($this->once())->method('getQuote')->will($this->returnValue($quote));
        $event->expects($this->once())->method('getAddress')->willReturn($addressMock);
        $quote->expects($this->atLeastOnce())->method('getBaseRewardCurrencyAmount')
            ->willReturn($baseRewardCurrencyAmount);
        $addressMock->expects($this->atLeastOnce())->method('getBaseRewardCurrencyAmount')
            ->willReturn($baseRewardCurrencyAmount);
        $model = $this->createMock(\Magento\Reward\Model\Reward::class);
        $this->_modelFactoryMock->expects($this->once())->method('create')->will($this->returnValue($model));
        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $this->_storeManagerMock->expects($this->once())->method('getStore')->will($this->returnValue($store));
        $store->expects($this->once())->method('getWebsiteId');
        $addressMock->expects($this->atLeastOnce())->method('getRewardPointsBalance')->willReturn($rewardPointsBalance);
        $order->expects($this->once())->method('setBaseRewardCurrencyAmount')->with($baseRewardCurrencyAmount);
        $order->expects($this->once())->method('setRewardPointsBalance')->with($rewardPointsBalance);
        $this->_model->execute($this->_observerMock);
    }
}
