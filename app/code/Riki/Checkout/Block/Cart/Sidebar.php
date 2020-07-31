<?php
namespace Riki\Checkout\Block\Cart;

/**
 * Class Sidebar
 * @package Riki\Checkout\Block\Cart
 */
class Sidebar extends \Magento\Checkout\Block\Cart\Sidebar{


    /**
     * @return mixed
     */
    public function getSsoLoginUrl(){
        return $loginUrl = $this->_scopeConfig->getValue('sso_login_setting/sso_group/url_login_sso', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
    * Set default template
    * @return string
    */
    protected function _toHtml()
    {
        $this->setModuleName($this->extractModuleName('Magento\Checkout\Block\Cart\Sidebar'));
        return parent::_toHtml();
    }
}