<?php
namespace Riki\Subscription\Helper\Profile;

use Magento\Framework\Data\Argument\Interpreter\DataObject;

class ProfileSessionHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Riki\Subscription\Model\ProfileCacheRepository
     */
    protected $profileCache;
    /**
     * @var \Riki\SalesRule\Helper\CouponHelper
     */
    protected $couponHelper;

    /**
     * ProfileSessionHelper constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Riki\SalesRule\Helper\CouponHelper $couponHelper
     * @param \Riki\Subscription\Model\ProfileCacheRepository $profileCache
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\SalesRule\Helper\CouponHelper $couponHelper,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCache
    ) {
        parent::__construct($context);
        $this->couponHelper = $couponHelper;
        $this->profileCache = $profileCache;
    }

    /**
     * Set session profile data
     *
     * @param $profileId
     * @param $profileData
     */
    public function setProfileDataById($profileId, $profileData)
    {
        $profileCacheData = $this->profileCache->getCache($profileId);
        if (!is_object($profileCacheData) && empty($profileCacheData->getProfileData()) || isset($profileCacheData->getProfileData()[$profileId])) {
            return;
        }

        //$profileDataCache[$profileId] = $profileData;

        $this->setProfileData($profileData);
    }

    /**
     * Get profile data from session
     * @param $profileId
     * @return bool| DataObject
     */
    public function getProfileDataById($profileId)
    {
        /*get all profile data from session*/
        $profileDataSession = $this->getProfileData($profileId);

        if (empty($profileDataSession) || !isset($profileDataSession[$profileId])) {
            return false;
        }

        return $profileDataSession[$profileId];
    }

    /**
     * @param $profileData
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setProfileData($profileData)
    {
        $this->profileCache->save($profileData);
    }

    /**
     * Get profile data from session
     *
     * @return [dataObject]
     */
    public function getProfileData($profileId)
    {
        $cacheData = $this->profileCache->getCache($profileId);
        if (is_object($cacheData)) {
            return $cacheData->getProfileData();
        }
        return false;
    }

    /**
     * set profile data again (for session) by simulate data
     *   - used to set coupon data again
     *
     * @param $profileId
     * @param $simulateOrderByProfile
     */
    public function generateProfileDataBySimulateData($profileId, $simulateOrderByProfile)
    {
        $profileData = $this->profileCache->getProfileDataCache($profileId);

        if (!$profileData) {
            return;
        }

        /*set current list coupon again for profile - session*/
        $currentAppliedCoupon = $this->getAppliedCouponCodeFromSimulateObject($simulateOrderByProfile);
        $profileData['appliedCoupon'] = $currentAppliedCoupon;
        $profileData['coupon_code'] = implode(',', $currentAppliedCoupon);

        $this->setProfileData($profileData);
    }

    /**
     * @param $simulatorOrder
     * @return array|bool
     */
    public function getAppliedCouponCodeFromSimulateObject($simulatorOrder)
    {
        return $this->couponHelper->getRealAppliedCoupon($simulatorOrder);
    }

}