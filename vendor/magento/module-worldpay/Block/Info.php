<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Block;

use Magento\Framework\Phrase;
use Magento\Worldpay\Model\Adminhtml\Source\FraudCase;

class Info extends \Magento\Payment\Block\ConfigurableInfo
{
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field)
    {
        switch ($field) {
            case 'cc_type':
                return __('Credit card type');
            case 'cvv_result':
                return __('CVV result');
            case 'postcode_avs':
                return __('Postcode AVS check');
            case 'address_avs':
                return __('Address AVS check');
            case 'country_comparison':
                return __('Country comparison check');
            case 'waf_merch_message':
                return __('Risk Score');
            default:
                return parent::getLabel($field);
        }
    }

    /**
     * Returns value view
     *
     * @param string $field
     * @param string $value
     * @return string | Phrase
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getValueView($field, $value)
    {
        switch ($field) {
            case 'cvv_result':
            case 'postcode_avs':
            case 'address_avs':
            case 'country_comparison':
                switch ($value) {
                    case FraudCase::NOT_SUPPORTED:
                        return __('Not supported');
                    case FraudCase::NOT_CHECKED:
                        return __('Not checked');
                    case FraudCase::MATCHED:
                        return __('Matched');
                    case FraudCase::NOT_MATCHED:
                        return __('Not matched');
                    case FraudCase::PARTIALLY_MATCHED:
                        return __('Partially matched');
                }
                return parent::getValueView($field, $value);
            default:
                return parent::getValueView($field, $value);
        }
    }
}
