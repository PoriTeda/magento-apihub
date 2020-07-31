<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Command\SilentOrder\Token;

use Magento\Cybersource\Gateway\Command\SilentOrder\Token\ResponseProcessCommand;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\PaymentMethodManagementInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ResponseProcessCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ValidatorInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    private $validator;

    /**
     * @var HandlerInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    private $handler;

    /**
     * @var PaymentMethodManagementInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentManagement;

    /**
     * @var Logger | \PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var ResponseProcessCommand
     */
    private $command;

    protected function setUp()
    {
        $this->validator = $this->getMockBuilder(
            \Magento\Payment\Gateway\Validator\ValidatorInterface::class
        )->getMockForAbstractClass();
        $this->handler = $this->getMockBuilder(
            \Magento\Payment\Gateway\Response\HandlerInterface::class
        )->getMockForAbstractClass();
        $this->paymentManagement = $this->getMockBuilder(
            \Magento\Quote\Api\PaymentMethodManagementInterface::class
        )->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(
            \Magento\Payment\Model\Method\Logger::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new ResponseProcessCommand(
            $this->validator,
            $this->handler,
            $this->paymentManagement,
            $this->logger
        );
    }

    public function testExecutePaymentMissing()
    {
        $this->expectException('InvalidArgumentException');
        $this->command->execute(['payment' => null]);
    }

    public function testExecuteResponseMissing()
    {
        $this->expectException('InvalidArgumentException');

        $paymentDO = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
        )->getMockForAbstractClass();

        $this->command->execute(
            ['response' => null, 'payment' => $paymentDO]
        );
    }

    public function testExecuteResponseValidationFail()
    {
        $this->expectException('LogicException');

        $commandSubject = [
            'payment' => $this->getMockBuilder(
                \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
            )->getMockForAbstractClass(),
            'response' => ['data', 'data2']
        ];

        $resultMock = $this->getMockBuilder(
            \Magento\Payment\Gateway\Validator\ResultInterface::class
        )->getMockForAbstractClass();

        $this->logger->expects(static::once())
            ->method('debug')
            ->with(
                ['payment_token_response' => $commandSubject['response']]
            );
        $this->validator->expects(static::once())
            ->method('validate')
            ->with($commandSubject)
            ->willReturn($resultMock);
        $resultMock->expects(static::once())
            ->method('isValid')
            ->willReturn(false);

        $this->handler->expects(static::never())
            ->method('handle');
        $this->paymentManagement->expects(static::never())
            ->method('set');

        $this->command->execute($commandSubject);
    }

    public function testExecute()
    {
        $paymentDO = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
        )->getMockForAbstractClass();
        $paymentInfo = $this->getMockBuilder(
            \Magento\Quote\Api\Data\PaymentInterface::class
        )->getMockForAbstractClass();
        $orderAdapter = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\OrderAdapterInterface::class
        )->getMockForAbstractClass();
        $resultMock = $this->getMockBuilder(
            \Magento\Payment\Gateway\Validator\ResultInterface::class
        )->getMockForAbstractClass();

        $commandSubject = [
            'payment' => $paymentDO,
            'response' => ['data', 'data2']
        ];
        $orderId = 10;

        $this->logger->expects(static::once())
            ->method('debug')
            ->with(
                ['payment_token_response' => $commandSubject['response']]
            );
        $this->validator->expects(static::once())
            ->method('validate')
            ->with($commandSubject)
            ->willReturn($resultMock);
        $resultMock->expects(static::once())
            ->method('isValid')
            ->willReturn(true);
        $this->handler->expects(static::once())
            ->method('handle')
            ->with($commandSubject, $commandSubject['response']);

        $paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($orderAdapter);
        $orderAdapter->expects(static::once())
            ->method('getId')
            ->willReturn($orderId);
        $this->paymentManagement->expects(static::once())
            ->method('set')
            ->with($orderId, $paymentInfo);

        $this->command->execute($commandSubject);
    }
}
