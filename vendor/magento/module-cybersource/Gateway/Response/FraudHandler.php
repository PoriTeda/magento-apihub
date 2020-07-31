<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Cybersource\Gateway\Validator\DecisionValidator;
use Magento\Payment\Model\InfoInterface;

/**
 * Class FraudHandler
 */
abstract class FraudHandler implements HandlerInterface
{
    const RISK_SCORE = 'risk_score';

    const RISK_FACTORS = 'risk_factors';

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if ((string)$response[DecisionValidator::DECISION] !== 'REVIEW') {
            return;
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        $payment = $paymentDO->getPayment();

        if ($this->getRiskScore($response)) {
            $paymentDO->getPayment()
                ->setAdditionalInformation(
                    self::RISK_SCORE,
                    $this->getRiskScore($response)
                );
        }

        if ($this->getRiskFactors($response)) {
            $paymentDO->getPayment()
                ->setAdditionalInformation(
                    self::RISK_FACTORS,
                    $this->getRiskFactors($response)
                );
        }

        $this->setPaymentState($payment);
    }

    /**
     * Returns risk factors form response
     *
     * @param array $response
     * @return null | string
     */
    abstract protected function getRiskFactors(array $response);

    /**
     * Returns risk factors form response
     *
     * @param array $response
     * @return null | string
     */
    abstract protected function getRiskScore(array $response);

    /**
     * Sets payment state
     *
     * @param InfoInterface $payment
     * @return void
     */
    abstract protected function setPaymentState(InfoInterface $payment);
}
