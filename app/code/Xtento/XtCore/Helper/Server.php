<?php

/**
 * Product:       Xtento_XtCore (2.1.0)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2017-08-16T08:52:13+00:00
 * File:          app/code/Xtento/XtCore/Helper/Server.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\XtCore\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Server extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Core store config
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $configValueFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * Server constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->scopeConfig = $context->getScopeConfig();
        $this->configValueFactory = $configValueFactory;
        $this->filesystem = $filesystem;
    }

    /**
     * Get Magento base directory
     *
     * @return \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    public function getBaseDir()
    {
        return $this->filesystem->getDirectoryWrite(DirectoryList::ROOT);
    }

    /**
     * Increase memory limit to $newMemoryLimit, but only if current value is lower
     *
     * @param $newMemoryLimit
     * @return bool
     */
    public function increaseMemoryLimit($newMemoryLimit)
    {
        $currentLimit = ini_get('memory_limit');
        if ($currentLimit == -1) {
            // No limit, no need to increase
            return true;
        }
        $currentLimitInBytes = $this->convertToByte($currentLimit);
        $newMemoryLimitInBytes = $this->convertToByte($newMemoryLimit);
        if ($currentLimitInBytes < $newMemoryLimitInBytes) {
            @ini_set('memory_limit', $newMemoryLimit);
            return true;
        } else {
            return false;
        }
    }

    public function convertToByte($value)
    {
        if (stripos($value, 'G') !== false) {
            return (int)$value * pow(1024, 3);
        } elseif (stripos($value, 'M') !== false) {
            return (int)$value * 1024 * 1024;
        } elseif (stripos($value, 'K') !== false) {
            return (int)$value * 1024;
        }
        return (int)$value;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        $url = str_replace(['http://', 'https://', 'www.'], '', $this->scopeConfig->getValue('web/unsecure/base_url'));
        $url = explode('/', $url);
        $url = array_shift($url);
        $parsedUrl = parse_url($url, PHP_URL_HOST);
        if ($parsedUrl !== null) {
            return $parsedUrl;
        }
        return $url;
    }

    /**
     * @return string
     */
    public function getSecondName()
    {
        $url = str_replace(['http://', 'https://', 'www.'], '', $this->_request->getServer('SERVER_NAME'));
        $url = explode('/', $url);
        $url = array_shift($url);
        $parsedUrl = parse_url($url, PHP_URL_HOST);
        if ($parsedUrl !== null) {
            return $parsedUrl;
        }
        return $url;
    }

    /**
     * @param array $configuration
     * @param bool $updateConfiguration
     * @return bool
     */
    public function confirm(array $configuration, $updateConfiguration = false)
    {
        $sName = $this->getFirstName();
        $sName2 = $this->getSecondName();
        $s = trim($this->scopeConfig->getValue($configuration['config_path'] . 'serial'));
        if ($s !== sha1(sha1($configuration['ext_id'] . '_' . $sName)) &&
            $s !== sha1(sha1($configuration['ext_id'] . '_' . $sName2))
        ) {
            if ($updateConfiguration) {
                try {
                    $configValue = $this->configValueFactory->create();
                    /** @var $configValue \Magento\Framework\App\Config\Value */
                    $configValue->load($configuration['config_path'] . 'enabled', 'path');
                    $configValue->setValue(0)->setPath($configuration['config_path'] . 'enabled')->save();
                } catch (\Exception $e) {
                    return false;
                }
            }
            return false;
        } else {
            return true;
        }
    }
}
