<?php

namespace Riki\Theme\Block\Js;

class Cookie extends \Magento\Framework\View\Element\Js\Cookie
{
    public function getSecure()
    {
        return $this->sessionConfig->getCookieSecure();
    }
}