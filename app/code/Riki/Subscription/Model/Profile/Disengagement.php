<?php
namespace Riki\Subscription\Model\Profile;

use Magento\Framework\Exception\LocalizedException;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;

/**
 * Class Disengagement
 * @package Riki\Subscription\Model\Profile
 */
class Disengagement extends \Magento\Framework\Model\AbstractModel
{
    const CONSUMER_DB_SUBSCRIPTION_KEY_SUBSCRIPTION_STATUS = 'SUBSCRIPTION_STATUS';
    const CONSUMER_DB_SUBSCRIPTION_KEY_HANPUKAI_STATUS = 'HANPUKAI_STATUS';

     /**
      * @var \Riki\Subscription\Api\ProfileRepositoryInterface
      */
    protected $profileRepository;

    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
     */
    protected $courseRepository;

    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    protected $customerRepository;

    /**
     * Disengagement constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
     * @param \Riki\Customer\Model\CustomerRepository $customerRepository
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository,
        \Riki\Customer\Model\CustomerRepository $customerRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->profileRepository = $profileRepository;
        $this->courseRepository = $courseRepository;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @param $profileId
     * @return bool|\Riki\Subscription\Api\Data\ApiProfileInterface
     */
    public function getProfile($profileId)
    {
        try {
            return $this->profileRepository->get($profileId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * @param $courseId
     * @return bool|\Riki\SubscriptionCourse\Api\Data\SubscriptionCourseInterface
     */
    public function getSubscriptionCourse($courseId)
    {
        try {
            return $this->courseRepository->get($courseId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Verify a profile which can be cancel or not
     *
     * @param \Riki\Subscription\Model\Data\ApiProfile $profile
     * @return \Magento\Framework\Phrase
     */
    public function getDisengagementProfileErrorMessage(\Riki\Subscription\Model\Data\ApiProfile $profile)
    {
        if (($profile->getDisengagementDate() && $profile->getDisengagementReason()) ||
            !$profile->getStatus()
        ) {
            return __('This profile :%1 already disengaged', $profile->getProfileId());
        } else {
            $subscriptionCourse = $this->getSubscriptionCourse($profile->getCourseId());
            if ($subscriptionCourse) {
                if (strtolower($subscriptionCourse->getSubscriptionType()) == strtolower(CourseType::TYPE_HANPUKAI)) {
                    return __('You can not disengage HANPUKAI profile');
                } else {
                    if (!$subscriptionCourse->getData('is_allow_cancel_from_frontend')) {
                        return __('You can not cancel this profile');
                    } else {
                        $profileOrderTime = (int) $profile->getOrderTimes();
                        $courseMinimumOrderTime = (int) $subscriptionCourse->getData('minimum_order_times');
                        if ($profileOrderTime < $courseMinimumOrderTime) {
                            return __('The number of orders for the selected regular flight is less than the specified number');
                        }
                    }
                }
            } else {
                return __('This course :%1 does not exist', $profile->getCourseName());
            }
        }
    }

    /**
     * Call API to send customer profiles to KSS
     * @param array $activeProfileIds
     * @param $consumerDbId
     * @throws LocalizedException
     */
    public function updateSubscriptionStatusToConsumerDb(array $activeProfileIds, $consumerDbId)
    {
        if (!$consumerDbId) {
            throw new LocalizedException(__('ConsumerDbId does not exist'));
        }
        $subscriptionStatusCode = $this->customerRepository->getSubscriptionKeyCode(
            self::CONSUMER_DB_SUBSCRIPTION_KEY_SUBSCRIPTION_STATUS
        );
        $hanpukaiStatusCode = $this->customerRepository->getSubscriptionKeyCode(
            self::CONSUMER_DB_SUBSCRIPTION_KEY_HANPUKAI_STATUS
        );
        if (!$subscriptionStatusCode || !$hanpukaiStatusCode) {
            throw new LocalizedException(__('Subscription status code is not found'));
        }
        if (!$activeProfileIds[CourseType::TYPE_SUBSCRIPTION]) {
            $this->customerRepository->setCustomerSubAPI($consumerDbId, [$subscriptionStatusCode => 0]);
        }
        if (!$activeProfileIds[CourseType::TYPE_HANPUKAI]) {
            $this->customerRepository->setCustomerSubAPI($consumerDbId, [$hanpukaiStatusCode => 0]);
        }
    }
}
