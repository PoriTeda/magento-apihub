<?php

namespace Riki\CustomerSegment\Observer;

class ProcessEventObserver extends \Magento\CustomerSegment\Observer\ProcessEventObserver
{
    /**
     * Match customer segments on supplied event for currently logged in customer or visitor and current website.
     * Can be used for processing just frontend events
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $this->_coreRegistry->registry('segment_customer');

        // For visitors use customer instance from customer session
        if (!$customer) {
            $customer = $this->_customerSession->getCustomer();
        }

        if($customer->getFlagSsoLoginAction() && $observer->getEvent()->getName() == 'customer_login'){
            return;
        }

        $this->_customer->processEvent(
            $observer->getEvent()->getName(),
            $customer,
            $this->_storeManager->getStore()->getWebsite()
        );
    }
}
