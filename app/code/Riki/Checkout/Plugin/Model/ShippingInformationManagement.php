<?php
namespace Riki\Checkout\Plugin\Model;

use Riki\Customer\Model\Address\AddressType;

class ShippingInformationManagement
{
    protected $addressHelper;

    protected $customerSession;

    /**
     * @param \Riki\Checkout\Helper\Address $addressHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Quote\Model\Quote\AddressFactory $quoteAddress
     */
    public function __construct(
        \Riki\Checkout\Helper\Address $addressHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddress
    ){
        $this->addressHelper = $addressHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * if shipping address type is not company, set billing by home type address
     *
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return array
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ){
        $address = $addressInformation->getBillingAddress();

        $customerId = $this->customerSession->getCustomerId();
        if($addressType = $address->getCustomAttribute('riki_type_address')){

            $addressType = $addressType->getValue();

            if($addressType != AddressType::OFFICE && $addressType != AddressType::HOME){

                $homeQuoteAddress = $this->addressHelper->getDefaultHomeQuoteAddressByCustomerId($customerId);

                if($homeQuoteAddress){
                    $addressInformation->setBillingAddress($homeQuoteAddress);
                }
            }
        }

        return [$cartId, $addressInformation];
    }
}