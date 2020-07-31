<?php

namespace Riki\OfflinePayments\Helper;

use Riki\Customer\Model\Address\AddressType;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** @var \Magento\Customer\Api\AddressRepositoryInterface  */
    protected $addressRepository;

    /** @var \Magento\Customer\Api\CustomerRepositoryInterface  */
    protected $customerRepository;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    )
    {
        parent::__construct($context);

        $this->addressRepository = $addressRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     */
    public function isGiftOrderQuote(\Magento\Quote\Model\Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();

        /**
         * Case Import order csv
         */
        if($quote->getData('original_unique_id') !='' || $quote->getData('is_csv_import_order_flag')==true)
        {
            $rikiAddressTypeAttr = $shippingAddress->getCustomAttribute('riki_type_address');
            return $this->checkAddressWithPaymentCOD($quote,$rikiAddressTypeAttr);
        }

        /**
         * Case normal
         */
        if ($addressId = $shippingAddress->getCustomerAddressId()) {

            try {
                $address = $this->addressRepository->getById($addressId);
            } catch (\Exception $e) {
                return false;
            }

            $rikiAddressTypeAttr = $address->getCustomAttribute('riki_type_address');
            return $this->checkAddressWithPaymentCOD($quote,$rikiAddressTypeAttr);

        }else{
            //allow cod for machine maintenance
            return false;
        }

        return true;
    }

    /**
     * @param $quote
     * @param $rikiAddressType
     * @return bool
     */
    public function checkAddressWithPaymentCOD($quote,$rikiAddressType)
    {
        if($rikiAddressType && $rikiAddressType->getValue() !='')
        {
            $rikiAddressType = $rikiAddressType->getValue();

            if ($rikiAddressType == AddressType::HOME) {
                return false;
            }

            try {
                $customer = $this->customerRepository->getById($quote->getCustomerId());
            } catch (\Exception $e) {
                return false;
            }

            if ($membershipAttr = $customer->getCustomAttribute('membership')) {
                if (in_array(3, explode(',', $membershipAttr->getValue()))) {
                    if ($rikiAddressType == AddressType::OFFICE) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

}
