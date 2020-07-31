<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Response\SilentOrder;

use Magento\Cybersource\Gateway\Request\SilentOrder\PaymentTokenBuilder;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

class TokenHandler implements HandlerInterface
{
    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \InvalidArgumentException
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($response[PaymentTokenBuilder::PAYMENT_TOKEN])) {
            return;
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        $paymentDO->getPayment()
            ->setAdditionalInformation(
                PaymentTokenBuilder::PAYMENT_TOKEN,
                $response[PaymentTokenBuilder::PAYMENT_TOKEN]
            );

        if (isset($response[TransactionIdHandler::TRANSACTION_ID])) {
            $paymentDO->getPayment()
                ->setAdditionalInformation(
                    TransactionIdHandler::TRANSACTION_ID,
                    $response[TransactionIdHandler::TRANSACTION_ID]
                );
        }
    }
}
