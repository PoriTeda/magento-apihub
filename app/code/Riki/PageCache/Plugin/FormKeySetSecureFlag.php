<?php

namespace Riki\PageCache\Plugin;

class FormKeySetSecureFlag
{
    protected $sessionConfig;

    public function __construct(\Magento\Framework\Session\Config\ConfigInterface $sessionConfig)
    {
        $this->sessionConfig = $sessionConfig;
    }

    public function beforeSet(
        \Magento\Framework\App\PageCache\FormKey $subject,
        $value,
        \Magento\Framework\Stdlib\Cookie\PublicCookieMetadata $cookieMetadata
    )
    {
        $cookieMetadata->setSecure($this->sessionConfig->getCookieSecure());

        return [$value, $cookieMetadata];
    }
}