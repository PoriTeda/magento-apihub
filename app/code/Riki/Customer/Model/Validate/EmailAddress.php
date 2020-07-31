<?php
namespace Riki\Customer\Model\Validate;
class EmailAddress extends \Magento\Eav\Model\Attribute\Data\Text{

    /**
     * Extract data from request and return value
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return array|string|bool
     */
     public function extractValue(\Magento\Framework\App\RequestInterface $request){
         return parent::extractValue($request);
     }

    /**
     * Validate data
     *
     * @param array|string $value
     * @throws CoreException
     * @return bool
     */
     public function validateValue($value){
         $errors = [];
         $attribute = $this->getAttribute();
         $label = __($attribute->getStoreLabel());

         if ($value === false) {
             // try to load original value and validate it
             $value = $this->getEntity()->getDataUsingMethod($attribute->getAttributeCode());
         }

         if ($attribute->getIsRequired() && empty($value) && $value !== '0') {
             $errors[] = __('"%1" is a required value.', $label);
         }

         if (!$errors && !$attribute->getIsRequired() && empty($value)) {
             return true;
         }

         // validate length
         $length = $this->_string->strlen(trim($value));

         $validateRules = $attribute->getValidateRules();
         if (!empty($validateRules['min_text_length']) && $length < $validateRules['min_text_length']) {
             $v = $validateRules['min_text_length'];
             $errors[] = __('"%1" length must be equal or greater than %2 characters.', $label, $v);
         }
         if (!empty($validateRules['max_text_length']) && $length > $validateRules['max_text_length']) {
             $v = $validateRules['max_text_length'];
             $errors[] = __('"%1" length must be equal or less than %2 characters.', $label, $v);
         }

         // Rewrite default rule
         $result = $this->_validateInputRule($value);
         if ($result !== true) {
             $errors = array_merge($errors, $result);
         }
         if (count($errors) == 0) {
             return true;
         }

         return $errors;   
     }

    /**
     * Export attribute value to entity model
     *
     * @param array|string $value
     * @return $this
     */
     public function compactValue($value){
         return parent::compactValue($value);
     }

    /**
     * Restore attribute value from SESSION to entity model
     *
     * @param array|string $value
     * @return $this
     */
     public function restoreValue($value){
         return parent::restoreValue($value);
     }

    /**
     * Return formatted attribute value from entity model
     *
     * @param string $format
     * @return string|array
     */
     public function outputValue($format = \Magento\Eav\Model\AttributeDataFactory::OUTPUT_FORMAT_TEXT){
         return parent::outputValue($format);
     }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if $value is a valid email address
     * according to RFC2822
     *
     * @link   http://www.ietf.org/rfc/rfc2822.txt RFC2822
     * @link   http://www.columbia.edu/kermit/ascii.html US-ASCII characters
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        $validEmail = preg_match('/^[-!#$%&*+\.\/0-9=?A-Z\^_`a-z{|}~\\\]+@[0-9a-zA-Z\.\-]+\.[0-9a-zA-Z\-]+$/',$value);

        if ($validEmail) {
            return true;
        }
        return false;
    }
    /**
     * Validate value by attribute input validation rule
     *
     * @param string $value
     * @return string|true
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _validateInputRule($value)
    {
        // skip validate empty value
        if (empty($value)) {
            return true;
        }

        $label = $this->getAttribute()->getStoreLabel();
        $validateRules = $this->getAttribute()->getValidateRules();

        if (!empty($validateRules['input_validation'])) {
            switch ($validateRules['input_validation']) {
                case 'alphanumeric':
                    $validator = new \Zend_Validate_Alnum(true);
                    $validator->setMessage(__('"%1" invalid type entered.', $label), \Zend_Validate_Alnum::INVALID);
                    $validator->setMessage(
                        __('"%1" contains non-alphabetic or non-numeric characters.', $label),
                        \Zend_Validate_Alnum::NOT_ALNUM
                    );
                    $validator->setMessage(__('"%1" is an empty string.', $label), \Zend_Validate_Alnum::STRING_EMPTY);
                    if (!$validator->isValid($value)) {
                        return $validator->getMessages();
                    }
                    break;
                case 'numeric':
                    $validator = new \Zend_Validate_Digits();
                    $validator->setMessage(__('"%1" invalid type entered.', $label), \Zend_Validate_Digits::INVALID);
                    $validator->setMessage(
                        __('"%1" contains non-numeric characters.', $label),
                        \Zend_Validate_Digits::NOT_DIGITS
                    );
                    $validator->setMessage(
                        __('"%1" is an empty string.', $label),
                        \Zend_Validate_Digits::STRING_EMPTY
                    );
                    if (!$validator->isValid($value)) {
                        return $validator->getMessages();
                    }
                    break;
                case 'alpha':
                    $validator = new \Zend_Validate_Alpha(true);
                    $validator->setMessage(__('"%1" invalid type entered.', $label), \Zend_Validate_Alpha::INVALID);
                    $validator->setMessage(
                        __('"%1" contains non-alphabetic characters.', $label),
                        \Zend_Validate_Alpha::NOT_ALPHA
                    );
                    $validator->setMessage(__('"%1" is an empty string.', $label), \Zend_Validate_Alpha::STRING_EMPTY);
                    if (!$validator->isValid($value)) {
                        return $validator->getMessages();
                    }
                    break;
                case 'email':
                    /**
                    __("'%value%' appears to be a DNS hostname but the given punycode notation cannot be decoded")
                    __("Invalid type given. String expected")
                    __("'%value%' appears to be a DNS hostname but contains a dash in an invalid position")
                    __("'%value%' does not match the expected structure for a DNS hostname")
                    __("'%value%' appears to be a DNS hostname but cannot match against hostname schema for TLD '%tld%'")
                    __("'%value%' does not appear to be a valid local network name")
                    __("'%value%' does not appear to be a valid URI hostname")
                    __("'%value%' appears to be an IP address but IP addresses are not allowed")
                    __("'%value%' appears to be a local network name but local network names are not allowed")
                    __("'%value%' appears to be a DNS hostname but cannot extract TLD part")
                    __("'%value%' appears to be a DNS hostname but cannot match TLD against known list")
                     */
                    $validator = new \Zend_Validate_EmailAddress(
                        ['allow' => ['allow' => \Zend_Validate_Hostname::ALLOW_ALL, 'tld' => false]]
                    );
                    $validator->setMessage(
                        __('"%1" invalid type entered.', $label),
                        \Zend_Validate_EmailAddress::INVALID
                    );
                    $validator->setMessage(
                        __('"%1" is not a valid email address.', $label),
                        \Zend_Validate_EmailAddress::INVALID_FORMAT
                    );
                    $validator->setMessage(
                        __('"%1" is not a valid hostname.', $label),
                        \Zend_Validate_EmailAddress::INVALID_HOSTNAME
                    );
                    $validator->setMessage(
                        __('"%1" is not a valid hostname.', $label),
                        \Zend_Validate_EmailAddress::INVALID_MX_RECORD
                    );
                    $validator->setMessage(
                        __('"%1" is not a valid hostname.', $label),
                        \Zend_Validate_EmailAddress::INVALID_MX_RECORD
                    );
                    $validator->setMessage(
                        __('"%1" is not a valid email address.', $label),
                        \Zend_Validate_EmailAddress::DOT_ATOM
                    );
                    $validator->setMessage(
                        __('"%1" is not a valid email address.', $label),
                        \Zend_Validate_EmailAddress::QUOTED_STRING
                    );
                    $validator->setMessage(
                        __('"%1" is not a valid email address.', $label),
                        \Zend_Validate_EmailAddress::INVALID_LOCAL_PART
                    );
                    $validator->setMessage(
                        __('"%1" uses too many characters.', $label),
                        \Zend_Validate_EmailAddress::LENGTH_EXCEEDED
                    );
                    $validator->setMessage(
                        __("'%value%' looks like an IP address, which is not an acceptable format."),
                        \Zend_Validate_Hostname::IP_ADDRESS_NOT_ALLOWED
                    );
                    $validator->setMessage(
                        __("'%value%' looks like a DNS hostname but we cannot match the TLD against known list."),
                        \Zend_Validate_Hostname::UNKNOWN_TLD
                    );
                    $validator->setMessage(
                        __("'%value%' looks like a DNS hostname but contains a dash in an invalid position."),
                        \Zend_Validate_Hostname::INVALID_DASH
                    );
                    $validator->setMessage(
                        __(
                            "'%value%' looks like a DNS hostname but we cannot match it against the hostname schema for TLD '%tld%'."
                        ),
                        \Zend_Validate_Hostname::INVALID_HOSTNAME_SCHEMA
                    );
                    $validator->setMessage(
                        __("'%value%' looks like a DNS hostname but cannot extract TLD part."),
                        \Zend_Validate_Hostname::UNDECIPHERABLE_TLD
                    );
                    $validator->setMessage(
                        __("'%value%' does not look like a valid local network name."),
                        \Zend_Validate_Hostname::INVALID_LOCAL_NAME
                    );
                    $validator->setMessage(
                        __("'%value%' looks like a local network name, which is not an acceptable format."),
                        \Zend_Validate_Hostname::LOCAL_NAME_NOT_ALLOWED
                    );
                    $validator->setMessage(
                        __(
                            "'%value%' appears to be a DNS hostname, but the given punycode notation cannot be decoded."
                        ),
                        \Zend_Validate_Hostname::CANNOT_DECODE_PUNYCODE
                    );
                    // rewrite Magento email validate rule
                    if (!$this->isValid($value)) {
                        return array_unique($validator->getMessages());
                    }
                    break;
                case 'url':
                    $parsedUrl = parse_url($value);
                    if ($parsedUrl === false || empty($parsedUrl['scheme']) || empty($parsedUrl['host'])) {
                        return [__('"%1" is not a valid URL.', $label)];
                    }
                    $validator = new \Zend_Validate_Hostname();
                    if (!$validator->isValid($parsedUrl['host'])) {
                        return [__('"%1" is not a valid URL.', $label)];
                    }
                    break;
                case 'date':
                    $validator = new \Zend_Validate_Date(
                        [
                            'format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                            'locale' => $this->_localeResolver->getLocale(),
                        ]
                    );
                    $validator->setMessage(__('"%1" invalid type entered.', $label), \Zend_Validate_Date::INVALID);
                    $validator->setMessage(__('"%1" is not a valid date.', $label), \Zend_Validate_Date::INVALID_DATE);
                    $validator->setMessage(
                        __('"%1" does not fit the entered date format.', $label),
                        \Zend_Validate_Date::FALSEFORMAT
                    );
                    if (!$validator->isValid($value)) {
                        return array_unique($validator->getMessages());
                    }

                    break;
            }
        }
        return true;
    }
}