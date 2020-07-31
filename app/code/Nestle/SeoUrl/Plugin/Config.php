<?php

namespace Nestle\SeoUrl\Plugin;

/**
 * Class Config
 * @package Nestle\SeoUrl\Plugin
 */
class Config
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * Config constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\UrlInterface $urlInterface
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlInterface
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->urlInterface = $urlInterface;
    }

    /**
     * @param \Magento\Framework\View\Page\Config $config
     * @param $result
     * @return string
     */
    public function afterGetRobots(\Magento\Framework\View\Page\Config $config, $result)
    {
        $currentUrl = $this->urlInterface->getCurrentUrl();

        $excludeUrls = $this->scopeConfig->getValue(
            'design/search_engine_robots/exclude_default_robot_urls',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($excludeUrls && $excludeUrls != "") {
            $excludeUrlArr = preg_split("/\,/", $excludeUrls);
            $excludeMetaTag = $this->scopeConfig->getValue(
                'design/search_engine_robots/exclude_default_robots',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            foreach ($excludeUrlArr as $url) {
                if ($url != "") {
                    $url = trim($url);
                    $pos = strpos($url, "/");
                    if ($pos > 0 || $pos === false) {
                        $url = "/" . $url;
                    }
                    if (strpos($currentUrl, $url) !== false) {
                        return $excludeMetaTag;
                    }
                }
            }
        }
        return $result;
    }
}