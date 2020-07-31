<?php
namespace Riki\Quote\Model\Quote\Address;

class Validator extends \Magento\Quote\Model\Quote\Address\Validator{

    public function isValid($value)
    {
        $messages = [];
        $email = $value->getEmail();
        $validEmail = preg_match('/^[-!#$%&*+\.\/0-9=?A-Z\^_`a-z{|}~\\\]+@[0-9a-zA-Z\.\-]+\.[0-9a-zA-Z\-]+$/', $email);

        if (!$validEmail && $email != '') {
            $messages[] = __('Invalid email format: "%1".', $email);
        }

        $countryId = $value->getCountryId();
        if (!empty($countryId)) {
            $country = $this->countryFactory->create();
            $country->load($countryId);
            if (!$country->getId()) {
                $messages['invalid_country_code'] = __('Invalid country code');
            }
        }

        $this->_addMessages($messages);

        return empty($messages);
    }
    /**
     * {@inheritdoc}
     *
     * @param array $messages
     */
    protected function _addMessages(array $messages)
    {
        // address validator is singleton object, then risk/caused ErrorException: Array to string when generating multiple order (on consumer queue, cronjob)
        foreach ($messages as $key => $message) {
            if (isset($this->_messages[$key])) {
                unset($messages[$key]);
            }
        }

        parent::_addMessages($messages);
    }
}