<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Cybersource\Gateway\Vault;

use Magento\Cybersource\Gateway\Request\SilentOrder\PaymentTokenBuilder;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * PaymentTokenManagement has possibility to create, update and retrieve from payment Vault Payment Token.
 */
class PaymentTokenManagement
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private $paymentExtensionFactory;

    /**
     * @var PaymentTokenFactoryInterface
     */
    private $paymentTokenFactory;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param ConfigInterface $config
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param Json $serializer
     */
    public function __construct(
        ConfigInterface $config,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        Json $serializer
    ) {
        $this->config = $config;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->serializer = $serializer;
    }

    /**
     * Creates new Vault Payment Token based only on gateway token without any additional details.
     *
     * @param string $token
     * @return PaymentTokenInterface
     */
    public function create(string $token): PaymentTokenInterface
    {
        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setGatewayToken($token);

        return $paymentToken;
    }

    /**
     * Updates Vault Payment Token by provided details.
     *
     * @param PaymentTokenInterface $paymentToken
     * @param string $card
     * @param string $cardType
     * @param string $expMonth
     * @param string $expYear
     * @return void
     */
    public function update(
        PaymentTokenInterface $paymentToken,
        string $card,
        string $cardType,
        string $expMonth,
        string $expYear
    ) {
        $details = [
            'type' => $this->getCreditCardType($cardType),
            'maskedCC' => $card,
            'expirationDate' => $expMonth . '/' . $expYear,
        ];
        $paymentToken->setTokenDetails($this->serializer->serialize($details));
        $paymentToken->setExpiresAt($this->getExpirationDate($expYear, $expMonth));
    }

    /**
     * Retrieves Vault Payment Token from provided payment method.
     *
     * @param OrderPaymentInterface $payment
     * @return PaymentTokenInterface|null
     */
    public function retrieveFromPayment(OrderPaymentInterface $payment)
    {
        $extAttributes = $this->getExtensionAttributes($payment);
        $paymentToken = $extAttributes->getVaultPaymentToken();
        if ($paymentToken !== null) {
            return $paymentToken;
        }

        // try to retrieve payment token from additional information
        $token = $payment->getAdditionalInformation(PaymentTokenBuilder::PAYMENT_TOKEN);
        if (empty($token)) {
            return null;
        }

        $paymentToken = $this->create($token);
        $extAttributes->setVaultPaymentToken($paymentToken);

        return $paymentToken;
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

    /**
     * Gets token expiration date based on credit card expiration date.
     *
     * @param string $year
     * @param string $month
     * @return string
     */
    private function getExpirationDate(string $year, string $month)
    {
        $expDate = new \DateTime("$year-$month-01 00:00:00", new \DateTimeZone('UTC'));
        $expDate->add(new \DateInterval('P1M'));

        return $expDate->format('Y-m-d 00:00:00');
    }

    /**
     * Gets type of credit card mapped from numeric code to letter representation.
     *
     * @param string $type
     * @return string|null
     */
    private function getCreditCardType(string $type)
    {
        $types = $this->serializer->unserialize($this->config->getValue('cctypes_mapper'));

        return $types[$type] ?? null;
    }
}
