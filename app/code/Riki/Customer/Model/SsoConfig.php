<?php

namespace Riki\Customer\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class SsoConfig
{
    const XML_PATH_SSO_ENABLED = 'sso_login_setting/sso_group/use_sso_login';
    const XML_PATH_SSO_LOGIN_URL = 'sso_login_setting/sso_group/url_login_sso';
    const XML_PATH_SSO_LOGOUT_URL = 'sso_login_setting/sso_group/url_logout_sso';
    const XML_PATH_SSO_REGISTER_URL = 'sso_login_setting/sso_group/url_register_sso';
    const XML_PATH_APP_ENABLED = 'mypage_app_config_block/app_config_block/use_my_page_app';
    const XML_PATH_APP_URL = 'mypage_app_config_block/app_config_block/url_my_page_app';

    const SSO_SESSION_ID = 'SSOSID';

    const SSO_RESPONSE_SUCCESS_CODE = 'MID00000';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * SsoConfig constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function isEnabled($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SSO_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function getLoginUrl($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SSO_LOGIN_URL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function getLogoutUrl($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SSO_LOGOUT_URL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function getRegisterUrl($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SSO_REGISTER_URL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }


    /**
     * @param null $store
     *
     * @return mixed
     */
    public function isEnabledApp($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_APP_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function getUrlApp($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_APP_URL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
