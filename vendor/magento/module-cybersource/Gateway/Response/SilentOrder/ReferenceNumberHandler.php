<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Response\SilentOrder;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Cybersource\Gateway\Request\SilentOrder\TransactionDataBuilder;
use Magento\Sales\Model\Order\Payment;

class ReferenceNumberHandler implements HandlerInterface
{
    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $referenceNumberField = 'req_' . TransactionDataBuilder::REFERENCE_NUMBER;

        if (!isset($response[$referenceNumberField])) {
            return;
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        $paymentDO->getPayment()
            ->setAdditionalInformation(
                TransactionDataBuilder::REFERENCE_NUMBER,
                $response[$referenceNumberField]
            );
    }
}
