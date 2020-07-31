<?php

namespace Riki\SubscriptionProfileDisengagement\Helper;

use Magento\Framework\App\Helper\Context;
use Riki\SubscriptionProfileDisengagement\Model\Reason;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** @var \Riki\Subscription\Helper\Profile\ProfileSessionHelper  */
    protected $profileSessionHelper;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileDataHelper;
    /**
     * @var
     */
    protected $isDisengage;

    /**
     * Data constructor.
     *
     * @param \Riki\Subscription\Helper\Profile\ProfileSessionHelper $profileSessionHelper
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfileData
     * @param Context $context
     */
    public function __construct(
        \Riki\Subscription\Helper\Profile\ProfileSessionHelper $profileSessionHelper,
        \Riki\Subscription\Helper\Profile\Data $helperProfileData,
        Context $context
    )
    {
        $this->profileSessionHelper = $profileSessionHelper;
        $this->profileDataHelper = $helperProfileData;

        parent::__construct($context);
    }

    /**
     * @param $profileId
     * @return bool
     */
    public function isPendingToDisengage($profileId)
    {
        if (isset($this->isDisengage) and $this->isDisengage !== null) {
            return $this->isDisengage;
        }
        if($profileData = $this->profileDataHelper->loadProfileModel($profileId)){
            if(
                $profileData['disengagement_date']
                && $profileData['disengagement_reason']
                && $profileData['disengagement_user']
                && $profileData['status']
            ){
                $this->isDisengage = true;
            }
        } else {
            $profile = $this->profileDataHelper->loadProfileModel($profileId);

            if (
                $profile->getDisengagementDate() &&
                $profile->getDisengagementReason() &&
                $profile->getDisengagementUser() &&
                $profile->getStatus()
            ) {
                $this->isDisengage = true;
            }
        }

        $this->isDisengage = false;
        return $this->isDisengage;
    }

    /**
     * Check profile is disengaged
     *
     * @param $profileId
     * @return bool
     */
    public function isDisengageMode($profileId)
    {
        try {
            $profileModel = $this->profileDataHelper->loadProfileModel($profileId);
            return $profileModel->isWaitingToDisengaged() || !$profileModel->getStatus();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            $this->_logger->info(__('No such entity with subscription proifle = %1', $profileId));
            return false;
        }
    }

    /**
     * Get visibility options
     * @return array
     */
    public function getVisibilityOptions()
    {
        return [
            Reason::VISIBILITY_BACKEND => Reason::VISIBILITY_BACKEND_TITLE,
            Reason::VISIBILITY_FRONTEND => Reason::VISIBILITY_FRONTEND_TITLE,
            Reason::VISIBILITY_BOTH => Reason::VISIBILITY_BOTH_TITLE
        ];
    }
}
