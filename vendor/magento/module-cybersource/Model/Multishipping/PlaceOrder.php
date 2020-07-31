<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Cybersource\Model\Multishipping;

use Magento\Multishipping\Model\Checkout\Type\Multishipping\PlaceOrderInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * Default implementation for OrderPlaceInterface.
 */
class PlaceOrder implements PlaceOrderInterface
{
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private $paymentExtensionFactory;

    /**
     * @param OrderManagementInterface $orderManagement
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     */
    public function __construct(
        OrderManagementInterface $orderManagement,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
    ) {
        $this->orderManagement = $orderManagement;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function place(array $orderList): array
    {
        if (empty($orderList)) {
            return [];
        }

        $errorList = [];
        $firstOrder = $this->orderManagement->place(array_shift($orderList));
        // get payment token from first placed order
        $paymentToken = $this->getPaymentToken($firstOrder);

        foreach ($orderList as $order) {
            try {
                $orderPayment = $order->getPayment();
                $extensionAttributes = $this->getExtensionAttributes($orderPayment);
                // set payment token from first order payment to other order payments
                $extensionAttributes->setVaultPaymentToken($paymentToken);
                $this->orderManagement->place($order);
            } catch (\Exception $e) {
                $incrementId = $order->getIncrementId();
                $errorList[$incrementId] = $e;
            }
        }

        return $errorList;
    }

    /**
     * Returns payment token.
     *
     * @param OrderInterface $order
     * @return PaymentTokenInterface
     * @throws \BadMethodCallException
     */
    private function getPaymentToken(OrderInterface $order): PaymentTokenInterface
    {
        $orderPayment = $order->getPayment();
        $extensionAttributes = $this->getExtensionAttributes($orderPayment);
        $paymentToken = $extensionAttributes->getVaultPaymentToken();

        if ($paymentToken === null) {
            throw new \BadMethodCallException('Vault Payment Token should be defined for placed order payment.');
        }

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
}
