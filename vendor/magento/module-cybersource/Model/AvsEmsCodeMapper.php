<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Model;

use Magento\Payment\Api\PaymentVerificationInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Processes AVS codes mapping from Cybersource transaction to
 * electronic merchant systems standard.
 *
 * @see https://www.cybersource.com/developers/other_resources/quick_references/avs_results/
 * @see http://www.emsecommerce.net/avs_cvv2_response_codes.htm
 */
class AvsEmsCodeMapper implements PaymentVerificationInterface
{
    /**
     * AVS code field name for Cybersource.
     *
     * @var string
     */
    private static $authAvsCode = 'auth_avs_code';

    /**
     * Default code for mismatching mapping.
     *
     * @var string
     */
    private static $unavailableCode = '';

    /**
     * AVS codes mapping list.
     *
     * @var array
     */
    private static $avsMap = [
        'A' => 'A',
        'B' => 'B',
        'C' => 'C',
        'D' => 'D',
        'E' => 'E',
        'F' => 'Z',
        'G' => 'G',
        'H' => 'Y',
        'I' => 'I',
        'J' => 'Y',
        'K' => 'N',
        'L' => 'Z',
        'M' => 'M',
        'N' => 'N',
        'O' => 'A',
        'P' => 'Z',
        'R' => 'Y',
        'S' => 'S',
        'T' => 'A',
        'U' => 'U',
        'V' => 'Y',
        'W' => 'W',
        'X' => 'X',
        'Y' => 'Y',
        'Z' => 'Z',
        '1' => 'S',
        '2' => 'E',
        '3' => 'Y',
        '4' => 'N'
    ];

    /**
     * Returns payment AVS verification code.
     *
     * @param OrderPaymentInterface $orderPayment
     * @return string
     * @throws \InvalidArgumentException if specified order payment has different payment method code.
     */
    public function getCode(OrderPaymentInterface $orderPayment)
    {
        if ($orderPayment->getMethod() !== 'cybersource') {
            throw new \InvalidArgumentException(
                'The "' . $orderPayment->getMethod() . '" does not supported by Cybersource AVS mapper.'
            );
        }

        $additionalInfo = $orderPayment->getAdditionalInformation();
        if (empty($additionalInfo[self::$authAvsCode])) {
            return self::$unavailableCode;
        }

        $avs = $additionalInfo[self::$authAvsCode];

        return isset(self::$avsMap[$avs]) ? self::$avsMap[$avs] : self::$unavailableCode;
    }
}
