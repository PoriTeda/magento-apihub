<?php

namespace Riki\Subscription\CustomerData;

use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;

/**
 * Class Profiles
 * @package Riki\Subscription\CustomerData
 */
class Profiles implements \Magento\Customer\CustomerData\SectionSourceInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Riki\Subscription\Model\Profile\Profile
     */
    protected $profileModel;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $localeFormat;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * CustomerProfiles constructor.
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Subscription\Model\Profile\Profile $profileModel,
        \Riki\Subscription\Helper\Profile\Data $profileHelper,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->customerSession = $customerSession;
        $this->profileModel = $profileModel;
        $this->profileHelper = $profileHelper;
        $this->localeFormat = $localeFormat;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getSectionData()
    {
        $result = [];
        if ($this->customerSession->isLoggedIn()) {
            $customerId = $this->customerSession->getCustomerId();
            $profiles = $this->profileModel->getCustomerSubscriptionProfileExcludeHanpukai($customerId);
            if ($profiles->getSize()) {
                foreach ($profiles as $key => $profile) {
                    // If profile is monthly, don't show it in popup when add spot product in product detail page FO.
                    if ($profile->getData('subscription_type') == SubscriptionType::TYPE_MONTHLY_FEE) {
                        $profiles->removeItemByKey($profile->getId());
                        continue;
                    }
                    $deliveryType = $this->profileHelper
                        ->getCustomerAddressType($profile->getData('shipping_address_id'));
                    $deliveryTypeName = $this->getFirstDeliveryName($profile->getId());

                    $profile->setData('delivery_type', $deliveryType);
                    $profile->setData('delivery_type_name', __($deliveryTypeName));
                    $profile->setData('next_delivery_date_format',
                        $this->profileHelper->formatDate($profile->getNextDeliveryDate())
                    );
                }
            }
            $result['profiles'] = $profiles->toArray();
        }
        $result['price_format'] = $this->localeFormat->getPriceFormat(null, 'JPY');
        $result['confirm_url'] = $this->urlBuilder->getUrl('subscriptions/profile/confirmspotproduct');
        $result['no_scription'] = 'no_scription';
        return $result;
    }
    /**
     * Get first delivery type for profile
     *
     * @param int $profileId
     *
     * @return string
     */
    public function getFirstDeliveryName($profileId)
    {
        $firstName = '';
        $deliveryTypeName = $this->profileHelper
            ->getDeliveryTypeOfProfile($profileId);
        $names = explode(',', $deliveryTypeName);
        if ($names && count($names)) {
            $firstName = $names[0];
        }
        return $firstName;
    }
}

