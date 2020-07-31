<?php
namespace Riki\SubscriptionCourse\Model\Course\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class DurationUnit
 */
class NextDeliveryDateCalculationOption implements OptionSourceInterface
{
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $course;

    /**
     * Constructor
     *
     * @param \Riki\SubscriptionCourse\Model\Course $course
     */
    public function __construct(\Riki\SubscriptionCourse\Model\Course $course)
    {
        $this->course = $course;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = $this->course->getNextDeliveryDateCalculationOption();
        $options = [];
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
