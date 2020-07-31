<?php

namespace Riki\Subscription\Plugin\Checkout\Model\DefaultConfigProvider;

class InitSubscriptionData
{
    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
     */
    protected $courseRepository;

    /**
     * InitSubscriptionData constructor.
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
     */
    public function __construct(
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
    ) {
        $this->courseRepository = $courseRepository;
    }

    /**
     * @param \Magento\Checkout\Model\DefaultConfigProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetConfig(
        \Magento\Checkout\Model\DefaultConfigProvider $subject,
        array $result
    ) {
        if (isset($result['quoteData'])) {
            $result['quoteData']['allow_choose_delivery_date'] =
                (bool)$result['quoteData']['allow_choose_delivery_date'];

            if (isset($result['quoteData']['riki_course_id'])
                && $courseId = $result['quoteData']['riki_course_id']
            ) {
                try {
                    $course = $this->courseRepository->get($courseId);
                } catch (\Exception $e) {
                    return $result;
                }

                $result['quoteData']['allow_choose_delivery_date'] = (bool)$course->isAllowChooseDeliveryDate();
            }
        }

        return $result;
    }
}
