<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Gateway\Command;

use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Worldpay\Gateway\Command\InitializeCommand;

/**
 * Class InitializeCommandTest
 *
 * Test for class \Magento\Worldpay\Gateway\Command\InitializeCommand
 */
class InitializeCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InitializeCommand
     */
    protected $initializeCommand;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->initializeCommand = new InitializeCommand();
    }

    public function testExecuteException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Order Payment should be provided');

        $stateObjectMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentDO = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
        )
            ->getMockForAbstractClass();
        $paymentInfo = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->getMockForAbstractClass();

        $paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInfo);

        $this->initializeCommand->execute(
            [
                'payment' => $paymentDO,
                'stateObject' => $stateObjectMock
            ]
        );
    }

    public function testExecute()
    {
        $stateObjectMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentDO = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
        )
            ->getMockForAbstractClass();
        $paymentInfo = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $order = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDO->expects(static::any())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $stateObjectMock->expects($this->at(0))
            ->method('setData')
            ->with(OrderInterface::STATE, Order::STATE_PENDING_PAYMENT);
        $stateObjectMock->expects($this->at(1))
            ->method('setData')
            ->with(OrderInterface::STATUS, Order::STATE_PENDING_PAYMENT);
        $stateObjectMock->expects($this->at(2))
            ->method('setData')
            ->with('is_notified', false);

        $paymentInfo->expects(static::any())
            ->method('getOrder')
            ->willReturn($order);
        $order->expects(static::once())
            ->method('getTotalDue')
            ->willReturn(10);
        $order->expects(static::once())
            ->method('getBaseTotalDue')
            ->willReturn(10);
        $order->expects(static::once())
            ->method('setCanSendNewEmailFlag')
            ->with(false);

        $paymentInfo->expects(static::once())
            ->method('setAmountAuthorized')
            ->with(10);
        $paymentInfo->expects(static::once())
            ->method('setBaseAmountAuthorized')
            ->with(10);

        $this->initializeCommand->execute(
            [
                'payment' => $paymentDO,
                'stateObject' => $stateObjectMock
            ]
        );
    }
}
