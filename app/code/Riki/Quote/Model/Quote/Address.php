<?php

namespace Riki\Quote\Model\Quote;

class Address extends \Magento\Quote\Model\Quote\Address
{
    const ADDRESS_TYPE_CUSTOMER = 'customer';
    const ADDRESS_DEFAULT_CITY = 'None';
    const ADDRESS_DEFAULT_COUNTRY = 'JP';
    /**
     * Set the required fields
     *
     * @return void
     */
    protected function _populateBeforeSaveData()
    {
        if ($this->getQuote()) {
            $this->_dataSaveAllowed = (bool)$this->getQuote()->getId();

            if ($this->getQuote()->getId()) {
                $this->setQuoteId($this->getQuote()->getId());
            }
            $this->setCustomerId($this->getQuote()->getCustomerId());

            /**
             * Init customer address id if customer address is assigned
             */
            if ($this->getCustomerAddressData()) {
                $this->setCustomerAddressId($this->getCustomerAddressData()->getId());
            }

            if (!$this->getId() && $this->getSameAsBilling() === null) {
                $this->setSameAsBilling((int)$this->_isSameAsBilling());
            }
        }
    }

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

            if ($this->_eavConfig->getAttribute('customer_address', $code) &&
                $this->_eavConfig->getAttribute('customer_address', $code)->getSortOrder()
            ) {
                $sortOrder = $this->_eavConfig->getAttribute('customer_address', $code)->getSortOrder();
            }

            $attributesToSortOrder[$code] = $sortOrder;
        }

        asort($attributesToSortOrder);

        foreach ($attributesToSortOrder as $code => $sortOrder) {
            if ($this->_eavConfig->getAttribute('customer_address', $code) &&
                $this->_eavConfig->getAttribute('customer_address', $code)->getIsVisible() &&
                isset($attributesToValue[$code]) &&
                $attributesToValue[$code]
            ) {
                $name .= $attributesToValue[$code];
            }
        }

        return $name;
    }
}
