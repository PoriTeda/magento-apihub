<?php


namespace Nestle\Locale\Plugin\Magento\Framework\Locale\Format;


class Plugin
{
    public function beforeGetNumber($subject, $value)
    {
        return str_replace(",", "", $value);
    }
}
