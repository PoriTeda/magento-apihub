<?php

namespace Riki\SubscriptionPage\Block;

use Magento\Framework\View\Element\Template;

class LineAffiliate extends Template
{
    /**
     * @return int
     */
    public function getCookieLifetime()
    {
        return 86400;
    }

    /**
     * @param $string
     *
     * @return null|string|string[]
     */
    protected function _cleanConfig($string)
    {
        $string = rtrim($string, '/');
        return preg_replace('/http(s)?:\/\//', '', $string);
    }

    /**
     * @return string
     */
    public function getUrlConfigs()
    {
        $configUrl1 = $this->_cleanConfig($this->_scopeConfig->getValue('campaign_subscription_page/group_url_campaign_page/url_rt000033s'));
        $configUrl2 = $this->_cleanConfig($this->_scopeConfig->getValue('campaign_subscription_page/group_url_campaign_page/url_rt000032s'));
        $configUrl3 = $this->_cleanConfig($this->_scopeConfig->getValue('campaign_subscription_page/group_url_campaign_page/url_rt000034s'));

        $urlConfigs = [
            ['url' => "$configUrl1", 'cookieName' => 'COMPAIN_PAGE_RT000033S'],
            ['url' => "$configUrl2", 'cookieName' => 'COMPAIN_PAGE_RT000032S'],
            ['url' => "$configUrl3", 'cookieName' => 'COMPAIN_PAGE_RT000034S']
        ];

        return json_encode($urlConfigs);
    }
}
