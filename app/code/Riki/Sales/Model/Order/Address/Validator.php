<?php
namespace Riki\Sales\Model\Order\Address;

class Validator extends \Magento\Sales\Model\Order\Address\Validator
{
    /**
     *
     * @param \Magento\Sales\Model\Order\Address $address
     * @return array
     */
    public function validate(\Magento\Sales\Model\Order\Address $address)
    {
        $warnings = [];
        foreach ($this->required as $code => $label) {
            if (!$address->hasData($code)) {
                $warnings[] = __('%s is a required field', $label);
            }
        }
        $validEmail = preg_match('/^[-!#$%&*+\.\/0-9=?A-Z\^_`a-z{|}~\\\]+@[0-9a-zA-Z\.\-]+\.[0-9a-zA-Z\-]+$/', $address->getEmail());
        if (!$validEmail && $address->getEmail()!='') {
            $warnings[] = __('Invalid email format: "%1".', $address->getEmail());
        }
        $addressType = [
            \Magento\Sales\Model\Order\Address::TYPE_BILLING,
            \Magento\Sales\Model\Order\Address::TYPE_SHIPPING,
            \Riki\Quote\Model\Quote\Address::ADDRESS_TYPE_CUSTOMER
        ];

        if (!filter_var(in_array($address->getAddressType(), $addressType))) {
            $warnings[] = __('Address type doesn\'t match required options');
        }
        return $warnings;
    }
}
