<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SubscriptionCourse\Model\Course\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class AmountRestrictionOptions
 */
class AmountRestrictionOptions implements OptionSourceInterface
{
    const OPTION_VALUE_SECOND_ORDER = 0;
    const OPTION_VALUE_ALL_ORDER = 1;
    const OPTION_VALUE_CUSTOM_ORDER = 2;
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => self::OPTION_VALUE_SECOND_ORDER, 'label' => __('Only apply for the second order')],
            ['value' => self::OPTION_VALUE_ALL_ORDER, 'label' => __('Apply for all orders')],
            ['value' => self::OPTION_VALUE_CUSTOM_ORDER, 'label' => __('Custom amount for each order time')],
        ];
        return $options;
    }
}
