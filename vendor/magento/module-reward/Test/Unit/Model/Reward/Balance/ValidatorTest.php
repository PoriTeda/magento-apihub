<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Model\Reward\Balance;

class ValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Model\Reward\Balance\Validator
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_modelFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_sessionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_orderMock;

    protected function setUp()
    {
        $this->_storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManager::class);
        $this->_modelFactoryMock = $this->createPartialMock(\Magento\Reward\Model\RewardFactory::class, ['create']);
        $this->_sessionMock = $this->createPartialMock(
            \Magento\Checkout\Model\Session::class,
            ['setUpdateSection', 'setGotoSection']
        );
        $this->_orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['getRewardPointsBalance', '__wakeup']
        );
        $this->_model = new \Magento\Reward\Model\Reward\Balance\Validator(
            $this->_storeManagerMock,
            $this->_modelFactoryMock,
            $this->_sessionMock
        );
    }

    public function testValidateWhenBalanceAboveNull()
    {
        $this->_orderMock->expects($this->any())->method('getRewardPointsBalance')->will($this->returnValue(1));
        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $this->_storeManagerMock->expects($this->once())->method('getStore')->will($this->returnValue($store));
        $store->expects($this->once())->method('getWebsiteId');
        $reward = $this->createPartialMock(\Magento\Reward\Model\Reward::class, ['getPointsBalance', '__wakeup']);
        $this->_modelFactoryMock->expects($this->once())->method('create')->will($this->returnValue($reward));
        $reward->expects($this->once())->method('getPointsBalance')->will($this->returnValue(1));
        $this->_model->validate($this->_orderMock);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage You don't have enough reward points to pay for this purchase.
     */
    public function testValidateWhenBalanceNotEnoughToPlaceOrder()
    {
        $this->_orderMock->expects($this->any())->method('getRewardPointsBalance')->will($this->returnValue(1));
        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $this->_storeManagerMock->expects($this->once())->method('getStore')->will($this->returnValue($store));
        $store->expects($this->once())->method('getWebsiteId');
        $reward = $this->createPartialMock(\Magento\Reward\Model\Reward::class, ['getPointsBalance', '__wakeup']);
        $this->_modelFactoryMock->expects($this->once())->method('create')->will($this->returnValue($reward));
        $reward->expects($this->once())->method('getPointsBalance')->will($this->returnValue(0.5));
        $this->_sessionMock->expects($this->once())->method('setUpdateSection')->with('payment-method');
        $this->_sessionMock->expects($this->once())->method('setGotoSection')->with('payment');

        $this->_model->validate($this->_orderMock);
    }
}
