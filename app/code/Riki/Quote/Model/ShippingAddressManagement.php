<?php

namespace Riki\Quote\Model;

class ShippingAddressManagement implements \Riki\Quote\Api\ShippingAddressManagementInterface
{
    /** @var \Magento\Customer\Api\AddressRepositoryInterface  */
    protected $customerAddressRepository;

    /**
     * ShippingAddressManagement constructor.
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
    )
    {
        $this->customerAddressRepository = $addressRepository;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $addressId
     * @return bool
     */
    public function canAssignAddressToQuote(\Magento\Quote\Model\Quote $quote, $addressId)
    {
        try {
            $this->customerAddressRepository->getById(intval($addressId));
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
