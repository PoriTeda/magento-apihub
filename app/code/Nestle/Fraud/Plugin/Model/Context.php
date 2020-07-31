<?php

namespace Nestle\Fraud\Plugin\Model;

class Context
{
    protected $courseFactory;

    protected $resource;

    /**
     * Context constructor.
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->courseFactory = $courseFactory;
        $this->resource = $resource;
    }

    /**
     * @param \Mirasvit\FraudCheck\Model\Context $context
     * @param $result
     * @return mixed
     */
    public function afterExtractOrderData(\Mirasvit\FraudCheck\Model\Context $context, $result)
    {
        $order = $context->order;
        if (!is_null($order)) {
            $courseId = $order->getRikiCourseId();
            //Handle logic for admin where order object does not have riki_course_id information
            if (is_null($courseId) || $courseId <= 0) {
                $profileId = $order->getData('subscription_profile_id');
                if ($profileId === null || $profileId < 0) {
                    return $result;
                }
                $collection = $this->courseFactory->create()->getCollection();
                $courseTable = $this->resource->getTableName('subscription_profile');
                $collection->getSelect()
                    ->join(array('p' => $courseTable),
                        'main_table.course_id = p.course_id')
                    ->where('p.profile_id=?', $profileId);

                foreach ($collection as $item) {
                    $courseCode = $item->getCourseCode();
                    $order->setData('subscription_course', $courseCode);
                    $result->setData('subscription_course', $courseCode);
                }

                return $result;
            }
            $course = $this->courseFactory->create()->load($courseId);
            if ($course) {
                $courseCode = $course->getCourseCode();
                $order->setData('subscription_course', $courseCode);
                $result->setData('subscription_course', $courseCode);
            }
        }

        return $result;
    }
}
