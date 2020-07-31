<?php
namespace  Riki\Checkout\Plugin\Quote\Model;

use Riki\Customer\Model\Address\AddressType;

class BillingAddressManagement
{
    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    protected $addressHelper;

    public function __construct(
        \Riki\Checkout\Helper\Address $addressHelper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ){
        $this->quoteRepository = $quoteRepository;
        $this->addressHelper = $addressHelper;
    }

    /**
     * if shipping address type is not company, set billing by home type address
     *
     * @param \Magento\Quote\Model\BillingAddressManagement $subject
     * @param $cartId
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @param bool|false $useForShipping
     * @return array
     */
    public function beforeAssign(
        \Magento\Quote\Model\BillingAddressManagement $subject,
        $cartId,
        \Magento\Quote\Api\Data\AddressInterface $address,
        $useForShipping = false
    ){
        if($addressType = $address->getCustomAttribute('riki_type_address')){

            $addressType = $addressType->getValue();

            if($addressType != AddressType::OFFICE && $addressType != AddressType::HOME){

                $homeQuoteAddress = $this->addressHelper->getDefaultHomeQuoteAddressByCustomerId($address->getCustomerId());

                if($homeQuoteAddress){
                    $address = $homeQuoteAddress;
                }
            }
        }

        // Remove save in addressbook for billing addres in checkout
        $address->setSaveInAddressBook(0);

        return [$cartId, $address, $useForShipping];
    }
}