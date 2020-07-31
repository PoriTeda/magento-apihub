<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Gateway\Command;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Worldpay\Gateway\Command\CaptureCommand;
use Psr\Log\LoggerInterface;

class CaptureCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CaptureCommand
     */
    protected $command;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->command = new CaptureCommand(
            $this->createMock(
                BuilderInterface::class
            ),
            $this->createMock(
                TransferFactoryInterface::class
            ),
            $this->createMock(
                ClientInterface::class
            ),
            $this->createMock(
                LoggerInterface::class
            ),
            $this->createMock(
                HandlerInterface::class
            ),
            $this->createMock(
                ValidatorInterface::class
            )
        );
    }

    public function testExecuteNotOrderPayment()
    {
        $paymentDO = $this->createMock(
            PaymentDataObjectInterface::class
        );
        $paymentInfo = $this->getMockBuilder(InfoInterface::class)
            ->setMethods(['getAuthorizationTransaction'])
            ->getMockForAbstractClass();

        $paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->expects(static::never())
            ->method('getAuthorizationTransaction');

        $this->command->execute(
            [
                'payment' => $paymentDO
            ]
        );
    }

    public function testExecuteNoAuthTransaction()
    {
        $paymentDO = $this->createMock(
            PaymentDataObjectInterface::class
        );
        $paymentInfo = $this->getMockBuilder(Payment::class)
            ->setMethods(['getAuthorizationTransaction'])
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->expects(static::once())
            ->method('getAuthorizationTransaction')
            ->willReturn(false);

        $this->command->execute(
            [
                'payment' => $paymentDO
            ]
        );
    }
}
