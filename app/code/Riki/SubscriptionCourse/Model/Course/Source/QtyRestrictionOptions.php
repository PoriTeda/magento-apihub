<?php

namespace Riki\SubscriptionCourse\Model\Course\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class MaximumQtyRestrictionOption
 */
class QtyRestrictionOptions implements OptionSourceInterface
{
    const OPTION_VALUE_FIRST_ORDER = 1;
    const OPTION_VALUE_SECOND_ORDER = 2;
    const OPTION_VALUE_CUSTOM_ORDER = 3;
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => self::OPTION_VALUE_FIRST_ORDER, 'label' => __('Only apply for the first order')],
            ['value' => self::OPTION_VALUE_SECOND_ORDER, 'label' => __('Only apply for the second order')],
            ['value' => self::OPTION_VALUE_CUSTOM_ORDER, 'label' => __('Custom qty for each order time')],
        ];
        return $options;
    }
}
