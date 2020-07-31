<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Command\Shared;

use Magento\Eway\Gateway\Command\Shared\InitializeCommand;
use Magento\Framework\DataObject;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;

class InitializeCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InitializeCommand
     */
    private $command;

    /**
     * @var DataObject|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stateObjectMock;

    /**
     * @var PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDataObjectMock;

    /**
     * @var Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentOrderMock;

    /**
     * @var Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    protected function setUp()
    {
        $this->stateObjectMock = $this
            ->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDataObjectMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();

        $this->paymentOrderMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new InitializeCommand();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage State object does not exist
     */
    public function testExecuteStateObjectException()
    {
        $commandSubject = ['stateObject' => new \stdClass()];

        $this->command->execute($commandSubject);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Payment data object should be provided
     */
    public function testExecuteReadPaymentException()
    {
        $commandSubject = [
            'stateObject' => new DataObject(),
            'payment' => new \stdClass()
        ];

        $this->command->execute($commandSubject);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Order payment should be provided.
     */
    public function testExecuteOrderPaymentException()
    {
        $commandSubject = [
            'stateObject' => new DataObject(),
            'payment' => $this->paymentDataObjectMock
        ];

        $paymentQuoteMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDataObjectMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentQuoteMock);

        $this->command->execute($commandSubject);
    }

    public function testExecute()
    {
        $totalDue = 100;

        $commandSubject = [
            'stateObject' => $this->stateObjectMock,
            'payment' => $this->paymentDataObjectMock
        ];

        $this->paymentDataObjectMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->paymentOrderMock);
        $this->paymentOrderMock->expects($this->exactly(3))
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->orderMock->expects($this->once())
            ->method('getTotalDue')
            ->willReturn($totalDue);
        $this->paymentOrderMock->expects($this->once())
            ->method('setAmountAuthorized')
            ->with($totalDue);
        $this->orderMock->expects($this->once())
            ->method('getBaseTotalDue')
            ->willReturn($totalDue);
        $this->paymentOrderMock->expects($this->once())
            ->method('setBaseAmountAuthorized')
            ->with($totalDue);
        $this->orderMock->expects($this->once())
            ->method('setCanSendNewEmailFlag')
            ->with(false);

        $this->stateObjectMock->expects($this->at(0))
            ->method('setData')
            ->with(OrderInterface::STATE, Order::STATE_PENDING_PAYMENT);
        $this->stateObjectMock->expects($this->at(1))
            ->method('setData')
            ->with(OrderInterface::STATUS, Order::STATE_PENDING_PAYMENT);
        $this->stateObjectMock->expects($this->at(2))
            ->method('setData')
            ->with('is_notified', false);

        $this->command->execute($commandSubject);
    }
}
