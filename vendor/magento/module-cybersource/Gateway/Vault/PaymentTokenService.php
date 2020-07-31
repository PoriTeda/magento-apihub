<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Cybersource\Gateway\Vault;

use Magento\Cybersource\Gateway\Command\CaptureStrategyCommand;
use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * PaymentTokenService should retrieve payment token from payment method but
 * if Decision Manager triggers a fraud, payment token won't be returned.
 * So Simple Order API should be used for converting a transaction to a customer subscription and
 * use created payment token from subscription for Secure Acceptance flow.
 *
 * @see https://www.cybersource.com/en-APAC/products/payment_processing/recurring_billing/
 * @see http://apps.cybersource.com/library/documentation/dev_guides/Simple_Order_API_Clients/Client_SDK_SO_API.pdf
 * @see http://apps.cybersource.com/library/documentation/dev_guides/Secure_Acceptance_SOP/Secure_Acceptance_SOP.pdf
 */
class PaymentTokenService
{
    /**
     * @var PaymentTokenManagement
     */
    private $tokenManagement;

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @param PaymentTokenManagement $tokenManagement
     * @param CommandPoolInterface $commandPool
     */
    public function __construct(
        PaymentTokenManagement $tokenManagement,
        CommandPoolInterface $commandPool
    ) {
        $this->tokenManagement = $tokenManagement;
        $this->commandPool = $commandPool;
    }

    /**
     * Tries to retrieve payment token from payment method or creates subscription based on
     * transaction.
     *
     * @param PaymentDataObjectInterface $paymentDataObject
     * @return PaymentTokenInterface|null
     * @throws NotFoundException
     * @throws CommandException
     */
    public function getToken(PaymentDataObjectInterface $paymentDataObject)
    {
        $paymentToken = $this->tokenManagement->retrieveFromPayment(
            $paymentDataObject->getPayment()
        );
        if ($paymentToken !== null) {
            return $paymentToken;
        }

        // create payment token based on subscription
        $commandSubject = ['payment' => $paymentDataObject];
        $this->commandPool->get(CaptureStrategyCommand::SIMPLE_ORDER_SUBSCRIPTION)
            ->execute($commandSubject);

        // try to retrieve payment token again which based on subscription
        return $this->tokenManagement->retrieveFromPayment(
            $paymentDataObject->getPayment()
        );
    }
}
