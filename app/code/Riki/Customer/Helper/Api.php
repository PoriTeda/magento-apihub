<?php

namespace Riki\Customer\Helper;

use Magento\Store\Model\ScopeInterface;

class Api extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Api constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @param $path
     * @return string
     */
    public function getConsumerApiUrl($path)
    {
        $baseUrl = $this->scopeConfig->getValue(
            'consumer_db_api_url/setting_base_url/setCustomer_domain',
            ScopeInterface::SCOPE_WEBSITE
        );
        return rtrim($baseUrl, '/') . $path;
    }

    /**
     * Get config api url
     *
     * @param $path
     * @return string
     */
    public function getConsumerMidInfoApiUrl($path)
    {
        $baseUrl = $this->scopeConfig->getValue(
            'consumer_db_api_url/setting_url_get_mid_info/api_url',
            ScopeInterface::SCOPE_WEBSITE
        );
        return rtrim($baseUrl, '/') . $path;
    }
}
