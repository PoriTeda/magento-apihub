<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Block\Account;

use Magento\Framework\Exception\NoSuchEntityException;


/**
 * Dashboard Customer Info
 */
class Info extends \Magento\Customer\Block\Account\Dashboard\Info
{
    const MEMBERSHIP_CIS_ID = 6;
    const MEMBERSHIP_CNC_ID = 5;
    const MEMBERSHIP_CIS_CODE = 'cis';
    const MEMBERSHIP_CNC_CODE = 'cnc';
    const MEMBERSHIP_AMBASS_ID = 3;
    const SUBCSCRIBER_X_DAY = 'mypage_subscriber_block/subscriber_block/x_day';
    const SUBCSCRIBER_Y_DAY = 'mypage_subscriber_block/subscriber_block/y_day';
    /**
     * @var string
     */
    //protected $_template = 'account/dashboard/info.phtml';
    /**
     * @var \Riki\Subscription\Model\Profile\Profile
     */
    protected $_profileModel;
    /**
     * @var \Magento\Store\Api\WebsiteRepositoryInterface
     */
    protected $_websiteRepositoryInterface;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone 
     */
    protected $_stdTimezone;
    /**
     * @var
     */
    protected $_dateTime;
    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Info constructor.
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @param \Riki\Sales\Helper\Data $helper
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Customer\Helper\View $helperView
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepositoryInterface
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Riki\Subscription\Model\Profile\Profile $profile,
        \Riki\Sales\Helper\Data $helper,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Customer\Helper\View $helperView,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepositoryInterface,
        \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        parent::__construct($context,$currentCustomer,$subscriberFactory,$helperView, $data);
        $this->_profileModel = $profile;
        $this->_websiteRepositoryInterface = $websiteRepositoryInterface;
        $this->scopeConfig = $context->getScopeConfig();
        $this->_stdTimezone = $stdTimezone;
        $this->_dateTime = $dateTime;
        $this->functionCache = $functionCache;
        $this->customerSession = $customerSession;
    }

    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('My Account'));
        return parent::_prepareLayout();
    }


    /**
     * Check Subcription Current User
     * @return bool
     */
    public function customerHaveSubscriptionNotHanpukaiAvailable()
    {
        if ($this->getCustomer() instanceof \Magento\Customer\Api\Data\CustomerInterface) {
            $customerId = $this->getCustomer()->getId();
            if (!($customerId)) {
                return false;
            }
            $customerSub = $this->_profileModel->getCustomerSubscriptionProfileExcludeHanpukaiIds($customerId);
            if (count($customerSub)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $customerId
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection|mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCustomerSubscriptions($customerId)
    {
        $cacheKeyProfile  = $customerId.'_mypage_profile';

        if($this->functionCache->has($cacheKeyProfile))
        {
            return $this->functionCache->load($cacheKeyProfile) ;
        }

        $customerSub = $this->_profileModel->getCustomerSubscriptionProfileIds($customerId);
        $this->functionCache->store($customerSub,$cacheKeyProfile);
        return $customerSub;
    }

    /**
     * get exist profile of customer login
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function customerHaveSubscription()
    {
        if ($this->getCustomer() instanceof \Magento\Customer\Api\Data\CustomerInterface) {
            $customerId = $this->getCustomer()->getId();
            if (!($customerId)) {
                return false;
            }
            $customerSub = $this->getCustomerSubscriptions($customerId);
            if (count($customerSub)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function customerHaveSubscriptionHanpukaiAvailable()
    {
        if ($this->getCustomer() instanceof \Magento\Customer\Api\Data\CustomerInterface) {
            $customerId = $this->getCustomer()->getId();
            if (!($customerId)) {
                return false;
            }
            $totalSubscriptionHanpukaiAvailable = $this->_profileModel->getCustomerSubscriptionProfileHanpukaiIds($customerId);
            if (count($totalSubscriptionHanpukaiAvailable)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get System Config
     *
     * @param $path
     *
     * @return mixed
     */
    public function getSystemConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->scopeConfig->getValue($path, $storeScope);
        return $config;
    }

    /**
     * @return bool
     */
    public function checkMembership($memType = null){
        $memberShip = $this->getCustomer()->getCustomAttribute('membership');
        if($memberShip){
            $memberId = explode(',', $memberShip->getValue());
            switch ($memType){
                case 'amb':
                    if (in_array(self::MEMBERSHIP_AMBASS_ID,$memberId )) {
                        return true;
                    }
                    break;
                case 'cc':
                    if (in_array(self::MEMBERSHIP_CIS_ID,$memberId)||in_array(self::MEMBERSHIP_CNC_ID,$memberId)) {
                        return true;
                    }
                break;
                default:
                    return false;
            }

        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getWebsiteList(){
        try {
            $websiteId = $this->_storeManager->getStore()->getWebsiteId();
            $website = $this->_websiteRepositoryInterface->getById($websiteId);
            if ($website->getCode() == self::MEMBERSHIP_CIS_CODE || $website->getCode() == self::MEMBERSHIP_CNC_CODE) {
                return true;
            } else {
                return false;
            }
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Get current url
     */
    public function getCurrentAccountUrl()
    {
        return $this->_urlBuilder->escape($this->_urlBuilder->getCurrentUrl());
    }

    /**
     * Check ambassador
     *
     * @return bool
     */
    public function isAmbassador()
    {
        $customerObj = $this->getCustomer();
        if ($customerObj instanceof \Magento\Customer\Api\Data\CustomerInterface) {
            $customerId = $customerObj->getId();
            if ($customerId) {
                if ($customerObj->getCustomAttribute('amb_type')) {
                    if ((bool)$customerObj->getCustomAttribute('amb_type')->getValue()) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function getConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue($path, $storeScope);
    }

    /**
     * get Company Name
     *
     * @return null|string
     */
    public function getCompanyName()
    {
        $companyName = '' ;
        $customerObj = $this->getCustomer();
        if ($customerObj instanceof \Magento\Customer\Api\Data\CustomerInterface) {
            $customerAddress = $customerObj->getAddresses();
            if ($customerAddress){
                foreach ($customerAddress as $address){
                    if($address->getCustomAttribute('riki_type_address')
                        && $address->getCustomAttribute('riki_type_address')->getValue() == \Riki\Customer\Model\Address\AddressType::HOME
                        && $address->getCompany())
                    {
                        $companyName =  $address->getCompany();
                        return $companyName;
                    }
                }

            }
        }
        return $companyName;
    }

    /**
     * get sting block for my account page
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function  getSubscriberBlock(){
        $currentCustomer = $this->getCustomer();
        $currentCustomerId = $currentCustomer->getId();
        $groupId = $currentCustomer->getGroupId();

        //get id block for  member type
        switch ($groupId){
            case 1:
                return 'mypage_topright_for_normal';
                break;
            case 2:
                if($this->checkDateSubscription($currentCustomerId, (int)$this->getConfig(self::SUBCSCRIBER_X_DAY))){
                    return 'mypage_topright_for_subscriber_xxx';
                } else {
                    return 'mypage_topright_for_subscriber';
                }
                break;
            case 3:
                if($this->checkDateSubscription($currentCustomerId, (int)$this->getConfig(self::SUBCSCRIBER_Y_DAY))){
                    return 'mypage_topright_for_clubmember_yyy';
                } else {
                    return 'mypage_topright_for_clubmember';
                }
                break;
            default:
                return 'mypage_topright_for_normal';
        }
    }

    /**
     * @param $currentCustomerId
     * @param $dateConfig
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function checkDateSubscription($currentCustomerId, $dateConfig){
        $dateTimeNow = $this->_stdTimezone->date();
        $createdDate  =  null;

        // get profile oldest from customer
        $profileLast = $this->getCustomerSubscriptions($currentCustomerId);
        if(count($profileLast)){
            if($profileCreatedDay = $profileLast->getFirstItem()->getCreatedDate()) {
                $createdDate = \DateTime::createFromFormat('Y-m-d H:i:s', $profileCreatedDay);
            }
        }

        if($createdDate && $dateConfig && $createdDate->diff($dateTimeNow)->days >= $dateConfig){
            return true;
        }
        return false;
    }
}
