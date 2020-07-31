<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Model\Config\Source;

use Amasty\Smtp\Model\Provider\Config;
use Magento\Framework\Option\ArrayInterface;

class Providers implements ArrayInterface
{
    const AUTH_TYPE_NONE    = '';
    const AUTH_TYPE_LOGIN   = 'login';

    protected $providersConfig;

    /**
     * @param Config $providersConfig
     */
    public function __construct(Config $providersConfig)
    {
        $this->providersConfig = $providersConfig;
    }

    public function toOptionArray()
    {
        $providers = $this->providersConfig->get();

        $providersArr = array_column($providers, 'title');
        asort($providersArr);
        $resultArr = array();

        foreach($providersArr as $key => $val){
            $resultArr[] = [
                'value' => $key,
                'label' => __($val)
            ];
        }

        return $resultArr;
    }
}
