<?php

namespace Bluecom\Paygent\Plugin;

class DisableHttps
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
    }

    public function aroundShouldBeSecure($subject, $proceed, $path)
    {
        if ($this->_scopeConfig->getValue('payment/paygent/use_http_inform')
            && ($path == '/paygent/paygent/response' || $path == '/subscriptions/paygent/response')
        ) {
            return false;
        }

        return $proceed($path);
    }
}
