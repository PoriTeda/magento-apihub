<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Response\Soap;

use Magento\Cybersource\Gateway\Response\Soap\SubscriptionIdHandler;
use Magento\Cybersource\Gateway\Vault\PaymentTokenManagement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Tests Magento\Cybersource\Gateway\Response\Soap\SubscriptionIdHandler.
 */
class SubscriptionIdHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentTokenManagement|MockObject
     */
    private $paymentTokenManagement;

    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private $paymentDO;

    /**
     * @var Payment|MockObject
     */
    private $payment;

    /**
     * @var SubscriptionIdHandler
     */
    private $handler;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->paymentTokenManagement = $this->getMockBuilder(PaymentTokenManagement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDO = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $this->payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->setMethods(
                ['setIsTransactionPending', 'setIsFraudDetected', 'setAdditionalInformation', 'getExtensionAttributes']
            )
            ->getMockForAbstractClass();
        $this->paymentDO->method('getPayment')
            ->willReturn($this->payment);

        $this->handler = $objectManager->getObject(
            SubscriptionIdHandler::class,
            [
                'paymentTokenManagement' => $this->paymentTokenManagement,
            ]
        );
    }

    public function testHandle()
    {
        $subscriptionId = '1111';
        $response = [
            'paySubscriptionCreateReply' => ['subscriptionID' => $subscriptionId],
        ];

        /** @var PaymentTokenInterface|MockObject $paymentToken */
        $paymentToken = $this->getMockBuilder(PaymentTokenInterface::class)
            ->getMock();
        $this->paymentTokenManagement->method('create')
            ->with(self::equalTo($subscriptionId))
            ->willReturn($paymentToken);

        /** @var OrderPaymentExtensionInterface|MockObject $extAttributes */
        $extAttributes = $this->getMockBuilder(OrderPaymentExtensionInterface::class)
            ->setMethods(['setVaultPaymentToken'])
            ->getMock();
        $extAttributes->method('setVaultPaymentToken')
            ->with($paymentToken);
        $this->payment->method('getExtensionAttributes')
            ->willReturn($extAttributes);

        $handlingSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->payment->method('setAdditionalInformation')
            ->with('subscriptionID', $subscriptionId);

        $this->handler->handle($handlingSubject, $response);
    }
}
