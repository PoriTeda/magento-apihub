<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Command;

use Magento\Eway\Gateway\Command\CaptureStrategyCommand;
use Magento\Payment\Gateway\Command\CommandPoolInterface;

class CaptureStrategyCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CaptureStrategyCommand
     */
    private $command;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $commandPool;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDO;

    protected function setUp()
    {
        $this->commandPool = $this->getMockBuilder(\Magento\Payment\Gateway\Command\CommandPoolInterface::class)
            ->getMockForAbstractClass();

        $this->paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();

        $this->command = new CaptureStrategyCommand($this->commandPool);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Payment data object should be provided
     */
    public function testExecuteReadPaymentException()
    {
        $commandSubject = [];

        $this->command->execute($commandSubject);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Order payment should be provided.
     */
    public function testExecuteAssertOrderPaymentException()
    {
        $commandSubject = [
            'payment' => $this->paymentDO
        ];

        $paymentInfoFake = $this->getMockBuilder(\Magento\Quote\Model\Quote\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDO->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentInfoFake);

        $this->command->execute($commandSubject);
    }

    public function testExecuteCapture()
    {
        $commandSubject = [
            'payment' => $this->paymentDO
        ];

        $paymentInfo = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $captureCommand = $this->getMockBuilder(\Magento\Payment\Gateway\CommandInterface::class)
            ->getMockForAbstractClass();

        $this->paymentDO->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->expects($this->once())
            ->method('getAuthorizationTransaction')
            ->willReturn(false);

        $this->commandPool->expects($this->once())
            ->method('get')
            ->with('sale')
            ->willReturn($captureCommand);
        $captureCommand->expects($this->once())
            ->method('execute')
            ->with($commandSubject)
            ->willReturn(null);

        $this->assertNull($this->command->execute($commandSubject));
    }

    public function testExecuteSale()
    {
        $commandSubject = [
            'payment' => $this->paymentDO
        ];

        $paymentInfo = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $saleCommand = $this->getMockBuilder(\Magento\Payment\Gateway\CommandInterface::class)
            ->getMockForAbstractClass();

        $this->paymentDO->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->expects($this->once())
            ->method('getAuthorizationTransaction')
            ->willReturn(true);

        $this->commandPool->expects($this->once())
            ->method('get')
            ->with('pre_auth_capture')
            ->willReturn($saleCommand);
        $saleCommand->expects($this->once())
            ->method('execute')
            ->with($commandSubject)
            ->willReturn(null);

        $this->assertNull($this->command->execute($commandSubject));
    }
}
