<?php

namespace Riki\Customer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class SsoUrl extends AbstractHelper
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Riki\Customer\Model\SsoConfig
     */
    protected $ssoConfig;

    /**
     * SsoUrl constructor.
     *
     * @param Context $context
     * @param \Riki\Customer\Model\SsoConfig $ssoConfig
     */
    public function __construct(
        Context $context,
        \Riki\Customer\Model\SsoConfig $ssoConfig
    )
    {
        $this->request = $context->getRequest();
        $this->ssoConfig = $ssoConfig;
        parent::__construct($context);
    }

    /**
     * @param null $returnUrl
     * @param null $store
     *
     * @return string
     */
    public function getLoginUrl($returnUrl = null, $store = null)
    {
        if ($this->ssoConfig->isEnabled($store)) {
            return $this->getRedirectUrl($this->ssoConfig->getLoginUrl($store), $returnUrl);
        }

        return $this->_getDefaultLoginUrl($returnUrl);
    }

    /**
     * @param null $returnUrl
     * @param null $store
     *
     * @return string
     */
    public function getLogoutUrl($returnUrl = null, $store = null)
    {
        return $this->getRedirectUrl($this->ssoConfig->getLogoutUrl($store), $returnUrl);
    }

    /**
     * @param null $returnUrl
     * @param null $store
     *
     * @return string
     */
    public function getRegisterUrl($returnUrl = null, $store = null)
    {
        return $this->getRedirectUrl($this->ssoConfig->getRegisterUrl($store), $returnUrl);
    }

    /**
     * @param $redirectUrl
     * @param null $returnUrl
     *
     * @return string
     */
    public function getRedirectUrl($redirectUrl, $returnUrl = null)
    {
        if (strpos($redirectUrl, 'URL=') !== false) {
            if (!$returnUrl) {
                $returnUrl = $this->_getReturnUrl();
            }

            $redirectUrl .= urlencode($returnUrl);
        }

        return $redirectUrl;
    }

    /**
     * @return string
     */
    protected function _getReturnUrl()
    {
        if (($referrer = $this->request->getParam(\Magento\Customer\Model\Url::REFERER_QUERY_PARAM_NAME))
            || ($referrer = $this->request->getParam(\Magento\Framework\App\Action\Action::PARAM_NAME_URL_ENCODED))
        ) {
            return $this->urlDecoder->decode($referrer);
        } else {
            return $this->_getDefaultUrl();
        }
    }

    /**
     * @return string
     */
    protected function _getDefaultUrl()
    {
        return $this->_urlBuilder->getUrl('customer/account');
    }

    /**
     * @param null $returnUrl
     *
     * @return string
     */
    protected function _getDefaultLoginUrl($returnUrl)
    {
        if ($returnUrl) {
            return $this->_urlBuilder->getUrl(
                'customer/account/login/',
                [\Magento\Customer\Model\Url::REFERER_QUERY_PARAM_NAME => $this->urlEncoder->encode($returnUrl)]
            );
        }

        return $this->_urlBuilder->getUrl('customer/account/login');
    }
}
