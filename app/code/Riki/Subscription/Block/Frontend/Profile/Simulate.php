<?php

namespace Riki\Subscription\Block\Frontend\Profile;

class Simulate extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_helperProfile;
    /**
     * @var \Magento\Framework\Registry|null
     */
    protected $_coreRegistry = null;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->_helperProfile = $helperProfile;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }


    public function getProfileInfo()
    {
        return $this->_coreRegistry->registry('riki_subscription_profile_simulate');
    }

    public function getBackUrl()
    {
        $profileInfo = $this->getProfileInfo();        
        return $this->getUrl('subscriptions/profile/view').'id/'.$profileInfo->getData('profile_id');
    }
}
