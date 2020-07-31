<?php
namespace Riki\Subscription\Model\Config\Source\Course;

/**
 * Class Options
 * @package Riki\Subscription\Model\Config\Course
 */
class Options extends \Riki\Framework\Model\Source\AbstractOption
{
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;
    /**
     * CourseOptions constructor.
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     */
    public function __construct(
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
    ) {

        $this->courseFactory = $courseFactory;
    }

    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $subCourseModel = $this->courseFactory->create()->getCollection();
        $subCourseModel->addFieldToSelect(['course_id','course_code','course_name']);
        $subCourseModel->addFieldToFilter('course_code', ['notnull'=>true]);
        $subCourseModel->addFieldToFilter('course_code', ['neq'=>'']);
        $data = [];
        foreach ($subCourseModel as $course) {
            $data[] = [
                'label' => $course->getData('course_id'). " - ".$course->getData('course_name'),
                'value' => $course->getData('course_id')
            ];
        }
        /* your Attribute options list*/
        $this->_options = $data;
        return $this->_options;
    }
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptionsRule()
    {
        $subCourseModel = $this->courseFactory->create()->getCollection();
        $subCourseModel->addFieldToSelect(['course_id','course_code','course_name']);
        $subCourseModel->addFieldToFilter('course_code', ['notnull'=>true]);
        $subCourseModel->addFieldToFilter('course_code', ['neq'=>'']);
        $data = [];
        foreach ($subCourseModel as $course) {
            $data[] = [
                'label' => $course->getData('course_code'). " - ".$course->getData('course_name'),
                'value' => $course->getData('course_id')
            ];
        }
        /* your Attribute options list*/
        $this->_options = $data;
        return $this->_options;
    }
    public function prepare()
    {
        $subCourseModel = $this->courseFactory->create()->getCollection();
        $subCourseModel->addFieldToSelect(['course_id','course_code','course_name']);
        $subCourseModel->addFieldToFilter('course_code', ['notnull'=>true]);
        $subCourseModel->addFieldToFilter('course_code', ['neq'=>'']);
        $data = [];
        foreach ($subCourseModel as $course) {
            $data[$course->getData('course_id')] = $course->getData('course_code');
        }
        /* your Attribute options list*/
        return $data;
    }
}