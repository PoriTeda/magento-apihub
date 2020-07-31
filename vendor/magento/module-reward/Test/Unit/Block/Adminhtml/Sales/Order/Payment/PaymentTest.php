<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Block\Adminhtml\Sales\Order\Payment;

class PaymentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Block\Adminhtml\Sales\Order\Create\Payment
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderCreateMock;

    protected function setUp()
    {
        $this->rewardFactoryMock = $this->getMockBuilder(\Magento\Reward\Model\RewardFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $contextMock = $this->createMock(\Magento\Backend\Block\Template\Context::class);
        $this->orderCreateMock = $this->createMock(\Magento\Sales\Model\AdminOrder\Create::class);
        $rewardDataMock = $this->createMock(\Magento\Reward\Helper\Data::class);
        $converterMock = $this->createMock(\Magento\Framework\Api\ExtensibleDataObjectConverter::class);

        $this->model = new \Magento\Reward\Block\Adminhtml\Sales\Order\Create\Payment(
            $contextMock,
            $rewardDataMock,
            $this->orderCreateMock,
            $this->rewardFactoryMock,
            $converterMock
        );
    }

    public function testGetReward()
    {
        $rewardMock = $this->createPartialMock(
            \Magento\Reward\Model\Reward::class,
            ['setStore', 'setCustomer', 'loadByCustomer']
        );
        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $customerMock = $this->createMock(\Magento\Customer\Model\Data\Customer::class);
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->orderCreateMock->expects($this->any())->method('getQuote')->willReturn($quoteMock);

        $this->model->setData('reward', false);

        $quoteMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $this->rewardFactoryMock->expects($this->once())->method('create')->willReturn($rewardMock);
        $rewardMock->expects($this->once())->method('setCustomer')->with($customerMock)->willReturnSelf();
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $rewardMock->expects($this->once())->method('setStore')->with($storeMock);
        $rewardMock->expects($this->once())->method('loadByCustomer');

        $this->assertEquals($rewardMock, $this->model->getReward());
    }

    public function testGetRewardWithExistingReward()
    {
        $rewardMock = $this->createMock(\Magento\Reward\Model\Reward::class);
        $this->model->setData('reward', $rewardMock);
        $this->rewardFactoryMock->expects($this->never())->method('create');

        $this->assertEquals($rewardMock, $this->model->getReward());
    }
}
