<?php

namespace Riki\SalesRule\Ui\Component\Form\Frequency;

use Magento\Framework\Data\OptionSourceInterface;

class Options implements OptionSourceInterface
{
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    private $course;

    public function __construct(\Riki\SubscriptionCourse\Model\Course $course)
    {
        $this->course = $course;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     * @since 100.1.0
     */
    public function toOptionArray()
    {
        return $this->course->getFrequencyValuesForForm();
    }
}