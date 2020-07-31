<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Cybersource\Gateway\Response\SilentOrder;

use Magento\Cybersource\Gateway\Vault\PaymentTokenManagement;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * Handle Vault details.
 */
class VaultDetailsHandler implements HandlerInterface
{
    /**
     * @var PaymentTokenManagement
     */
    private $paymentTokenManagement;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @param PaymentTokenManagement $paymentTokenManagement
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        PaymentTokenManagement $paymentTokenManagement,
        SubjectReader $subjectReader
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        /** @var PaymentTokenInterface $vaultToken */
        $paymentToken = $payment->getExtensionAttributes()
            ->getVaultPaymentToken();

        list($month, $year) = explode('-', $response['req_card_expiry_date']);
        $card = substr($response['req_card_number'], -4);

        $this->paymentTokenManagement->update(
            $paymentToken,
            $card,
            $response['req_card_type'],
            $month,
            $year
        );
    }
}
