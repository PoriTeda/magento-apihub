<?php
namespace Riki\Preorder\Helper;
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
    public function getQuote(){
        return $this->_getSession()->getQuote();
    }

    /**
     * check the current cart is pre-order
     *
     * @return bool
     */
    public function isPreOrderCart(){
        return $this->_getSession()->getData(\Riki\Preorder\Model\Config\PreOrderType::SESSION_FLAG_NAME);
    }

    /**
     * check to hide add product button at create order page
     *
     * @return bool
     */
    public function allowedDisplayAddProductButton(){
        if($this->isPreOrderCart() && $this->_getSession()->getQuote()->getItemsCount())
            return false;

        return true;
    }

    /**
     * check rule to show/hide add pre-product button
     *
     * @return bool
     */
    public function allowedDisplayAddPreProductButton(){
        if($this->_getSession()->getQuote()->getItemsCount())
            return false;

        return true;
    }
}
