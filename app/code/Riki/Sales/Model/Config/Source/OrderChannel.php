<?php

namespace Riki\Sales\Model\Config\Source;

class OrderChannel implements \Magento\Framework\Option\ArrayInterface
{
    const ORDER_CHANEL_TYPE_ONLINE = 'online';
    const ORDER_CHANEL_TYPE_TAX = 'fax';
    const ORDER_CHANEL_TYPE_CALL = 'call';
    const ORDER_CHANEL_TYPE_EMAIL = 'email';
    const ORDER_CHANEL_TYPE_POSTCARD = 'postcard';
    const ORDER_CHANEL_TYPE_MACHINE_API = 'machine_maintenance';

    /**
     * @return array
     */
    protected function _getOptionList(){
        return [
            self::ORDER_CHANEL_TYPE_ONLINE =>  __('Online'),
            self::ORDER_CHANEL_TYPE_TAX =>  __('By Fax'),
            self::ORDER_CHANEL_TYPE_CALL =>  __('By Call'),
            self::ORDER_CHANEL_TYPE_EMAIL =>  __('By Email'),
            self::ORDER_CHANEL_TYPE_POSTCARD =>  __('By Postcard'),
            self::ORDER_CHANEL_TYPE_MACHINE_API =>  __('Machine maintenance')
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
