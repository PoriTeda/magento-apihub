<?php

namespace Riki\Catalog\Plugin;

class SetCustomerData
{
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->_customerSession = $customerSession;
        $this->_coreRegistry = $coreRegistry;
    }

    public function beforeExecute(\Magento\Catalog\Controller\Product\View $subject)
    {
        $customerId = $this->_customerSession->getCustomerId();
        $this->_coreRegistry->register('customer_id', $customerId);
    }
}
