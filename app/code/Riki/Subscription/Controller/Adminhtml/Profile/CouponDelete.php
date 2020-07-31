<?php

namespace Riki\Subscription\Controller\Adminhtml\Profile;

class CouponDelete extends \Magento\Framework\App\Action\Action
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
     * @var \Riki\Subscription\Helper\Profile\ProfileSessionHelper
     */
    protected $profileCacheHelper;

    /**
     * CouponDelete constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Riki\Subscription\Helper\Profile\Data $profileDataHelper
     * @param \Riki\Subscription\Helper\Profile\ProfileSessionHelper $profileCacheHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\Subscription\Helper\Profile\Data $profileDataHelper,
        \Riki\Subscription\Helper\Profile\ProfileSessionHelper $profileCacheHelper
    ) {
        parent::__construct($context);
        $this->messageManager = $context->getMessageManager();
        $this->jsonFactory = $jsonFactory;
        $this->dateTime = $dateTime;
        $this->profileDataHelper = $profileDataHelper;
        $this->profileCacheHelper = $profileCacheHelper;
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

        /*get profile data from session*/
        $profileCacheData = $this->profileCacheHelper->getProfileData($profileId);

        if (empty($profileCacheData) || !isset($profileCacheData[$profileId])) {
            return $result->setData([
                'error' => true,
                'errorMessage' => __('Profile is not exists.')
            ]);
        }

        $profileData = $profileCacheData[$profileId];

        $currentAppliedCoupon = $profileData['appliedCoupon'];

        if (empty($currentAppliedCoupon) && !in_array($couponCode, $currentAppliedCoupon)) {
            return $result->setData([
                'error' => true,
                'errorMessage' => __('Coupon code is not exists.')
            ]);
        }

        $currentAppliedCoupon = array_diff($currentAppliedCoupon, [$couponCode]);

        /*set list applied coupon for profile again*/
        $profileData['appliedCoupon'] = $currentAppliedCoupon;

        $profileData['coupon_code'] = implode(',', $currentAppliedCoupon);

        /*flag to check this profile has changed*/
        $profileData[\Riki\Subscription\Model\Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED] = true;

        /*set lifetime again for profile session - make sure this session do not expire after change warehouse*/
        $profileData['lifetime_cache'] = $this->dateTime->gmtTimestamp();

        $this->profileCacheHelper->setProfileData($profileData);

        $this->messageManager->addSuccess(__('The coupon code has been removed.'));

        return $result->setData([
            'error' => false,
            'errorMessage' => ''
        ]);
    }
}
