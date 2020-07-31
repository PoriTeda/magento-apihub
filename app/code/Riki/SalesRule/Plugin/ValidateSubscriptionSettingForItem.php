<?php

namespace Riki\SalesRule\Plugin;

use Riki\SalesRule\Helper\Rule;

class ValidateSubscriptionSettingForItem
{
    /**
     * @var array
     */
    protected $courses = [];

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * CheckSubscriptionSettings constructor.
     *
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     */
    public function __construct(\Riki\SubscriptionCourse\Model\CourseFactory $courseFactory)
    {
        $this->courseFactory = $courseFactory;
    }

    /**
     * Check quote item match subscription settings.
     * Cart price rule has 2 validation: conditions and actions. This plugin hooks in to rule's actions.
     *
     * @param \Magento\SalesRule\Model\Rule\Condition\Product\Combine $subject
     * @param \Closure $proceed
     * @param $item
     *
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundValidate($subject, \Closure $proceed, $item)
    {
        if (!$item->getSkipValidateSubscriptionSettingFlag()
            && $subject->getPrefix() == 'actions'
            && $subject->getId() == 1
        ) {
            $rule = $subject->getRule();
            $quote = $item->getQuote();

            $courseId = $quote->getData('riki_course_id');
            if ($courseId) {
                if ($item->getData('is_spot') && Rule::SUBSCRIPTION_SPOT_BOTH != $rule->getSubscription()) {
                    return false;
                }
            }
        }

        return $proceed($item);
    }

    /**
     * Get course by id
     *
     * @param $courseId
     * @return mixed
     */
    public function _getCourseById($courseId)
    {
        if (!isset($this->_courses[$courseId])) {
            $this->courses[$courseId] = $this->courseFactory->create()->load($courseId);
        }

        return $this->courses[$courseId];
    }
}
