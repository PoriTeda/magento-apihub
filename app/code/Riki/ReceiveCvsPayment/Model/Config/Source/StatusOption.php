<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\ReceiveCvsPayment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IsActive
 */
class StatusOption implements OptionSourceInterface
{
    const STATUS_UNIMPORT = 0;

    const STATUS_IMPORTED = 1;

    const STATUS_IMPORTING = 2;
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {

        $options = [];
        $options[] = ['label'=>'File uploaded', 'value'=>'0'];
        $options[] = ['label'=>'Imported', 'value'=>'1'];
        return $options;
    }
    /**
     * Retrieve option array
     *
     * @return string[]
     */
    public static function getOptionArray()
    {
        return [self::STATUS_UNIMPORT => __('File uploaded'), self::STATUS_IMPORTED => __('Imported')];
    }

    /**
     * Retrieve option array with empty value
     *
     * @return string[]
     */
    public function getAllOptions()
    {
        $result = [];

        foreach (self::getOptionArray() as $index => $value) {
            $result[] = ['value' => $index, 'label' => $value];
        }

        return $result;
    }

    /**
     * Retrieve option text by option value
     *
     * @param string $optionId
     * @return string
     */
    public function getOptionText($optionId)
    {
        $options = self::getOptionArray();

        return isset($options[$optionId]) ? $options[$optionId] : null;
    }
}
