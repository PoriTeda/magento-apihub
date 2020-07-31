<?php

namespace Riki\AdvancedInventory\Model\Config\Source\ReAssignation;

class Status implements \Magento\Framework\Option\ArrayInterface
{
    const STATUS_WAITING = 'waiting';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILURE = 'failure';

    /**
     * @return array
     */
    public function getOptions()
    {
        return [
            self::STATUS_WAITING    =>  __('Waiting'),
            self::STATUS_SUCCESS    =>  __('Success'),
            self::STATUS_FAILURE   =>  __('Failure')
        ];
    }

    /**
     * @param $value
     * @return \Magento\Framework\Phrase|string
     */
    public function getLabel($value)
    {
        $options = $this->getOptions();

        if (isset($options[$value]))
            return $options[$value];

        return self::STATUS_WAITING;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $result = [];

        foreach ($this->getOptions() as $value  =>  $label) {
            $result[] = [
                'value' =>  $value,
                'label' =>  $label
            ];
        }

        return $result;
    }
}