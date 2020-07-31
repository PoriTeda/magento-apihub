<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Cybersource\Test\Unit\Gateway\Vault;

use Magento\Cybersource\Gateway\Vault\PaymentTokenManagement;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\PaymentToken;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Tests Magento\Cybersource\Gateway\Vault\PaymentTokenManagement.
 */
class PaymentTokenManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentTokenFactoryInterface|MockObject
     */
    private $paymentTokenFactory;

    /**
     * @var ConfigInterface|MockObject
     */
    private $config;

    /**
     * @var OrderPaymentExtensionInterfaceFactory|MockObject
     */
    private $paymentExtensionFactory;

    /**
     * @var PaymentTokenManagement
     */
    private $management;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->paymentTokenFactory = $this->getMockBuilder(PaymentTokenFactoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->getMockBuilder(ConfigInterface::class)
            ->getMockForAbstractClass();

        $this->paymentExtensionFactory = $this->getMockBuilder(OrderPaymentExtensionInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->management = new PaymentTokenManagement(
            $this->config,
            $this->paymentExtensionFactory,
            $this->paymentTokenFactory,
            new Json()
        );
    }

    /**
     * Checks a case when payment token doesn't exist in extension attributes but present in the additional
     * information and can be created on the fly.
     *
     * @return void
     */
    public function testRetrieveFromPayment()
    {
        $token = 'token';

        /** @var Payment|MockObject $payment */
        $payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extAttributes = $this->getExtensionAttributes();
        $payment->method('getExtensionAttributes')
            ->willReturn($extAttributes);
        $extAttributes->method('getVaultPaymentToken')
            ->willReturn(null);

        $payment->method('getAdditionalInformation')
            ->with('payment_token')
            ->willReturn($token);

        /** @var PaymentTokenInterface|MockObject $paymentToken */
        $paymentToken = $this->getMockBuilder(PaymentTokenInterface::class)
            ->getMockForAbstractClass();
        $this->paymentTokenFactory->method('create')
            ->with('card')
            ->willReturn($paymentToken);
        $paymentToken->method('setGatewayToken')
            ->with($token);

        $extAttributes->method('setVaultPaymentToken')
            ->with($paymentToken);

        $this->management->retrieveFromPayment($payment);
    }

    /**
     * Checks a case when payment token can be updated by provided data.
     *
     * @return void
     */
    public function testUpdate()
    {
        $card = '1111';
        $type = '002';
        $expMonth = '12';
        $expYear = '2019';

        /** @var PaymentToken|MockObject $paymentToken */
        $paymentToken = $this->getMockBuilder(PaymentToken::class)
            ->disableOriginalConstructor()
            ->setMethods(['setGatewayToken', 'getGatewayToken'])
            ->getMock();

        $this->config->method('getValue')
            ->with('cctypes_mapper')
            ->willReturn('{"001":"VI","002":"MC","003":"AE","004":"DI","005":"DN","006":"DN","007":"JCB","024":"MI"}');

        $this->management->update($paymentToken, $card, $type, $expMonth, $expYear);
        $this->assertEquals('2020-01-01 00:00:00', $paymentToken->getExpiresAt());

        $tokenDetails = json_decode($paymentToken->getTokenDetails(), true);
        self::assertEquals('MC', $tokenDetails['type']);
        self::assertEquals('12/2019', $tokenDetails['expirationDate']);
    }

    /**
     * Creates mock for payment extension attributes.
     *
     * @return OrderPaymentExtensionInterface|MockObject
     */
    private function getExtensionAttributes()
    {
        $extAttributes = $this->getMockBuilder(OrderPaymentExtensionInterface::class)
            ->setMethods(['getVaultPaymentToken', 'setVaultPaymentToken'])
            ->getMock();

        $this->paymentExtensionFactory->method('create')
            ->willReturn($extAttributes);

        return $extAttributes;
    }
}
