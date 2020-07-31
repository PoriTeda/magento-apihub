<?php

namespace Riki\Checkout\Model;

class HanpukaiMaxOrderTimeConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    private $subscriptionCourseHelper;

    /**
     * HanpukaiMaxOrderTimeConfigProvider constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Riki\SubscriptionCourse\Helper\Data $subscriptionCourseHelper
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riki\SubscriptionCourse\Helper\Data $subscriptionCourseHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->subscriptionCourseHelper = $subscriptionCourseHelper;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return ['maxOrderTime' => $this->getMaxOrderTime()];
    }

    /**
     * @return int
     */
    private function getMaxOrderTime()
    {
        $courseId = $this->checkoutSession->getQuote()->getData('riki_course_id');
        if ($courseId) {
            $course = $this->subscriptionCourseHelper->loadCourse($courseId);
            return $course->getHanpukaiMaximumOrderTimes();
        }
        return 0;
    }
}
