<?php

namespace Riki\Sales\Model\Config\Source;

class OrderDistributionChanelYear implements \Magento\Framework\Option\ArrayInterface
{
    const YEAR_2016 = '2016';
    const YEAR_2017 = '2017';

    /**
     * @return array
     */
    protected function _getOptionList(){
        return [
            self::YEAR_2016 =>  __('2016'),
            self::YEAR_2017 =>  __('2017')
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_getOptionList();
    }

    /**
     * Return option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        foreach ($this->_getOptionList() as $key    =>  $value) {
            $options[] = ['label' => $value, 'value' => $key];
        }

        return $options;
    }
}
