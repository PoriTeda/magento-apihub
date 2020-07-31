<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Response\SilentOrder;

use \Magento\Payment\Model\InfoInterface;

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
        return isset($response['score_factors'])
            ? $response['score_factors']
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
        return isset($response['score_score_result'])
            ? $response['score_score_result']
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
        $payment->setIsTransactionPending(false);
        $payment->setIsFraudDetected(true);
    }
}
