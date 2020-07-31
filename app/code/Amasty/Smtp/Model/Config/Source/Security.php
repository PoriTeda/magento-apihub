<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Security implements ArrayInterface
{
    const SECURITY_NONE = '';
    const SECURITY_SSL  = 'ssl';
    const SECURITY_TLS  = 'tls';

    public function toOptionArray()
    {
        return [
            ['value' => self::SECURITY_NONE,    'label' => __('None')],
            ['value' => self::SECURITY_SSL,     'label' => __('SSL')],
            ['value' => self::SECURITY_TLS,     'label' => __('TLS')],
        ];
    }
}
