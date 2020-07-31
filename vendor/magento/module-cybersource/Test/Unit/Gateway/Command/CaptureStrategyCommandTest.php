<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Command;

use Magento\Cybersource\Gateway\Command\CaptureStrategyCommand;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Tests Magento\Cybersource\Gateway\Command\CaptureStrategyCommand.
 */
class CaptureStrategyCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CommandPoolInterface|MockObject
     */
    private $commandPool;

    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private $paymentDO;

    /**
     * @var CaptureStrategyCommand
     */
    private $captureCommand;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->commandPool = $this->getMockBuilder(CommandPoolInterface::class)
            ->getMockForAbstractClass();
        $this->paymentDO = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();

        $this->captureCommand = new CaptureStrategyCommand($this->commandPool);
    }

    /**
     * Checks a case when command will perform Secure Acceptance sale operation.
     *
     * @return void
     */
    public function testExecuteSecureAcceptanceSale()
    {
        $commandSubject = [
            'payment' => $this->paymentDO,
            'amount' => '10.00',
        ];

        $paymentInfo = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $saleCommand = $this->getMockBuilder(CommandInterface::class)
            ->getMockForAbstractClass();

        $this->paymentDO->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->method('getAuthorizationTransaction')
            ->willReturn(false);

        $this->commandPool->expects(self::once())
            ->method('get')
            ->with(CaptureStrategyCommand::SECURE_ACCEPTANCE_SALE)
            ->willReturn($saleCommand);
        $saleCommand->method('execute')
            ->with($commandSubject)
            ->willReturn(null);

        $this->captureCommand->execute($commandSubject);
    }

    /**
     * Checks a case when SOAP command will be executed to perform capture operation.
     *
     * @return void
     */
    public function testExecuteSOAPOrderCapture()
    {
        $commandSubject = [
            'payment' => $this->paymentDO,
            'amount' => '10.00',
        ];

        $paymentInfo = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $captureCommand = $this->getMockBuilder(CommandInterface::class)
            ->getMockForAbstractClass();
        /** @var Transaction|MockObject $transaction */
        $transaction = $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDO->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->method('getAuthorizationTransaction')
            ->willReturn($transaction);

        $this->commandPool->expects(self::once())
            ->method('get')
            ->with(CaptureStrategyCommand::SIMPLE_ORDER_CAPTURE)
            ->willReturn($captureCommand);
        $captureCommand->method('execute')
            ->with($commandSubject)
            ->willReturn(null);

        $this->captureCommand->execute($commandSubject);
    }
}
