<?php

namespace Riki\Subscription\Block\Frontend\Profile;


class ConfirmChangeFrequency extends \Magento\Framework\View\Element\Template
{

    /* @var \Magento\Framework\Registry */
    protected $_registry;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $_profileData;

    /* @var \Riki\Subscription\Model\Frequency\Frequency */
    protected $_frequencyModel;

    protected $frequencyHelper;

    public function __construct(
        \Riki\Subscription\Model\Frequency\Frequency $frequency,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper,
        array $data = []
    ){
        $this->_frequencyModel = $frequency;
        $this->_profileData = $profileData;
        $this->_registry = $registry;
        $this->frequencyHelper = $frequencyHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get profile id
     *
     * @return mixed
     */
    public function getProfileId()
    {
        return $this->_registry->registry('subscription-profile-id');
    }

    /**
     * Get profile object model
     *
     * @return \Riki\Subscription\Model\Profile\Profile
     */
    public function getProfileModelObj()
    {
        $profileId = $this->getProfileId();
        return $this->_profileData->load($profileId);
    }

    public function getProfileFrequencyId()
    {
        return $this->_registry->registry('subscription-profile-frequency-id');
    }

    public function getTextFrequencySelected()
    {
        $frequencyModel = $this->_frequencyModel->load($this->getProfileFrequencyId());
        if(empty($frequencyModel) || $frequencyModel->getId() == null) {
           return '';
        }
        return $this->frequencyHelper->formatFrequency($frequencyModel->getData('frequency_interval'), $frequencyModel->getData('frequency_unit'));
    }

    public function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Change Frequency'));
        return parent::_prepareLayout();
    }

    public function getReferUrl($profileId)
    {
        return $this->getUrl('subscriptions/profile/changefrequency',['id' => $profileId]);
    }
}