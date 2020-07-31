<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Response\Soap;

use Magento\Cybersource\Gateway\Vault\PaymentTokenManagement;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Handle "Subscription ID" payment information.
 */
class SubscriptionIdHandler implements HandlerInterface
{
    /**
     * @var PaymentTokenManagement
     */
    private $paymentTokenManagement;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private $paymentExtensionFactory;

    /**
     * @param PaymentTokenManagement $paymentTokenManagement
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     */
    public function __construct(
        PaymentTokenManagement $paymentTokenManagement,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $subscriptionId = $response['paySubscriptionCreateReply']['subscriptionID'] ?? null;
        if (empty($subscriptionId)) {
            return;
        }

        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        $payment->setAdditionalInformation('subscriptionID', $subscriptionId);

        // create token based on the Subscription ID
        $paymentToken = $this->paymentTokenManagement->create($subscriptionId);
        $extAttributes = $this->getExtensionAttributes($payment);
        $extAttributes->setVaultPaymentToken($paymentToken);
    }

    /**
     * Gets payment extension attributes.
     *
     * @param OrderPaymentInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(OrderPaymentInterface $payment): OrderPaymentExtensionInterface
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }
}
