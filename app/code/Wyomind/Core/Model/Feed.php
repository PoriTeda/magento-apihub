<?php

/**
 * Copyright © 2015 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\Core\Model;

class Feed extends \Magento\AdminNotification\Model\Feed
{
    
    const FEED_URL = 'rss.wyomind.com';

    const FREQUENCY = 1; // hour(s)
    
    public $coreHelper = null;
    public $moduleList = null;
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\ConfigInterface $backendConfig,
        \Magento\AdminNotification\Model\InboxFactory $inboxFactory,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Wyomind\Core\Helper\Data $coreHelper,
        \Magento\Framework\Module\ModuleList $moduleList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $backendConfig, $inboxFactory, $curlFactory, $deploymentConfig, $productMetadata, $urlBuilder, $resource, $resourceCollection, $data);
        $this->coreHelper = $coreHelper;
        $this->moduleList = $moduleList;
    }
    
    public function getFeedUrl()
    {
        $httpPath = 'http://';
        if ($this->_feedUrl === null) {
            $this->_feedUrl = $httpPath . self::FEED_URL;
        }
        
        $url = $this->coreHelper->getDefaultConfig("web/secure/base_url");
        $version = $this->moduleList->getOne("Wyomind_Core")['setup_version'];
        $lastcheck = $this->getLastUpdate();
        
        return $this->_feedUrl."/?domain=$url&version=$version&lastcheck=$lastcheck&now=" . time();
    }
    
    public function getFrequency()
    {
        return self::FREQUENCY * 3600*24; // 24h
    }
    
    public function getLastUpdate()
    {
        return $this->_cacheManager->load('wyomind_notifications_lastcheck');
    }

    /**
     * Set last update time (now)
     *
     * @return $this
     */
    public function setLastUpdate()
    {
        $this->_cacheManager->save(time(), 'wyomind_notifications_lastcheck');
        return $this;
    }
    
    public function checkUpdate()
    {
        if ($this->getFrequency() + $this->getLastUpdate() > time()) {
            return $this;
        }

        $feedData = [];

        $feedXml = $this->getFeedData();

        $installDate = strtotime($this->_deploymentConfig->get(\Magento\Framework\Config\ConfigOptionsListConstants::CONFIG_PATH_INSTALL_DATE));
        
        if ($feedXml && $feedXml->channel && $feedXml->channel->item) {
            foreach ($feedXml->channel->item as $item) {
                $itemPublicationDate = (string)$item->pubDate;
                if ($installDate <= $itemPublicationDate) {
                    $feedData[] = [
                        'severity' => (int)$item->severity,
                        'date_added' => date('Y-m-d H:i:s', $itemPublicationDate),
                        'title' => (string)$item->title,
                        'description' => (string)$item->description,
                        'url' => (string)$item->link,
                    ];
                }
            }

            if ($feedData) {
                $this->_inboxFactory->create()->parse(array_reverse($feedData));
            }
        }
        $this->setLastUpdate();
        return $this;
    }
}
