<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Worldpay\Test\Unit\Gateway\Command\Response;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Worldpay\Gateway\Command\Response\AcceptCommand;

class AcceptCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AcceptCommand
     */
    private $command;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $validator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDO;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $payment;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resultMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var OrderRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderRepository;

    /**
     * @var OrderSender|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderSender;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->validator = $this->getMockBuilder(
            \Magento\Payment\Gateway\Validator\ValidatorInterface::class
        )->getMockForAbstractClass();
        $this->handler = $this->getMockBuilder(
            \Magento\Payment\Gateway\Response\HandlerInterface::class
        )->getMockForAbstractClass();
        $this->paymentDO = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
        )->getMockForAbstractClass();
        $this->payment = $this->getMockBuilder(
            \Magento\Sales\Model\Order\Payment::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultMock = $this->getMockBuilder(
            \Magento\Payment\Gateway\Validator\ResultInterface::class
        )
            ->getMockForAbstractClass();
        $this->orderMock = $this->getMockBuilder(
            \Magento\Sales\Model\Order::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepository = $this->getMockForAbstractClass(OrderRepositoryInterface::class);
        $this->orderSender = $this->getMockBuilder(OrderSender::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDO->expects(static::any())
            ->method('getPayment')
            ->willReturn($this->payment);
        $this->payment->expects(static::any())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->command = new AcceptCommand(
            $this->validator,
            $this->handler,
            $this->orderRepository,
            $this->orderSender
        );
    }

    /**
     * Test Execute Capture
     *
     * @return void
     */
    public function testExecuteCapture(): void
    {
        $commandSubject = [
            'payment' => $this->paymentDO,
            'response' => [
                'authMode' => 'A'
            ]
        ];

        $this->validator->expects(static::once())
            ->method('validate')
            ->with($commandSubject)
            ->willReturn($this->resultMock);
        $this->resultMock->expects(static::once())
            ->method('isValid')
            ->willReturn(true);
        $this->handler->expects(static::once())
            ->method('handle')
            ->with(
                $commandSubject,
                $commandSubject['response']
            );
        $this->payment->expects(static::once())
            ->method('capture');
        $this->orderMock->method('getEmailSent')
            ->willReturn(false);
        $this->orderSender->expects(static::once())
            ->method('send')
            ->with($this->orderMock);
        $this->orderRepository->expects(static::once())
            ->method('save')
            ->with($this->orderMock);

        $this->command->execute($commandSubject);
    }

    /**
     * Test Execute Authorize
     *
     * @return void
     */
    public function testExecuteAuthorize(): void
    {
        $commandSubject = [
            'payment' => $this->paymentDO,
            'response' => [
                'authMode' => 'E'
            ]
        ];

        $orderAdapater = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\OrderAdapterInterface::class
        )
            ->getMockForAbstractClass();

        $this->validator->expects(static::once())
            ->method('validate')
            ->with($commandSubject)
            ->willReturn($this->resultMock);
        $this->resultMock->expects(static::once())
            ->method('isValid')
            ->willReturn(true);
        $this->handler->expects(static::once())
            ->method('handle')
            ->with(
                $commandSubject,
                $commandSubject['response']
            );
        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($orderAdapater);
        $orderAdapater->expects(static::once())
            ->method('getGrandTotalAmount')
            ->willReturn(20.02);
        $this->payment->expects(static::once())
            ->method('authorize')
            ->with(
                false,
                20.02
            );
        $this->orderMock->method('getEmailSent')
            ->willReturn(true);
        $this->orderSender->expects(static::never())
            ->method('send');
        $this->orderRepository->expects(static::once())
            ->method('save')
            ->with($this->orderMock);

        $this->command->execute($commandSubject);
    }
}
