<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Auth implements ArrayInterface
{
    const AUTH_TYPE_NONE    = '';
    const AUTH_TYPE_LOGIN   = 'login';

    public function toOptionArray()
    {
        return [
            ['value' => self::AUTH_TYPE_NONE,  'label' => __('Authentication Not Required')],
            ['value' => self::AUTH_TYPE_LOGIN, 'label' => __('Login/Password')],
        ];
    }
}
