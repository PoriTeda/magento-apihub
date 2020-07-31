<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Observer;

class InvoiceRegisterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Observer\InvitationToCustomer
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->subject = $objectManager->getObject(\Magento\Reward\Observer\InvoiceRegister::class);
    }

    public function testAddRewardsIfRewardCurrencyAmountIsNull()
    {
        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent', '__wakeup']);
        $invoiceMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Invoice::class,
            ['getBaseRewardCurrencyAmount', '__wakeup']
        );
        $invoiceMock->expects($this->once())->method('getBaseRewardCurrencyAmount')->will($this->returnValue(null));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getInvoice']);
        $eventMock->expects($this->once())->method('getInvoice')->will($this->returnValue($invoiceMock));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testAddRewardsSuccess()
    {
        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent', '__wakeup']);
        $invoiceMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Invoice::class,
            [
                'getBaseRewardCurrencyAmount',
                '__wakeup',
                'getOrder',
                'getRewardCurrencyAmount'
            ]
        );
        $invoiceMock->expects($this->exactly(2))->method('getBaseRewardCurrencyAmount')->will($this->returnValue(100));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getInvoice']);
        $eventMock->expects($this->once())->method('getInvoice')->will($this->returnValue($invoiceMock));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $orderMock = $this->createPartialMock(\Magento\Sales\Model\Order::class, [
                'getRwrdCurrencyAmountInvoiced',
                'getBaseRwrdCrrncyAmtInvoiced',
                'setRwrdCurrencyAmountInvoiced',
                'setBaseRwrdCrrncyAmtInvoiced',
                '__wakeup'
            ]);
        $orderMock->expects($this->once())->method('getRwrdCurrencyAmountInvoiced')->will($this->returnValue(50));
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmtInvoiced')->will($this->returnValue(50));
        $orderMock->expects($this->once())
            ->method('setRwrdCurrencyAmountInvoiced')
            ->with(100)
            ->will($this->returnSelf());
        $orderMock->expects($this->once())
            ->method('setBaseRwrdCrrncyAmtInvoiced')
            ->with(150)
            ->will($this->returnSelf());

        $invoiceMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));
        $invoiceMock->expects($this->once())->method('getRewardCurrencyAmount')->will($this->returnValue(50));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
