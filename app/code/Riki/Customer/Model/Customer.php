<?php
namespace Riki\Customer\Model;

class Customer extends \Magento\Customer\Model\Customer
{
    const MEMBERSHIP_AMB = 3;
    /**
     * Get full customer name
     *
     * @return string
     */
    public function getName()
    {
        $name = '';

        $attributesToValue = [
            'prefix'    =>  $this->getPrefix(),
            'lastname'    =>  $this->getLastname(),
            'lastnamekana'    =>  $this->getLastnamekana(),
            'middlename'    =>  $this->getMiddlename(),
            'firstname'    =>  $this->getFirstname(),
            'firstnamekana'    =>  $this->getFirstnamekana(),
            'suffix'    =>  $this->getSuffix(),
        ];

        $attributesToSortOrder = [];

        foreach ($attributesToValue as $code => $value) {
            $sortOrder = 0;

            if ($this->_config->getAttribute('customer', $code) &&
                $this->_config->getAttribute('customer', $code)->getSortOrder()
            ) {
                $sortOrder = $this->_config->getAttribute('customer', $code)->getSortOrder();
            }

            $attributesToSortOrder[$code] = $sortOrder;
        }

        asort($attributesToSortOrder);

        foreach ($attributesToSortOrder as $code => $sortOrder) {
            if ($this->_config->getAttribute('customer', $code) &&
                $this->_config->getAttribute('customer', $code)->getIsVisible() &&
                isset($attributesToValue[$code]) &&
                $attributesToValue[$code]
            ) {
                $name .= $attributesToValue[$code];
            }
        }

        return $name;
    }
    /**
     * Validate customer attribute values.
     * For existing customer password + confirmation will be validated only when password is set
     * (i.e. its change is requested)
     *
     * @return bool|string[]
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function validate()
    {
        $errors = [];
        if (!\Zend_Validate::is(trim($this->getFirstname()), 'NotEmpty')) {
            $errors[] = __('Please enter a first name.');
        }

        if (!\Zend_Validate::is(trim($this->getLastname()), 'NotEmpty')) {
            $errors[] = __('Please enter a last name.');
        }

        $validEmail = preg_match(
            '/^[-!#$%&*+\.\/0-9=?A-Z\^_`a-z{|}~\\\]+@[0-9a-zA-Z\.\-]+\.[0-9a-zA-Z\-]+$/',
            $this->getEmail()
        );

        if (!$validEmail && $this->getEmail() !='') {
            $errors[] = __('Please correct this email address: "%1".', $this->getEmail());
        }

        $entityType = $this->_config->getEntityType('customer');
        $attribute = $this->_config->getAttribute($entityType, 'dob');
        if ($attribute->getIsRequired() && '' == trim($this->getDob())) {
            $errors[] = __('Please enter a date of birth.');
        }
        $attribute = $this->_config->getAttribute($entityType, 'taxvat');
        if ($attribute->getIsRequired() && '' == trim($this->getTaxvat())) {
            $errors[] = __('Please enter a TAX/VAT number.');
        }
        $attribute = $this->_config->getAttribute($entityType, 'gender');
        if ($attribute->getIsRequired() && '' == trim($this->getGender())) {
            $errors[] = __('Please enter a gender.');
        }

        $transport = new \Magento\Framework\DataObject(
            ['errors' => $errors]
        );
        $this->_eventManager->dispatch('customer_validate', ['customer' => $this, 'transport' => $transport]);
        $errors = $transport->getErrors();

        if (empty($errors)) {
            return true;
        }
        return $errors;
    }

    /**
     * @param string $attributeCode
     * @return bool|\Magento\Framework\DataObject
     */
    public function getPrimaryAddress($attributeCode)
    {
        $addresses = $this->getAddressesCollection()->getItems();
        $hasCompany = $companyAddress = $homeAddress = false;
        if (!empty($addresses)) {
            foreach ($addresses as $address) {
                if ($address->getData('riki_type_address') == \Riki\Customer\Model\Address\AddressType::OFFICE) {
                    $hasCompany = true;
                    $companyAddress = $address;
                }
                if ($address->getData('riki_type_address') == \Riki\Customer\Model\Address\AddressType::HOME) {
                    $homeAddress = $address;
                }
            }
            if ($hasCompany && $companyAddress) {
                return $companyAddress;
            } elseif ($homeAddress) {
                return $homeAddress;
            } else {
                return false;
            }
        }
    }
}
