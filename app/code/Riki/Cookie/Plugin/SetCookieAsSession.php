<?php

namespace Riki\Cookie\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;

class SetCookieAsSession
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * SetCookieAsSession constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\State $appState
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->appState = $appState;
    }

    /**
     * In case value of duration equal zero, it means we want cookie is considered as session cookie,
     * we need to set duration to null to prevent Magento set expire time for the cookie
     *
     * @param $subject
     * @param $name
     * @param $value
     * @param PublicCookieMetadata $metadata
     *
     * @return array
     */
    public function beforeSetPublicCookie($subject, $name, $value, PublicCookieMetadata $metadata)
    {
        if (!$this->_isEnabled()) {
            return [$name, $value, $metadata];
        }

        if (isset($metadata) && !is_null($metadata->getDuration()) && $metadata->getDuration() == 0) {
            $metadata->setDuration(null);
        }

        $metadata = $this->_handleExceptionCase($name, $metadata);

        return [$name, $value, $metadata];
    }

    /**
     * Set cookie as session for those cookies which duration are hard code
     *
     * @param $name
     * @param PublicCookieMetadata $metadata
     *
     * @return PublicCookieMetadata
     */
    protected function _handleExceptionCase($name, PublicCookieMetadata $metadata)
    {
        if (in_array($name, [
            \Magento\Theme\Controller\Result\MessagePlugin::MESSAGES_COOKIES_NAME,
            \Magento\Framework\App\PageCache\Version::COOKIE_NAME
        ])) {
            $metadata->setDuration(null);
        }

        return $metadata;
    }

    protected function _isEnabled()
    {
        if ($this->appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
            $sessionLifetime = $this->scopeConfig->getValue(\Magento\Backend\Model\Auth\Session::XML_PATH_SESSION_LIFETIME);
        } else {
            $sessionLifetime = $this->scopeConfig->getValue(\Magento\Framework\Session\Config::XML_PATH_COOKIE_LIFETIME);
        }

        return (int)$sessionLifetime == 0;
    }
}
