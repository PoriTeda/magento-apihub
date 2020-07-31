<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SubscriptionCourse\Model\Course\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IsActive
 */
class Website implements OptionSourceInterface
{
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_course;

    /**
     * Constructor
     *
     * @param \Riki\SubscriptionCourse\Model\Course $course
     */
    public function __construct(\Riki\SubscriptionCourse\Model\Course $course)
    {
        $this->_course = $course;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = $this->_course->getAvailableWebsites();
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