<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Gateway\Response;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

class AvsHandler implements HandlerInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var array
     */
    static protected $codesPosition = [
        1 => 'postcode_avs',
        2 => 'address_avs',
        3 => 'country_comparison'
    ];

    /**
     * @var string
     */
    const FRAUD_CASE = 'avs_fraud_case';

    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($response['AVS'])) {
            return;
        }

        $codes = str_split((string)$response['AVS']);
        if (array_intersect_key(static::$codesPosition, array_keys($codes))
                !== static::$codesPosition
        ) {
            return;
        }

        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        foreach (static::$codesPosition as $codePosition => $codeKey) {
            $payment->setAdditionalInformation(
                $codeKey,
                (int)$codePosition
            );
        }

        $fraudCases = explode(
            ',',
            (string)$this->config->getValue(static::FRAUD_CASE)
        );

        if (empty($fraudCases)) {
            return;
        }

        foreach (static::$codesPosition as $codePosition => $codeKey) {
            if (in_array($codes[$codePosition], $fraudCases)) {
                ContextHelper::assertOrderPayment($payment);
                /** @var Payment $payment */

                $payment->setIsFraudDetected(true);
                break;
            }
        }
    }
}
