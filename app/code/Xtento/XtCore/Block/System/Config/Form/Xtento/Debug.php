<?php

/**
 * Product:       Xtento_XtCore (2.1.0)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2017-08-16T08:52:13+00:00
 * File:          app/code/Xtento/XtCore/Block/System/Config/Form/Xtento/Debug.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\XtCore\Block\System\Config\Form\Xtento;

use Magento\Framework\App\Filesystem\DirectoryList;

class Debug extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var \Magento\Framework\Filesystem\Directory\Write
     */
    protected $directory;

    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $zendClientFactory;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Http\ZendClientFactory $httpClientFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->zendClientFactory = $httpClientFactory;
    }

    /*
     * Debug information is shown at System > Configuration > XTENTO Extensions > General Configuration
     */
    protected function _getHeaderHtml($element)
    {
        $headerHtml = parent::_getHeaderHtml($element);
        $debugInfo = [];
        try {
            // Fetch public IP address of server - important if you have failing FTP transfers
            // and need to add the public IP address to the firewall, etc.
            $url = 'https://www.xtento.com/license/info/getip';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $ipAddress = file_get_contents($url);
            } else {
                $client = $this->zendClientFactory->create();
                $client->setUri($url);
                $client->setConfig(['timeout' => 10]);
                $response = $client->request('GET');
                $ipAddress = $response->getBody();
            }
        } catch (\Exception $e) {
            return '------------------------------------------------<div style="display:none">Exception: ' .
                $e->getMessage() . '</div>' . $headerHtml;
        }

        $debugInfo[] = "Public Server IP Address: $ipAddress<br/>";
        $debugInfo[] = "PHP memory_limit: " . ini_get('memory_limit');
        $debugInfo[] = "PHP max_execution_time: " . ini_get('max_execution_time');
        $debugInfo[] = "Magento Base Path: " . $this->directory->getAbsolutePath();

        $headerHtml = str_replace(
            '<table cellspacing="0" class="form-list">',
            implode("<br/>", $debugInfo) . '<table cellspacing="0" class="form-list">',
            $headerHtml
        );
        return $headerHtml;
    }
}
