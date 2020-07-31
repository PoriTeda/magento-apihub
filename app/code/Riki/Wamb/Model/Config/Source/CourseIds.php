<?php
namespace Riki\Wamb\Model\Config\Source;

class CourseIds extends \Riki\Framework\Model\Source\AbstractOption
{
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * CourseIds constructor.
     *
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     */
    public function __construct(
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
    ){
        $this->courseFactory = $courseFactory; // Riki_SubscriptionCourse should support repository
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $options = [];

        $listItems = $this->courseFactory->create()->getCollection()->addFieldToFilter('is_enable',1);

        /** @var \Wyomind\PointOfSale\Model\PointOfSale $warehouse */
        foreach ($listItems as $course) {
            $options[$course->getId()] = $course->getCourseCode() . ' - ' . $course->getCourseName();
        }

        return $options;
    }
}