<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Gateway\Response;

use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

class WafMessageHandler implements HandlerInterface
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
        if (!isset($response['wafMerchMessage'])) {
            return;
        }

        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();
        $payment->setAdditionalInformation(
            'waf_merch_message',
            $response['wafMerchMessage']
        );

        if ($response['wafMerchMessage'] === 'waf.warning') {
            ContextHelper::assertOrderPayment($payment);
            /** @var Payment $payment */

            $payment->setIsFraudDetected(true);
        }
    }
}
