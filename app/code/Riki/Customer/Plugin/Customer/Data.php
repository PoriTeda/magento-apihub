<?php

namespace Riki\Customer\Plugin\Customer;

class Data extends \Magento\Customer\CustomerData\Customer
{
    /**
     * {@inheritdoc}
     */
    public function afterGetSectionData($subject, $result)
    {
        if (!$subject->currentCustomer->getCustomerId()) {
            return $result;
        }

        $customer = $subject->currentCustomer->getCustomer();

        $result['email'] = $customer->getEmail();

        // For Front End because FO can't check proxyLocale like at ViewModifyData.php file
        $result['fullname'] = $customer->getLastname() . $customer->getFirstname();

        return $result;
    }
}
