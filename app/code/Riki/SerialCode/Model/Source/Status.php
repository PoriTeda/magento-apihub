<?php

namespace Riki\SerialCode\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Serial code status functionality model
 */
class Status implements OptionSourceInterface
{
    /**#@+
     * Serial code status values
     */
    const STATUS_NOT_USED = 1;

    const STATUS_USED = 2;

    const STATUS_CANCELLED = 3;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            self::STATUS_NOT_USED => __('Not used'),
            self::STATUS_USED => __('Used'),
            self::STATUS_CANCELLED => __('Cancelled')
        ];
    }

    /**
     * Retrieve option text by option value
     *
     * @param string $optionId
     * @return string
     */
    public function getOptionText($optionId)
    {
        $options = self::toOptionArray();

        return isset($options[$optionId]) ? $options[$optionId] : null;
    }
}

