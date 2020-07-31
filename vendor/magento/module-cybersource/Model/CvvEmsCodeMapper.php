<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Model;

use Magento\Payment\Api\PaymentVerificationInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Processes CVV codes mapping from Cybersource transaction to
 * electronic merchant systems standard.
 *
 * @see https://www.cybersource.com/developers/other_resources/quick_references/cvn_results/
 * @see http://www.emsecommerce.net/avs_cvv2_response_codes.htm
 */
class CvvEmsCodeMapper implements PaymentVerificationInterface
{
    /**
     * CVV code field name for Cybersource.
     *
     * @var string
     */
    private static $authCvResult = 'auth_cv_result';

    /**
     * Default code for mismatch mapping.
     *
     * @var string
     */
    private static $notProvidedCode = 'P';

    /**
     * CVV codes mapping list.
     *
     * @var array
     */
    private static $cvvMap = [
        'D' => 'P',
        'I' => 'N',
        'M' => 'M',
        'N' => 'N',
        'P' => 'P',
        'S' => 'S',
        'U' => 'U',
        'X' => 'P',
        '1' => 'P',
        '2' => 'P',
        '3' => 'P'
    ];

    /**
     * Returns payment CVV verification code.
     *
     * @param OrderPaymentInterface $orderPayment
     * @return string
     * @throws \InvalidArgumentException if specified order payment has different payment method code.
     */
    public function getCode(OrderPaymentInterface $orderPayment)
    {
        if ($orderPayment->getMethod() !== 'cybersource') {
            throw new \InvalidArgumentException(
                'The "' . $orderPayment->getMethod() . '" does not supported by Cybersource CVV mapper.'
            );
        }

        $additionalInfo = $orderPayment->getAdditionalInformation();
        if (empty($additionalInfo[self::$authCvResult])) {
            return self::$notProvidedCode;
        }

        $cvv = $additionalInfo[self::$authCvResult];

        return isset(self::$cvvMap[$cvv]) ? self::$cvvMap[$cvv] : self::$notProvidedCode;
    }
}
