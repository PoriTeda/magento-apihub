<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Block;

use Magento\Framework\Phrase;

class Info extends \Magento\Payment\Block\ConfigurableInfo
{
    /*
     * Risk factors field name
     */
    const RISK_FACTORS = 'risk_factors';

    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getLabel($field)
    {
        switch ($field) {
            case 'auth_avs_code':
                return __('AVS result code');
            case 'auth_cv_result':
                return __('CVN result code');
            case 'card_number':
                return __('Card number');
            case 'card_expiry_date':
                return __('Card expiry date');
            case 'decision':
                return __('Decision');
            case 'transaction_id':
                return __('Transaction ID');
            case 'risk_score':
                return __('Risk Score');
            case 'risk_factors':
                return __('Risk Factor');
            case 'reference_number':
                return __('Reference number');
            default:
                return __($field);
        }
    }

    /**
     * Sets data to transport
     *
     * @param \Magento\Framework\DataObject $transport
     * @param string $field
     * @param string $value
     * @return void
     */
    protected function setDataToTransfer(
        \Magento\Framework\DataObject $transport,
        $field,
        $value
    ) {
        if ($field === self::RISK_FACTORS) {
            $this->setRiskFactors($transport, $value);
            return;
        }

        $transport->setData(
            (string)$this->getLabel($field),
            (string)$this->getValueView(
                $field,
                $value
            )
        );
    }

    /**
     * Set Risk Factors info
     * @param \Magento\Framework\DataObject|array|null $transport
     * @param string $value
     * @return void
     */
    private function setRiskFactors($transport, $value)
    {
        foreach (explode('^', $value) as $code) {
            $transport->setData(
                (string)$this->getLabel(self::RISK_FACTORS) . ' ' . $code,
                (string)$this->explainRiskFactorsCode($code)
            );
        }
    }

    /**
     * Explain Risk Factors codes
     *
     * @param string $code
     * @return Phrase
     */
    private function explainRiskFactorsCode($code)
    {
        // @codingStandardsIgnoreStart
        $factors = [
            'A' => __('Excessive address change. The customer changed the billing address two or more times in the last six months'),
            'B' => __('Card BIN or authorization risk. Risk factors a e related to credit card BIN and/or card authorization checks'),
            'C' => __('High number of account numbers. The customer used more than six credit cards numbers in the last six months'),
            'D' => __('Email address impact. The customer uses a free email provider, or the email address is risky'),
            'E' => __('Positive list. The customer is on your positive list. Decision Manager Developer Guide Using the Simple Order AP  January 201 10 Appendix  Information and Reply Code'),
            'F' => __('Negative list. The account number, street add ess, email address, or IP address for this order appears on your negative list'),
            'G' => __('Geolocation inconsistencies. The customer’s email domain, phone number, billing address, shipping address, or IP address is suspicious'),
            'H' => __('Excessive name changes. The customer changed the name two or more times in the last six months'),
            'I' => __('Internet inconsistencies. The IP address  nd email domain are not consistent with the billing address'),
            'N' => __('Nonsensical input. The customer name and address fields contain meaningless words or language'),
            'O' => __('Obscenities. The customer’s input contains obscene words'),
            'P' => __('Identity morphing. Multiple values of an  dentity element are linked to a value of a different identity element. For example, m ltiple phone numbers are linked to a single account number'),
            'Q' => __('Phone inconsistencies. The customer’s phone number is suspicious'),
            'R' => __('Risky order. The transaction, customer, and merchant information show multiple high-risk correlations'),
            'T' => __('Time hedge. The customer is attempting a purchase outside of the expected hours'),
            'U' => __('Unverifiable address. The billing or shipping address cannot be verified'),
            'V' => __('Velocity. The account number was use  many times in the past 15 minutes'),
            'W' => __('Marked as suspect. The billing or shipping address is similar to an address previously marked as suspect'),
            'Y' => __('Gift Order. The street address, city, state, or country of the billing and shipping addresses do not correlate'),
            'Z' => __('Invalid value. Because the request contains an unexpected value, a default value was substituted. Although the transact on can still be processed, examine the request carefully for abnormalities in the order')
        ];
        // @codingStandardsIgnoreEnd

        return isset($factors[$code])
            ? $factors[$code] : __('Unknown factor. Please contact Cybersource customer service');
    }
}
