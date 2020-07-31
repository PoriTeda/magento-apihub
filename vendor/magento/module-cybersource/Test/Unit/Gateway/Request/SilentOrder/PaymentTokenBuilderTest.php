<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Request\SilentOrder;

use Magento\Cybersource\Gateway\Request\SilentOrder\PaymentTokenBuilder;
use Magento\Cybersource\Gateway\Vault\PaymentTokenService;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Tests Magento\Cybersource\Gateway\Request\SilentOrder\PaymentTokenBuilder.
 */
class PaymentTokenBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentTokenService|MockObject
     */
    private $paymentTokenService;

    /**
     * @var PaymentTokenBuilder
     */
    private $paymentTokenBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->paymentTokenService = $this->getMockBuilder(PaymentTokenService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTokenBuilder = new PaymentTokenBuilder($this->paymentTokenService);
    }

    /**
     * Check a case when builder can retrieve payment token from extension attributes.
     *
     * @return void
     */
    public function testBuildSuccess()
    {
        $paymentDO = $this->buildVaultPaymentToken('token');
        $result = $this->paymentTokenBuilder->build(['payment' => $paymentDO]);

        self::assertArrayHasKey(PaymentTokenBuilder::PAYMENT_TOKEN, $result);
        self::assertEquals('token', $result[PaymentTokenBuilder::PAYMENT_TOKEN]);
    }

    /**
     * Run test build method (Exception)
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testBuildException()
    {
        $this->paymentTokenBuilder->build(['payment' => null]);
    }

    /**
     * Checks a case when extension attributes do not contain Vault Payment Token.
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Vault Payment Token should be defined.
     * @return void
     */
    public function testUndefinedPaymentToken()
    {
        $paymentDO = $this->getPaymentMock();
        $this->paymentTokenService->method('getToken')
            ->with($paymentDO)
            ->willReturn(null);

        $this->paymentTokenBuilder->build(['payment' => $paymentDO]);
    }

    /**
     * Creates vault payment token and returns payment data object.
     *
     * @param string $token
     * @return PaymentDataObjectInterface|MockObject
     */
    private function buildVaultPaymentToken(string $token)
    {
        /** @var PaymentTokenInterface|MockObject $paymentToken */
        $paymentToken = $this->getMockBuilder(PaymentTokenInterface::class)
            ->getMockForAbstractClass();
        $paymentToken->method('getGatewayToken')
            ->willReturn($token);

        $paymentDO = $this->getPaymentMock();
        $this->paymentTokenService->method('getToken')
            ->with($paymentDO)
            ->willReturn($paymentToken);

        return $paymentDO;
    }

    /**
     * Creates payment mock object.
     *
     * @return array PaymentDataObjectInterface|MockObject
     */
    private function getPaymentMock()
    {
        $paymentDO = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();

        return $paymentDO;
    }
}
