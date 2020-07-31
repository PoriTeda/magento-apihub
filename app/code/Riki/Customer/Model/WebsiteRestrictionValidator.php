<?php

namespace Riki\Customer\Model;

class WebsiteRestrictionValidator
{
    const DEFAULT_WEBSITE_ID = 1;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * WebsiteRestrictionValidator constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function validate()
    {
        $currentWebsiteId = $this->storeManager->getStore()->getWebsiteId();
        $customer = $this->customerSession->getCustomer();

        if ($currentWebsiteId == self::DEFAULT_WEBSITE_ID) {
            return true;
        }

        if (!$customer->getId()) {
            $configRestrictedWebsiteIds = explode(',', $this->scopeConfig->getValue('sso_login_setting/restrict_website_group/restrict_website'));
            if (is_array($configRestrictedWebsiteIds) && !in_array($currentWebsiteId, $configRestrictedWebsiteIds)) {
                return true;
            }
        }

        if ($customer->getData('multiple_website')) {
            $customerAssociatedWebsiteIds = is_array($customer->getData('multiple_website'))
                ? $customer->getData('multiple_website')
                : explode(',', $customer->getData('multiple_website'));

            if (is_array($customerAssociatedWebsiteIds)) {
                return in_array($currentWebsiteId, $customerAssociatedWebsiteIds);
            }
        }

        if ($customer->getWebsiteId() == $currentWebsiteId) {
            return true;
        }

        return false;
    }
}
