<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Request\Soap;

use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Cybersource\Gateway\Response\SilentOrder\TransactionIdHandler;

class SubscriptionDataBuilder implements BuilderInterface
{
    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var Payment $paymentInfo */
        $paymentInfo = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($paymentInfo);

        return [
            'paySubscriptionCreateService' => [
                'run' => 'true',
                'paymentRequestID' => $paymentInfo->getAdditionalInformation(
                    TransactionIdHandler::TRANSACTION_ID
                )
            ],
            'recurringSubscriptionInfo' => [
                'frequency' => 'on-demand'
            ]
        ];
    }
}
