<?php

namespace Nestle\Gillette\Helper;

class Admin extends Data
{
    /**
     * @return \Magento\Backend\Model\Session\Quote
     */
    protected function _getSession()
    {
        return parent::getSessionQuote();
    }

    /**
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->_getSession()->getQuote();
    }

    /**
     * check rule to show/hide add gillette-product button
     *
     * @return bool
     */
    public function allowedDisplayAddGilletteProductButton()
    {
        if ($this->_getSession()->getQuote()->getItemsCount()) {
            return false;
        }

        return true;
    }
}
