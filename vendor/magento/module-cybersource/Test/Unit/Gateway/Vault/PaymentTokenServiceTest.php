<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Cybersource\Test\Unit\Gateway\Vault;

use Magento\Cybersource\Gateway\Vault\PaymentTokenManagement;
use Magento\Cybersource\Gateway\Vault\PaymentTokenService;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Tests Magento\Cybersource\Gateway\Vault\PaymentTokenService.
 */
class PaymentTokenServiceTest extends TestCase
{
    /**
     * @var PaymentTokenManagement|MockObject
     */
    private $paymentTokenManagement;

    /**
     * @var CommandPoolInterface|MockObject
     */
    private $commandPool;

    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private $paymentDO;

    /**
     * @var PaymentTokenService
     */
    private $paymentTokenService;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->paymentTokenManagement = $this->getMockBuilder(PaymentTokenManagement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->commandPool = $this->getMockBuilder(CommandPoolInterface::class)
            ->getMockForAbstractClass();

        $this->paymentDO = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $payment = $this->getMockBuilder(OrderPaymentInterface::class)
            ->getMockForAbstractClass();
        $this->paymentDO->method('getPayment')
            ->willReturn($payment);

        $this->paymentTokenService = new PaymentTokenService($this->paymentTokenManagement, $this->commandPool);
    }

    /**
     * Checks a case when token can be retrieved from payment token management.
     *
     * @return void
     */
    public function testGetToken()
    {
        $paymentToken = $this->getMockBuilder(PaymentTokenInterface::class)
            ->getMockForAbstractClass();

        $this->paymentTokenManagement->expects(self::once())
            ->method('retrieveFromPayment')
            ->willReturn($paymentToken);

        $this->commandPool->expects(self::never())
            ->method('get');

        self::assertEquals($paymentToken, $this->paymentTokenService->getToken($this->paymentDO));
    }

    /**
     * Checks a case when payment token should be created from subscription.
     *
     * @return void
     */
    public function testGetTokenFromSubscription()
    {
        $paymentToken = $this->getMockBuilder(PaymentTokenInterface::class)
            ->getMockForAbstractClass();

        $this->paymentTokenManagement->expects(self::at(0))
            ->method('retrieveFromPayment')
            ->willReturn(null);

        $command = $this->getMockBuilder(CommandInterface::class)
            ->getMockForAbstractClass();
        $this->commandPool->method('get')
            ->willReturn($command);
        $command->method('execute')
            ->with(['payment' => $this->paymentDO]);

        $this->paymentTokenManagement->expects(self::at(1))
            ->method('retrieveFromPayment')
            ->willReturn($paymentToken);

        self::assertEquals($paymentToken, $this->paymentTokenService->getToken($this->paymentDO));
    }
}
