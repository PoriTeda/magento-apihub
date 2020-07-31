<?php

namespace Riki\Subscription\Model\Simulator;

use Riki\Subscription\Api\Simulator\CouponSimulatorInterface;
use \Magento\Framework\DataObject;
use Riki\Subscription\Helper\Order as SubOrderHelper;

class CouponSimulator implements CouponSimulatorInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $_profileRepository;
    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $_helperSimulator;
    /**
     * @var \Riki\Subscription\Helper\Profile\ProfileSessionHelper
     */
    protected $_profileSessionHelper;
    /**
     * @var \Riki\SalesRule\Helper\CouponHelper
     */
    protected $_couponHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;

    protected $cacheProfile;

    protected $_profileId;

    protected $_couponCode;

    protected $_action;

    protected $_arrData = [];

    protected $_profileData;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subOrderHelper;

    /**
     * @var array
     */
    protected $amountRestrictionErrorMessage = [];

    /**
     * CouponSimulator constructor.
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param SubOrderHelper\Simulator $helperSimulator
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Riki\Subscription\Helper\Profile\ProfileSessionHelper $profileSessionHelper
     * @param \Riki\SalesRule\Helper\CouponHelper $couponHelper
     * @param \Magento\Framework\Registry $registry
     * @param SubOrderHelper $subOrderHelper
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Riki\Subscription\Helper\Order\Simulator $helperSimulator,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\Subscription\Helper\Profile\ProfileSessionHelper $profileSessionHelper,
        \Riki\SalesRule\Helper\CouponHelper $couponHelper,
        \Magento\Framework\Registry $registry,
        SubOrderHelper $subOrderHelper
    ) {
        $this->_customerSession = $customerSession;
        $this->_profileRepository = $profileRepository;
        $this->_helperSimulator = $helperSimulator;
        $this->_dateTime = $dateTime;
        $this->_profileSessionHelper = $profileSessionHelper;
        $this->_couponHelper = $couponHelper;
        $this->_registry = $registry;
        $this->subOrderHelper = $subOrderHelper;
    }

    /**
     * @param int $profileId
     * @param string $couponCode
     * @param string $action
     * @return array|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function couponApplied($profileId, $couponCode, $action)
    {
        $this->_profileId = $profileId;
        $this->_couponCode = $couponCode;
        $this->_action = $action;

        $this->_profileData = $this->checkProfileExistOnCustomer();
        $isValid = $this->validateCode($couponCode);

        $this->_arrData = [
            'is_validate' => false,
            'message' => __('Coupon code is not valid')
        ];

        if ($this->_profileData && $couponCode != '' && $isValid) {
            $this->cacheProfile = $this->_profileSessionHelper->getProfileData($profileId);
            if (empty($this->cacheProfile) || ($this->cacheProfile and !isset($this->cacheProfile[$profileId])) ) {
                $this->setSessionProfileData($profileId);
            }

            $result = $this->processCouponCode($this->_profileId, $this->_couponCode, $this->_action);
            if ($result) {
                $message = __('You used coupon code "%1".', $couponCode);
                if ($action == 'delete') {
                    $message = __('You canceled the coupon code.');
                }
                $this->_arrData = [
                    'is_validate' => true,
                    'coupon_code' => $this->_couponCode,
                    'message' => $message
                ];
            }
            if (!empty($this->amountRestrictionErrorMessage) && !$this->_arrData['is_validate']) {
                $this->_arrData['is_valid'] = false;
                $this->_arrData['message'] = reset($this->amountRestrictionErrorMessage);
                $this->amountRestrictionErrorMessage = [];
            }
            $this->cacheProfile[$profileId]->setData('validateCoupon', $this->_arrData);
            $this->_profileSessionHelper->setProfileData($this->cacheProfile[$profileId]);
        }

        return $this->_arrData;
    }

    /**
     * Check profile exist
     *
     * @return null|\Riki\Subscription\Api\Data\ApiProfileInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function checkProfileExistOnCustomer()
    {
        $profile = $this->_profileRepository->get($this->_profileId);
        if ($profile instanceof \Riki\Subscription\Api\Data\ApiProfileInterface) {
            $profile = $this->_profileRepository->get($profile);
        }
        if (!$profile instanceof \Riki\Subscription\Model\Profile\Profile) {
            return null;
        }
        return $profile;
    }


    /**
     * Validate coupon code
     *
     * @param $couponCode
     * @return bool
     */
    public function validateCode($couponCode)
    {
        $isValid = false;
        if ($couponCode != '' && !strpos($couponCode, ',')) {
            $isValid = true;
        }
        return $isValid;

    }


    /**
     * Validate coupon code
     *
     * @param $profileId
     * @param $couponCode
     * @param $action
     * @return bool
     */
    public function processCouponCode($profileId, $couponCode, $action)
    {
        /*get coupon data*/
        $couponData = $this->_couponHelper->getCouponDataByCode($couponCode);

        if (!$couponData) {
            return false;
        }

        $simulatorOrder = $this->getSimulatorOrderOfProfile($profileId, $couponCode, $action);

        $isApplied = false;

        if ($simulatorOrder) {
            //$arrCouponCode = explode(',', $simulatorOrder->getCouponCode());

            //check coupon real applied rule
            $arrCouponCode = $this->getRealAppliedCodes($simulatorOrder);

            if (in_array($couponCode, $arrCouponCode)) {
                $this->cacheProfile[$profileId]->setData('coupon_code', $simulatorOrder->getCouponCode());
                $this->cacheProfile[$profileId]->setData('appliedCoupon', $arrCouponCode);
                $isApplied = true;
            } else if ($action == 'delete') {
                $isApplied = true;
            }
        }

        /**
         * Remove coupon validate error  or delete coupon
         */
        if (!$isApplied || $action == 'delete') {
            $this->removeCouponValidateError($profileId, $couponCode);
        }
        return $isApplied;
    }

    /**
     * Get data simulator profile
     * @param $profileId
     * @param $couponCode
     * @param $action
     * @return bool
     */
    public function getSimulatorOrderOfProfile($profileId, $couponCode, $action)
    {
        $this->cacheProfile = $this->_profileSessionHelper->getProfileData($profileId);
        if ($this->cacheProfile and isset($this->cacheProfile[$profileId])) {
            $sessionProfile = $this->cacheProfile[$profileId];
            try {
                $couponCode = $this->getCoupon($profileId, $couponCode, $action);
                $sessionProfile->setData('coupon_code', $couponCode);
                $this->_registry->register('subscription_profile', $sessionProfile);
                $this->_registry->register('subscription_profile_obj', $this->_profileData);
                $simulatorOrder = $this->_helperSimulator->createSimulatorOrderHasData($sessionProfile);
                if ($simulatorOrder instanceof \Riki\Subscription\Model\Emulator\Order) {
                    $subscriptionCourse = $this->subOrderHelper->loadCourse($sessionProfile->getData('course_id'));
                    $validateResults = $this->subOrderHelper->validateAmountRestriction(
                        $simulatorOrder,
                        $subscriptionCourse,
                        $sessionProfile
                    );
                    if (!$validateResults['status']) {
                        $couponModel = $this->_couponHelper->getCouponDataByCode($couponCode);
                        if ($couponModel) {
                            $rule = $this->_couponHelper->getRuleById($couponModel->getRuleId());
                            if ($rule) {
                                if (in_array($rule->getSimpleAction(), SubOrderHelper::AMOUNT_THRESHOLD_CART_RULE_TYPES)) {
                                    $this->amountRestrictionErrorMessage[] = $validateResults['message'];
                                    return false;
                                }
                            }
                        }
                    }
                    return $simulatorOrder;
                }
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Get Coupon code
     *
     * @return string
     */
    public function getCoupon()
    {
        $arrCoupon = explode(',', $this->cacheProfile[$this->_profileId]->getCouponCode());
        $arrCoupon = array_unique(array_filter($arrCoupon));
        if ($this->_action == 'delete' && ($key = array_search($this->_couponCode, $arrCoupon)) !== false) {
            unset($arrCoupon[$key]);
        } else {
            //add coupon
            $arrCoupon[] = trim($this->_couponCode);
        }
        return implode(array_unique(array_filter($arrCoupon)), ',');
    }

    /**
     * @param $simulatorOrder
     * @return array|bool
     */
    public function getRealAppliedCodes($simulatorOrder)
    {
        return $this->_profileSessionHelper->getAppliedCouponCodeFromSimulateObject($simulatorOrder);
    }

    /**
     * Remove coupon error
     *
     * @param $profileId
     * @param $couponCode
     */
    public function removeCouponValidateError($profileId, $couponCode)
    {
        if (empty($this->cacheProfile[$profileId]->getCouponCode())) {
            $this->cacheProfile[$profileId]->setData('appliedCoupon' , []);
            return;
        }
        $arrCoupon = explode(',', $this->cacheProfile[$profileId]->getCouponCode());
        if (in_array($couponCode, $arrCoupon) && ($key = array_search($couponCode, $arrCoupon)) !== false) {
            unset($arrCoupon[$key]);
        }
        $this->cacheProfile[$profileId]->setCouponCode(implode(',', $arrCoupon));
        $this->cacheProfile[$profileId]->setData('appliedCoupon' ,$arrCoupon);
    }

    /**
     * Get list coupon code applied
     *
     * @return array
     */
    public function getListCouponApplied()
    {
        $dataCode = [];
        if (isset($this->cacheProfile[$this->_profileId])) {
            $stringCoupon = $this->cacheProfile[$this->_profileId]->getCouponCode();
            $listCouponApplied = explode(',', $stringCoupon);

            if (is_array($listCouponApplied) && count($listCouponApplied) > 0) {
                $dataCode = $listCouponApplied;
            }
        }
        return $dataCode;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return $this->_arrData;
    }

    /**
     * Can coupon code applied for this profile
     *
     * @param $couponCode
     * @param $profileId
     * @return bool
     */
    public function canApplyCouponCodeForProfile($couponCode, $profileId)
    {
        /*get coupon data*/
        $couponData = $this->_couponHelper->getCouponDataByCode($couponCode);

        if (!$couponData) {
            return false;
        }

        /*get all profile data from session*/
        $profileDataCache = $this->_profileSessionHelper->getProfileData($profileId);

        if (!isset($profileDataCache[$profileId])) {
            return false;
        }

        /*get profile data from session*/
        $profileData = $profileDataCache[$profileId];

        /*current coupon list of this profile - from session, not db*/
        $currentAppliedCoupon = $this->getAppliedCouponListFromProfileData($profileData, $couponCode);

        /*generate profile coupon code from coupon list*/
        $profileData['coupon_code'] = implode(',', $currentAppliedCoupon);
        $this->_profileId = $profileId;
        $this->_profileData = $this->checkProfileExistOnCustomer();
        $this->_registry->register('subscription_profile', $profileData);
        $this->_registry->register('subscription_profile_obj', $this->_profileData);
        $simulateOrderByProfile = $this->_helperSimulator->createSimulatorOrderHasData($profileData, null, true);

        $subscriptionCourse = $this->subOrderHelper->loadCourse($profileData->getData('course_id'));
        $validateResults = $this->subOrderHelper->validateAmountRestriction(
            $simulateOrderByProfile,
            $subscriptionCourse,
            $profileData
        );
        if (!$validateResults['status']) {
            $rule = $this->_couponHelper->getRuleById($couponData->getRuleId());
            if ($rule != false) {
                if (in_array($rule->getSimpleAction(), SubOrderHelper::AMOUNT_THRESHOLD_CART_RULE_TYPES)) {
                    $this->_registry->unregister('subscription_profile');
                    unset($profileData['coupon_code']);
                    $this->_registry->register('subscription_profile', $profileData);
                    return $validateResults['message'];
                }
            }
        }
            /*for case cannot simulate for this profile*/
        if (!$simulateOrderByProfile) {
            return false;
        }

        $rs = true;

        /*list promotion can applied for this profile after simulator*/
        $appliedRuleIds = $simulateOrderByProfile->getAppliedRuleIds();

        /*for case do not have any rule which applied for this profile*/
        if (empty($appliedRuleIds)) {
            $rs = false;
        } else {
            $listAppliedRule = explode(',', $appliedRuleIds);

            if (!in_array($couponData->getRuleId(), $listAppliedRule)) {
                $rs = false;
            }
        }

        $this->_profileSessionHelper->generateProfileDataBySimulateData($profileId, $simulateOrderByProfile);

        return $rs;
    }

    /**
     * Get current applied coupon list from profile data (session)
     *
     * @param $profileData
     * @param bool|string $couponCode
     * @return array
     */
    public function getAppliedCouponListFromProfileData($profileData, $couponCode = false)
    {
        $currentAppliedCoupon = [];

        if (!empty($profileData['appliedCoupon'])) {
            $currentAppliedCoupon = $profileData['appliedCoupon'];
        }

        if (!empty($couponCode) && !in_array($couponCode, $currentAppliedCoupon)) {
            array_push($currentAppliedCoupon, $couponCode);
        }

        return $currentAppliedCoupon;
    }


    /**
     * Set session profile data if session does not exist
     *
     * @param $profileId
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setSessionProfileData($profileId)
    {
        if ($this->_profileData) {
            $timestamp = $this->_dateTime->gmtTimestamp();

            /** @var \Riki\Subscription\Model\Profile\Profile $objProfile */
            $objProfile = $this->_profileData;

            $obj = new DataObject();
            $obj->setData($objProfile->getData());
            $obj->setData("course_data", $objProfile->getCourseData());
            $obj->setData("product_cart", $objProfile->getProductCartData());
            $obj->setData("lifetime_cache", $timestamp);
            $this->_profileSessionHelper->setProfileData($obj);
        }

        $this->cacheProfile = $this->_profileSessionHelper->getProfileData($profileId);
    }


}
