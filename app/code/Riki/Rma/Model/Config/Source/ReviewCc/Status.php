<?php

namespace Riki\Rma\Model\Config\Source\ReviewCc;

class Status implements \Magento\Framework\Option\ArrayInterface
{
    const STATUS_NEW = 1;
    const STATUS_RUNNING = 2;
    const STATUS_DONE = 3;

    /**
     * @return array
     */
    public function getOptions()
    {
        return [
            self::STATUS_NEW    =>  __('New'),
            self::STATUS_RUNNING    =>  __('Running'),
            self::STATUS_DONE   =>  __('Done')
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

        return self::STATUS_NEW;
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
