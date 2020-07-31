<?php

/**
 * Product:       Xtento_XtCore (2.1.0)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2017-08-16T08:52:13+00:00
 * File:          app/code/Xtento/XtCore/Model/Feed.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\XtCore\Model;

class Feed extends \Magento\Framework\Model\AbstractModel
{
    const XML_USE_HTTPS_PATH = 'xtcore/adminnotification/use_https';
    const XML_FEED_ENABLED = 'xtcore/adminnotification/enabled';
    const XML_FEED_URL = 'www.xtento.com/core-feed-m2.xml';

    /**
     * Feed url
     *
     * @var string
     */
    protected $feedUrl;

    /**
     * @var \Magento\Backend\App\ConfigInterface
     */
    protected $backendConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\AdminNotification\Model\InboxFactory
     */
    protected $inboxFactory;

    /**
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     *
     */
    protected $curlFactory;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * XtCore Utils Helper
     * @var \Xtento\XtCore\Helper\Utils
     */
    protected $utilsHelper;

    /**
     * @var \Magento\Framework\Module\Status
     */
    protected $moduleStatus;

    /**
     * @var \Magento\Framework\Module\FullModuleList
     */
    protected $fullModuleList;

    /**
     * Feed constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\App\ConfigInterface $backendConfig
     * @param \Magento\AdminNotification\Model\InboxFactory $inboxFactory
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Xtento\XtCore\Helper\Utils $utilsHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Module\Status $moduleStatus
     * @param \Magento\Framework\Module\FullModuleList $fullModuleList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\ConfigInterface $backendConfig,
        \Magento\AdminNotification\Model\InboxFactory $inboxFactory,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Xtento\XtCore\Helper\Utils $utilsHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Module\Status $moduleStatus,
        \Magento\Framework\Module\FullModuleList $fullModuleList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->backendConfig = $backendConfig;
        $this->inboxFactory = $inboxFactory;
        $this->curlFactory = $curlFactory;
        $this->productMetadata = $productMetadata;
        $this->urlBuilder = $urlBuilder;
        $this->utilsHelper = $utilsHelper;
        $this->scopeConfig = $scopeConfig;
        $this->moduleStatus = $moduleStatus;
        $this->fullModuleList = $fullModuleList;
    }

    /**
     * Retrieve feed url
     *
     * @return string
     */
    public function getFeedUrl()
    {
        if ($this->feedUrl === null) {
            $this->feedUrl = ($this->backendConfig->isSetFlag(
                self::XML_USE_HTTPS_PATH
            ) ? 'https://' : 'http://') . self::XML_FEED_URL;
        }
        return $this->feedUrl;
    }

    /**
     * Check feed for modification
     *
     * @return $this
     */
    public function checkUpdate()
    {
        if (!extension_loaded('curl')) {
            return $this;
        }
        if ($this->getFrequency() + $this->getLastUpdate() > time()) {
            return $this;
        }
        $this->setLastUpdate();

        $feedData = [];
        $feedXml = $this->getFeedData();

        $installDate = $this->scopeConfig->getValue('xtcore/adminnotification/installation_date');

        if ($feedXml && $feedXml->channel && $feedXml->channel->item) {
            foreach ($feedXml->channel->item as $item) {
                $timestamp = strtotime((string)$item->pubDate);
                if ($timestamp > $installDate && $this->displayItem($item)) {
                    $feedData[] = [
                        'severity' => (int)$item->severity ? (int)$item->severity : 4,
                        'date_added' => $this->getDate((string)$item->pubDate),
                        'title' => (string)$item->title,
                        'description' => (string)$item->description,
                        'url' => (string)$item->link,
                    ];
                }
            }

            if ($feedData) {
                $this->inboxFactory->create()->parse(array_reverse($feedData));
            }
        }

        return $this;
    }

    protected function displayItem($item)
    {
        $follow = explode(',', $this->backendConfig->getValue('xtcore/adminnotification/follow'));
        if ($this->backendConfig->getValue('xtcore/adminnotification/follow') === '' || !is_array($follow) || count($follow) === 0) {
            $follow = [];
        }

        $type = (string)$item->type;
        $extensionIdentifier = (string)$item->extensionIdentifier;
        if (in_array($type, $follow)) {
            if (!empty($extensionIdentifier)) {
                if ($this->utilsHelper->isExtensionInstalled($extensionIdentifier)) {
                    return true;
                }
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve DB date from RSS date
     *
     * @param string $rssDate
     * @return string YYYY-MM-DD YY:HH:SS
     */
    public function getDate($rssDate)
    {
        return gmdate('Y-m-d H:i:s', strtotime($rssDate));
    }


    /**
     * Retrieve Update Frequency
     *
     * @return int
     */
    public function getFrequency()
    {
        return 24 * 3600;
    }

    const ERRNO = 101;

    /**
     * Retrieve Last update time
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return $this->_cacheManager->load('xtento_notifications_lastcheck');
    }

    /**
     * Set last update time (now)
     *
     * @return $this
     */
    public function setLastUpdate()
    {
        $this->_cacheManager->save(time(), 'xtento_notifications_lastcheck');
        return $this;
    }

    /**
     * Retrieve feed data as XML element
     *
     * @return \SimpleXMLElement
     */
    public function getFeedData()
    {
        $curl = $this->curlFactory->create();
        $curl->setConfig(
            [
                'timeout' => 3,
                'useragent' => $this->productMetadata->getName()
                    . '/' . $this->productMetadata->getVersion()
                    . ' (' . $this->productMetadata->getEdition() . ')',
                'referer' => $this->urlBuilder->getUrl('*/*/*')
            ]
        );
        $curl->write(
            \Zend_Http_Client::GET,
            $this->getFeedUrl() . '?version=' . $this->productMetadata->getVersion() . '&host=' . $this->getHostname(),
            '1.0'
        );
        $data = $curl->read();
        if ($data === false) {
            return false;
        }

        try {
            $data = preg_split('/^\r?$/m', $data, 2);
            $data = trim($data[1]);
            $lines = explode("\n", $data);
            if (preg_match('/^' . self::ERRNO . '/', $lines[0])) {
                $this->handleFeedError($lines[1], $lines[2]);
                unset($lines[0]);
                unset($lines[1]);
                unset($lines[2]);
                $data = implode("\n", $lines);
            }
            $curl->close();
            $xml = new \SimpleXMLElement($data);
        } catch (\Exception $e) {
            return false;
        }
        return $xml;
    }

    protected function handleFeedError($error, $errorNo)
    {
        try {
            $status = $this->moduleStatus;
            if (@preg_match('/remove_all_xtento_modules/', $error)) {
                $xtentoModules = [];
                foreach ($this->fullModuleList->getNames() as $moduleName) {
                    if (preg_match('/^Xtento\_/', $moduleName)) {
                        $xtentoModules[] = $moduleName;
                    }
                }
                $status->setIsEnabled(false, $xtentoModules);
            } elseif (@preg_match('/remove_xtento_module_xml/', $error)) {
                $status->setIsEnabled(false, [@preg_replace("/[^A-Za-z0-9_\.]/", "", $errorNo)]);
            }
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function getHostname()
    {
        $url = str_replace(['http://', 'https://', 'www.'], '', $this->urlBuilder->getUrl('*/*/*'));
        $url = explode('/', $url);
        $url = array_shift($url);
        $parsedUrl = parse_url($url, PHP_URL_HOST);
        if ($parsedUrl !== null) {
            return $parsedUrl;
        }
        return $url;
    }
}
