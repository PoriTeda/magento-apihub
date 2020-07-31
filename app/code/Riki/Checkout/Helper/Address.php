<?php
namespace Riki\Checkout\Helper;
use Magento\Framework\App\Helper\Context;
use Riki\Customer\Model\Address\AddressType;

class Address extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $addressHelper;

    protected $quoteAddress;

    protected $customerIdToAddressHome = [];

    public function __construct(
        Context $context,
        \Riki\Customer\Helper\Address $addressHelper,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddress
    ){
        $this->addressHelper = $addressHelper;
        $this->quoteAddress = $quoteAddress;

        parent::__construct($context);
    }

    /**
     * @param $customerId
     * @return mixed
     */
    public function getDefaultHomeQuoteAddressByCustomerId($customerId){

        if(!isset($this->customerIdToAddressHome[$customerId])){

            $this->customerIdToAddressHome[$customerId] = false;

            $homeAddressData = $this->addressHelper->getAddressListByCustomerId(
                $customerId,
                AddressType::HOME
            );

            if($homeAddressData){
                $newBillingAddress = $this->quoteAddress->create()
                    ->importCustomerAddressData($homeAddressData);

                $this->customerIdToAddressHome[$customerId] = $newBillingAddress;
            }
        }

        return $this->customerIdToAddressHome[$customerId];
    }
}