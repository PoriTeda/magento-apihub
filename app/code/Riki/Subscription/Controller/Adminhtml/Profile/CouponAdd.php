<?php

namespace Riki\Subscription\Controller\Adminhtml\Profile;

class CouponAdd extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileDataHelper;
    /**
     * @var \Riki\Subscription\Model\Simulator\CouponSimulator
     */
    protected $couponSimulator;
    /**
     * @var \Riki\Subscription\Helper\Profile\ProfileSessionHelper
     */
    protected $profileCacheHelper;
    /**
     * @var \Riki\SalesRule\Helper\CouponHelper
     */
    protected $couponHelper;

    /**
     * CouponAdd constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Riki\Subscription\Helper\Profile\Data $profileDataHelper
     * @param \Riki\Subscription\Model\Simulator\CouponSimulator $couponSimulator
     * @param \Riki\Subscription\Helper\Profile\ProfileSessionHelper $profileCacheHelper
     * @param \Riki\SalesRule\Helper\CouponHelper $couponHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\Subscription\Helper\Profile\Data $profileDataHelper,
        \Riki\Subscription\Model\Simulator\CouponSimulator $couponSimulator,
        \Riki\Subscription\Helper\Profile\ProfileSessionHelper $profileCacheHelper,
        \Riki\SalesRule\Helper\CouponHelper $couponHelper
    ) {
        parent::__construct($context);
        $this->messageManager = $context->getMessageManager();
        $this->jsonFactory = $jsonFactory;
        $this->dateTime = $dateTime;
        $this->profileDataHelper = $profileDataHelper;
        $this->couponSimulator = $couponSimulator;
        $this->profileCacheHelper = $profileCacheHelper;
        $this->couponHelper = $couponHelper;
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        $profileId = $this->getRequest()->getParam('id');

        $couponCode = $this->getRequest()->getParam('couponCode');

        $tmpProfile = $this->profileDataHelper->getTmpProfile($profileId);

        if ($tmpProfile !== false) {
            /*get exactly profileId if this profile is tmp profile*/
            $profileId = $tmpProfile->getData('linked_profile_id');
        }

        /*get profile data from cache*/
        $profileCacheData = $this->profileCacheHelper->getProfileData($profileId);

        if (!isset($profileCacheData[$profileId])) {
            return $result->setData([
                'error' => true,
                'errorMessage' => __('Something went wrong, please reload page.')
            ]);
        }

        /*profile data*/
        $profileData = $profileCacheData[$profileId];

        /*current list applied coupon - from cache, not db data*/
        $currentAppliedCoupon = $profileData['appliedCoupon'];

        if (!empty($currentAppliedCoupon) && in_array($couponCode, $currentAppliedCoupon)) {
            return $result->setData([
                'error' => true,
                'errorMessage' => __('Coupon code has already exists.')
            ]);
        }

        $couponCodeIsExist = $this->couponHelper->getCouponDataByCode($couponCode);

        if (!$couponCodeIsExist) {
            return $result->setData([
                'error' => true,
                'errorMessage' => __('Coupon code does not exists.')
            ]);
        }

        $canAppliedForProfile = $this->couponSimulator->canApplyCouponCodeForProfile($couponCode, $profileId);

        if (!$canAppliedForProfile) {
            $resultData = [
                'error' => true,
                'errorMessage' => __('Coupon code can not apply for this profile.')
            ];
            return $result->setData($resultData);
        } elseif ($canAppliedForProfile instanceof \Magento\Framework\Phrase) {
            $resultData = [
                'error' => true,
                'errorMessage' => $canAppliedForProfile
            ];
            return $result->setData($resultData);
        }

        /*get profile data again - cache has been change from canApplyCouponCodeForProfile function*/
        $profileData = $this->profileCacheHelper->getProfileDataById($profileId);

        /*flag to check this profile has changed*/
        $profileData[\Riki\Subscription\Model\Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED] = true;

        /*set lifetime again for profile cache - make sure this cache do not expire after change warehouse*/
        $profileData['lifetime_cache'] = $this->dateTime->gmtTimestamp();

        /*set profile cache again*/
        $this->profileCacheHelper->setProfileDataById($profileId, $profileData);

        $this->messageManager->addSuccess(__('The coupon code has been accepted.'));

        return $result->setData([
            'error' => false,
            'errorMessage' => ''
        ]);
    }
}
