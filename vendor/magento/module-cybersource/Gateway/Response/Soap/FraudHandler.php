<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Response\Soap;

use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Model\Quote\Payment;

/**
 * Class FraudHandler
 */
class FraudHandler extends \Magento\Cybersource\Gateway\Response\FraudHandler
{
    /**
     * Returns risk factors form response
     *
     * @param array $response
     * @return null | string
     */
    protected function getRiskFactors(array $response)
    {
        return isset($response['afsReply']['afsFactorCode'])
            ? $response['afsReply']['afsFactorCode']
            : null;
    }

    /**
     * Returns risk factors form response
     *
     * @param array $response
     * @return null | string
     */
    protected function getRiskScore(array $response)
    {
        return isset($response['afsReply']['afsResult'])
            ? $response['afsReply']['afsResult']
            : null;
    }

    /**
     * Sets payment state
     *
     * @param InfoInterface $payment
     * @return void
     */
    protected function setPaymentState(InfoInterface $payment)
    {
        $payment->setIsTransactionPending(true);
        $payment->setIsFraudDetected(true);
    }
}
