<?php
namespace Riki\Customer\Plugin\Customer;


class AccountManagement extends  \Magento\Customer\Model\AccountManagement{

    /**
     * Prevent send welcome email
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $redirectUrl
     * @return bool
     */
    protected function sendEmailConfirmation(\Magento\Customer\Api\Data\CustomerInterface $customer, $redirectUrl)
    {
        return true;
    }
}