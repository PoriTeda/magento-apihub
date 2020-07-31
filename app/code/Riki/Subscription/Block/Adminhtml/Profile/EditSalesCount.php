<?php

namespace Riki\Subscription\Block\Adminhtml\Profile;

class EditSalesCount extends \Magento\Framework\View\Element\Template
{

    /* @var \Magento\Framework\Registry */
    protected $_registry;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $_helperProfile;

    public function __construct
    (
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ){
        $this->_helperProfile = $helperProfile;
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    public function getRegistryData($key)
    {
        return $this->_registry->registry($key);
    }

    public function getProfileData($profileId)
    {
        return $this->_helperProfile->loadProfileModel($profileId);
    }

}