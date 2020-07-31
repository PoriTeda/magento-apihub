<?php

namespace Riki\Quote\Model;

use Magento\Quote\Model\Quote as QuoteEntity;

class CustomerManagement extends \Magento\Quote\Model\CustomerManagement
{
    public function populateCustomerInfo(QuoteEntity $quote)
    {
        /* When place order with Gillette API with new address and have the option that save in address book is true
         * Always save address before assign customer to quote
         * No need to run this observer to save performance
        */
        if ($quote->getData('is_gillette_quote')) {
            return;
        }
        $customer = $quote->getCustomer();

        //custom

        $existingAddressIds = [];

        $billing = $quote->getBillingAddress();
        $shipping = $quote->isVirtual() ? null : $quote->getShippingAddress();

        if (
            (
                $shipping &&
                !$shipping->getSameAsBilling() &&
                (!$shipping->getCustomerId() || $shipping->getSaveInAddressBook())
            ) ||
            (
                !$billing->getCustomerId() ||
                $billing->getSaveInAddressBook()
            )
        ) {

            $origAddresses = $customer->getAddresses();

            if ($origAddresses !== null) {
                if ($customer->getId()) {
                    $getIdFunc = function ($address) {
                        return $address->getId();
                    };
                    $existingAddressIds = array_map($getIdFunc, $origAddresses);
                }
            }
        }

        if (!$customer->getId()) {
            $customer = $this->accountManagement->createAccountWithPasswordHash(
                $customer,
                $quote->getPasswordHash()
            );
            $quote->setCustomer($customer);
        } else if (!$quote instanceof \Riki\Subscription\Model\Emulator\Cart) { // skip simulator quote
            $this->customerRepository->save($customer);
        }

        //custom set customer address id for add new address case
        $newAddressList = $this->customerRepository->getById($customer->getId())->getAddresses();

        $newAddressIdList = [];

        if ($newAddressList !== null) {

            foreach($newAddressList as $address){
                if(!in_array($address->getId(), $existingAddressIds))
                    $newAddressIdList[] = $address->getId();
            }
        }

        if(count($newAddressIdList)){
            if ($shipping && !$shipping->getSameAsBilling()
                && (!$shipping->getCustomerId() || $shipping->getSaveInAddressBook())
            ) {
                $quote->getShippingAddress()->setCustomerAddressId($newAddressIdList[0]);
            }

            if (!$billing->getCustomerId() || $billing->getSaveInAddressBook()) {
                $quote->getShippingAddress()->setCustomerAddressId(end($newAddressIdList));
            }
        }


        //

        if (!$quote->getBillingAddress()->getId() && $customer->getDefaultBilling()) {
            $quote->getBillingAddress()->importCustomerAddressData(
                $this->customerAddressRepository->getById($customer->getDefaultBilling())
            );
            $quote->getBillingAddress()->setCustomerAddressId($customer->getDefaultBilling());
        }
        if (!$quote->getShippingAddress()->getSameAsBilling()
            && !$quote->getBillingAddress()->getId()
            && $customer->getDefaultShipping()
        ) {
            $quote->getShippingAddress()->importCustomerAddressData(
                $this->customerAddressRepository->getById($customer->getDefaultShipping())
            );
            $quote->getShippingAddress()->setCustomerAddressId($customer->getDefaultShipping());
        }
    }
}
