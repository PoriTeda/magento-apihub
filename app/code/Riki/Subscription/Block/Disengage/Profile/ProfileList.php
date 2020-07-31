<?php

namespace Riki\Subscription\Block\Disengage\Profile;

use \Riki\Subscription\Block\Html\Pager as BlockHtmlPager;

/**
 * Class ProfileList
 * @package Riki\Subscription\Block\Disengage\Profile
 */
class ProfileList extends AbstractDisengagement
{
    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * Prepare layout
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getSubscriptionProfiles()) {
            $pager = $this->getLayout()->createBlock(
                BlockHtmlPager::class,
                'disengage.subscription.profile.list.pager'
            )->setCollection(
                $this->getSubscriptionProfiles()
            );
            $this->setChild('pager', $pager);
            $this->getSubscriptionProfiles()->load();
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getNextThreeDeliveries()
    {
        $result = [];
        $profileIds = [];
        $profileCollection = $this->getSubscriptionProfiles();
        if ($profileCollection->getItems()) {
            foreach ($profileCollection as $item) {
                $profileIds[] = $item->getProfileId();
            }
            if ($profileIds) {
                foreach ($profileCollection as $profileItem) {
                    $threeDeliveries = $this->helperProfile->calculateNextDelivery($profileItem);
                    $result[] = $this->buildDisengagementProfile(
                        $profileItem,
                        $threeDeliveries
                    );
                }
            }
        }
        return $result;
    }

    /**
     * Check an radio button of list profile was check or not
     *
     * @param $profileId
     * @return bool
     */
    public function isElementChecked($profileId)
    {
        $selectedProfileId = $this->getProfileDisengagement();
        if ($profileId == $selectedProfileId) {
            return true;
        }
        return false;
    }

    /**
     * @param $profiles
     * @return bool
     */
    public function hasCheckedElement($profiles)
    {
        $selectedProfileId = $this->getProfileDisengagement();
        foreach ($profiles as $profile) {
            if ($profile->getProfileId() == $selectedProfileId) {
                return true;
            }
        }
        return false;
    }
}
