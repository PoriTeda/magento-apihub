<?php

namespace Riki\Sales\Model\Config\Source;

class OrderType implements \Magento\Framework\Option\ArrayInterface
{
    const ORDER_TYPE_NORMAL = 1;
    const ORDER_TYPE_REPLACEMENT = 2;
    const ORDER_TYPE_FREE_SAMPLE = 3;

    /**
     * @return array
     */
    protected function _getOptionList(){
        return [
            self::ORDER_TYPE_NORMAL =>  __('Normal order'),
            self::ORDER_TYPE_REPLACEMENT =>  __('Free of charge - Replacement'),
            self::ORDER_TYPE_FREE_SAMPLE =>  __('Free of charge - Free samples')
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

    /**
     * get Title by code
     *
     * @param $code
     * @return \Magento\Framework\Phrase
     */
    public function getTitleByCode($code){
        if(isset($this->_getOptionList()[$code]))
            return $this->_getOptionList()[$code];
        return __('Unknown');
    }
}
