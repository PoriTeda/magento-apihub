<?php

namespace Riki\SubscriptionPage\Block\Html;

use Magento\Framework\View\Element\Template;
use Riki\SubscriptionCourse\Model\Course;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;

class Title extends \Magento\Theme\Block\Html\Title
{
    /* @var \Riki\SubscriptionCourse\Model\Course */
    protected $modelCourse;

    /* @var \Magento\Framework\Registry */
    protected $_registry;

    public function __construct(
        \Magento\Framework\Registry $registry,
        Course $course,
        Template\Context $context,
        array $data = []
    ){
        $this->_registry = $registry;
        $this->modelCourse = $course;
        parent::__construct($context, $data);
    }

    public function setPageTitle($title)
    {
        $course = $this->_registry->registry('subscription-course');
        if ($course) {
            $this->pageTitle = $course->getData('course_name');
        } else {
            $this->pageTitle = $title;
        }
    }

    public function getFrequency($subModel)
    {
        /* @var $subModel \Riki\SubscriptionCourse\Model\Course */
        $result = array();
        $selectedFrequency = $subModel->getData('frequency_ids');
        $allFrequency = $subModel->getFrequencyForHanpukai();
        if (is_array($selectedFrequency) && count($selectedFrequency) > 0 && array_key_exists($selectedFrequency[0], $allFrequency) ) {
            return $allFrequency[$selectedFrequency[0]];
        }
        return false;
    }
}