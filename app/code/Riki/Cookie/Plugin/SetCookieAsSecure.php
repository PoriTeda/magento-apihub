<?php

namespace Riki\Cookie\Plugin;

use Magento\Framework\Stdlib\Cookie\CookieMetadata;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\Cookie\SensitiveCookieMetadata;

class SetCookieAsSecure
{
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function beforeSetPublicCookie($subject, $name, $value, PublicCookieMetadata $metadata)
    {
        $metadata = $this->addSecureFlag($metadata);
        return [$name, $value, $metadata];
    }

    public function beforeSetSensitiveCookie($subject, $name, $value, SensitiveCookieMetadata $metadata)
    {
        $metadata = $this->addSecureFlag($metadata);
        return [$name, $value, $metadata];
    }

    protected function addSecureFlag(CookieMetadata $metadata)
    {
        if (isset($metadata)
            && is_null($metadata->getSecure())
            && $this->scopeConfig->getValue(\Riki\Session\Model\FrontendConfig::XML_PATH_COOKIE_SECURE)
        ) {
            $metadata->setSecure(true);
        }

        return $metadata;
    }
}
