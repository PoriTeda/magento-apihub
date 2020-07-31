<?php

namespace Riki\Subscription\Block\Frontend\Profile;

use \Riki\Subscription\Helper\Order\Simulator as HelperOrderSimulator;

class EditHanpukai extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_helperProfile;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_subCourseModel;

    protected $_timezone;

    /* @var \Riki\SubscriptionCourse\Helper\Data */
    protected $_courseHelperData;

    /* @var \Magento\Framework\Registry */
    protected $_registry = null;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $_profileHelperData;

    /* @var \Bluecom\Paygent\Model\PaygentHistory */
    protected $paygentHistory;

    /* @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /* @var \Magento\Customer\Api\CustomerRepositoryInterface */
    protected $customerRepository;

    protected $_sessionManager;
    /**
     * @var \Riki\SalesRule\Helper\CouponHelper
     */
    protected $_couponHelper;

    protected $profileCacheRepository;
    /**
     * Construct
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Customer\Model\Session $customerSession,
        \Bluecom\Paygent\Model\PaygentHistory $paygentHistory,
        \Riki\Subscription\Helper\Profile\Data $profileHelperData,
        \Magento\Framework\Registry $registry,
        \Riki\SubscriptionCourse\Helper\Data $courseHelperData,
        \Riki\SubscriptionCourse\Model\Course $subCourseModel,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Riki\SalesRule\Helper\CouponHelper $couponHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository,
        array $data = []
    ) {
        $this->customerRepository = $customerRepositoryInterface;
        $this->customerSession = $customerSession;
        $this->paygentHistory = $paygentHistory;
        $this->_profileHelperData = $profileHelperData;
        $this->_registry = $registry;
        $this->_courseHelperData = $courseHelperData;
        $this->_timezone = $context->getLocaleDate();
        $this->_subCourseModel = $subCourseModel;
        $this->_helperProfile = $helperProfile;
        $this->_sessionManager = $context->getSession();
        $this->_couponHelper = $couponHelper;
        $this->profileCacheRepository = $profileCacheRepository;
        parent::__construct($context, $data);
    }

    public function _prepareLayout()
    {
        $pageTitle = __('Payment method edit');
        $this->pageConfig->getTitle()->set(__($pageTitle));
        return parent::_prepareLayout();
    }

    /**
     * Get profile id
     *
     * @return mixed
     */
    public function getProfileId()
    {
        return $this->_registry->registry('current_subscription_profile_id');
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadProfileDataCache()
    {
        $profileId = $this->getProfileId();
        $profileCache = $this->profileCacheRepository->initProfile($profileId);
        if (is_object($profileCache)) {
            return $profileCache->getProfileData()[$profileId];
        }
        return false;

    }

    /**
     * Load profile by profile id
     *
     * @return object
     */
    public function loadCurrentProfileModel()
    {
        $profileId = $this->getProfileId();
        return $this->_profileHelperData->loadProfileModel($profileId);
    }

    public function getCourseSetting()
    {
        $profileModel  = $this->loadCurrentProfileModel();
        $courseId = $profileModel->getData("course_id");
        $objCourse = $this->_courseHelperData->loadCourse($courseId);

        if (empty($objCourse) || !$objCourse->getId()) {
            return [];
        }

        return $objCourse->getSettings();
    }

    /**
     * Is shosha customer
     *
     * @return bool
     */
    public function isShoshaCustomer()
    {
        $customerId = $this->customerSession->getId();
        $customer = $this->customerRepository->getById($customerId);
        $isShosha = false;
        if ($customer->getCustomAttribute('shosha_business_code')
            && $customer->getCustomAttribute('shosha_business_code')->getValue()) {
            $isShosha = true;
        }
        return $isShosha;
    }

    /**
     * Get List Payment Method
     *
     * @return mixed
     */
    public function getListPaymentMethod()
    {
        $obj = $this->loadCurrentProfileModel();
        return $obj->getListPaymentMethodAvailable();
    }

    /**
     * Format Currency
     *
     * @param $price
     * @param null $websiteId
     *
     * @return mixed
     */
    public function formatCurrency($price, $websiteId = null)
    {
        return $this->_storeManager->getWebsite($websiteId)->getBaseCurrency()->format($price);
    }

    /**
     * Get last used date of credit card
     *
     * @return bool | string
     */
    public function getCcLastUsedDate($customerId)
    {
        $profileId = $this->getRequest()->getParams('id');
        $collection = $this->paygentHistory->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('profile_id', $profileId)
            ->addFieldToFilter('type', ['in' => ['profile_update', 'authorize']])
            ->setOrder('id', 'desc')
            ->setPageSize(1);
        if (!$collection->getSize()) {
            return false;
        }
        return $collection->getFirstItem()->getUsedDate();
    }

    public function getSystemConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->_scopeConfig->getValue($path, $storeScope);
        return $config;
    }


    /**
     * Get list rule coupon
     *
     * @param $orderSimulator
     * @return array|bool
     */
    public function getListRulIdsApplied($orderSimulator)
    {
        if (!$orderSimulator) {
            return false;
        }

        $ruleId = $orderSimulator->getAppliedRuleIds();
        $listCouponCode = $orderSimulator->getCouponCode();
        $dataCoupon = $this->_couponHelper->checkCouponRealIdsWhenProcessSimulator($ruleId, $listCouponCode);
        if (is_array($dataCoupon) && count($dataCoupon)>0) {
            return $dataCoupon;
        }
        return false;
    }

    /**
     * Build html list coupon
     *
     * @param $profileModel
     * @return string
     */
    public function getHtmlListCouponApplied($profileModel)
    {
        $html = [];
        $stringCoupon = $profileModel->getCouponCode();
        $profileId = $profileModel->getProfileId();
        if ($stringCoupon!=null) {
            $listCouponApplied = explode(',', $stringCoupon);
            if (is_array($listCouponApplied) && count($listCouponApplied) > 0) {
                foreach ($listCouponApplied as $couponCode) {
                    if ($couponCode != '') {
                        $html[] = '
                            <div class="applied-coupon">
                                <div class="title">' . __('Coupon use') . '</div>
                                <div class="applied-coupon-item">
                                    <input name="data_coupon_code[]" type="hidden" class="amCouponsCode" value="' . $couponCode . '" />
                                    <span>' . $couponCode . '</span>
                                    <a data-profile-id="'.trim($profileId).'" data-coupon-code="'.trim($couponCode).'" class="delete-coupon" data-bind="click: function() {deleteCouponCode(\''.trim($profileId).'\', \''.trim($couponCode).'\')}" href="javascript:;">' . __('Cancel Coupon') . '</a>
                                </div>
                            </div>                
                        ';
                    }
                }
            }
        }

        return implode('', $html);
    }
}
