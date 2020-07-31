<?php

namespace Riki\Backend\Block\Widget\Grid\Column\Renderer;

class Currency extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Currency
{
    /**
     * Renders grid column
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($data = (string)$this->_getValue($row)) {
            $currency_code = $this->_getCurrencyCode($row);
            $data = floatval($data) * $this->_getRate($row);
            $sign = (bool)(int)$this->getColumn()->getShowNumberSign() && $data > 0 ? '+' : '';
            $data = sprintf("%f", $data);
            $data = $this->_localeCurrency->getCurrency($currency_code)->toCurrency($data, ['position'  =>  \Zend_Currency::RIGHT]);
            return $sign . $data;
        }
        return $this->getColumn()->getDefault();
    }
}
