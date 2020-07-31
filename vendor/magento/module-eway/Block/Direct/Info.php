<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Block\Direct;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;

/**
 * Class Info
 */
class Info extends ConfigurableInfo
{
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getLabel($field)
    {
        switch ($field) {
            case 'cc_type':
                return __('Payment Type');
            case 'transaction_type':
                return __('Transaction Type');
            case 'transaction_id':
                return __('Transaction ID');
            case 'beagle_score':
                return __('BeagleScore');
            case 'cvv_validation':
                return __('CVV Verification');
            case 'address_verification':
                return __('Address Verification');
            case 'email_verification':
                return __('Email Verification');
            case 'mobile_verification':
                return __('Mobile Verification');
            case 'phone_verification':
                return __('Phone Verification');
            case 'card_number':
                return __('Card number');
            case 'card_expiry_date':
                return __('Expiration Date');
            case 'response_code':
                return __('Response Code');
            case 'fraud_messages':
                return __('Fraud Message');
            case 'approve_messages':
                return __('Approve Message');
            default:
                return parent::getLabel($field);
        }
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getValueView($field, $value)
    {
        switch ($field) {
            case 'fraud_messages':
            case 'approve_messages':
                return $this->formatMessages($value);
            default:
                return parent::getValueView($field, $value);
        }
    }

    /**
     * Formatting messages
     *
     * @param array $messagesCodes
     * @return string
     */
    private function formatMessages(array $messagesCodes)
    {
        $result = [];
        $messages = $this->getMessageCode();
        foreach ($messagesCodes as $code) {
            if (isset($messages[$code])) {
                $result[] = sprintf('%s - %s', $code, $messages[$code]);
            }
        }

        return implode(', ', $result);
    }

    /**
     * Getting codes of messages and the text representation
     *
     * @return array
     */
    private static function getMessageCode()
    {
        return [
            'F7000' => 'Undefined Fraud Error',
            'F7001' => 'Challenged Fraud',
            'F7002' => 'Country Match Fraud',
            'F7003' => 'High Risk Country Fraud',
            'F7004' => 'Anonymous Proxy Fraud',
            'F7005' => 'Transparent Proxy Fraud',
            'F7006' => 'Free Email Fraud',
            'F7007' => 'International Transaction Fraud',
            'F7008' => 'Risk Score Fraud',
            'F7009' => 'Denied Fraud',
            'F9010' => 'High Risk Billing Country',
            'F9011' => 'High Risk Credit Card Country',
            'F9012' => 'High Risk Customer IP Address',
            'F9013' => 'High Risk Email Address',
            'F9014' => 'High Risk Shipping Country',
            'F9015' => 'Multiple card numbers for single email address',
            'F9016' => 'Multiple card numbers for single location',
            'F9017' => 'Multiple email addresses for single card number',
            'F9018' => 'Multiple email addresses for single location',
            'F9019' => 'Multiple locations for single card number',
            'F9020' => 'Multiple locations for single email address',
            'F9021' => 'Suspicious Customer First Name',
            'F9022' => 'Suspicious Customer Last Name',
            'F9023' => 'Transaction Declined',
            'F9024' => 'Multiple transactions for same address with known credit card',
            'F9025' => 'Multiple transactions for same address with new credit card',
            'F9026' => 'Multiple transactions for same email with new credit card',
            'F9027' => 'Multiple transactions for same email with known credit card',
            'F9028' => 'Multiple transactions for new credit card',
            'F9029' => 'Multiple transactions for known credit card',
            'F9030' => 'Multiple transactions for same email address',
            'F9031' => 'Multiple transactions for same credit card',
            'F9032' => 'Invalid Customer Last Name',
            'F9033' => 'Invalid Billing Street',
            'F9034' => 'Invalid Shipping Street',
            'F9037' => 'Suspicious Customer Email Address',
            'F9050' => 'High Risk Email Address and amount',

            'A0000' => 'Undefined Approved',
            'A2000' => 'Transaction Approved',
            'A2008' => 'Honour With Identification',
            'A2010' => 'Approved For Partial Amount',
            'A2011' => 'Approved VIP',
            'A2016' => 'Approved Update Track 3',
        ];
    }
}
