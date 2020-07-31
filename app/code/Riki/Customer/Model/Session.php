<?php

namespace Riki\Customer\Model;

class Session extends \Magento\Customer\Model\Session
{
    /**
     * Fix default bug causes cart become empty.
     *
     * @return $this|\Magento\Customer\Model\Session
     */
    public function regenerateId()
    {
        if (headers_sent()) { // @codingStandardsIgnoreLine
            return $this;
        }
        if ($this->isSessionExists()) {
            session_regenerate_id(false); // @codingStandardsIgnoreLine
        } else {
            session_start(); // @codingStandardsIgnoreLine
        }
        $this->storage->init(isset($_SESSION) ? $_SESSION : []); // @codingStandardsIgnoreLine

        if ($this->sessionConfig->getUseCookies()) {
            $this->clearSubDomainSessionCookie();
        }

        $this->_cleanHosts();

        return $this;
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return $this
     */
    public function setCustomerDataAsLoggedIn($customer)
    {
        $this->_httpContext->setValue(\Magento\Customer\Model\Context::CONTEXT_AUTH, true, false);
        $this->setCustomerData($customer);

        $customerModel = $this->_customerFactory->create()->updateData($customer);

        $this->setCustomer($customerModel);

        $flagSsoLoginAction = $customer->getFlagSsoLoginAction();
        if($flagSsoLoginAction){
            $customerModel->setFlagSsoLoginAction($flagSsoLoginAction);
        }

        $this->_eventManager->dispatch('customer_login', ['customer' => $customerModel]);
        $this->_eventManager->dispatch('customer_data_object_login', ['customer' => $customer]);
        return $this;
    }
}
