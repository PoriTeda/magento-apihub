<?php

namespace Riki\Customer\Plugin\Address;

class ValidateAddressBeforeSave
{
    /**
     * @var \Riki\Customer\Helper\ValidateAddress
     */
    protected $validateAddress;

    /**
     * ValidateAddress constructor.
     * @param \Riki\Customer\Helper\ValidateAddress $validateAddress
     */
    public function __construct(
        \Riki\Customer\Helper\ValidateAddress $validateAddress
    ) {
        $this->validateAddress = $validateAddress;
    }

    /**
     * @param $subject
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Zend_Validate_Exception
     */
    public function beforeSave($subject, \Magento\Customer\Api\Data\AddressInterface $address)
    {
        $inputException = $this->validateAddress->validate($address);
        if ($inputException->wasErrorAdded()) {
            throw $inputException;
        }
        return [$address];
    }
}
