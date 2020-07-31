<?php

/**
 * Product:       Xtento_XtCore (2.1.0)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2017-08-16T08:52:13+00:00
 * File:          app/code/Xtento/XtCore/Block/System/Config/Form/Xtento/Module.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\XtCore\Block\System\Config\Form\Xtento;

class Module extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * Module list
     *
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $moduleList;

    /**
     * Object Manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Product Metadata
     *
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->moduleList = $moduleList;
        $this->objectManager = $objectManager;
        $this->productMetadata = $productMetadata;
    }

    protected function _getHeaderHtml($element)
    {
        $headerHtml = parent::_getHeaderHtml($element);
        if ($element->getGroup() && isset($element->getGroup()['fieldset_css'])
            && $element->getGroup()['fieldset_css'] !== '') {
            // Set up cache, using the Magento cache doesn't make sense as it won't cache if cache is disabled
            try {
                $cacheBackend = new \Zend_Cache_Backend();
                $cache = \Zend_Cache::factory(
                    'Core',
                    'File',
                    ['lifetime' => 43200],
                    ['cache_dir' => $cacheBackend->getTmpDir()]
                );
            } catch (\Exception $e) {
                return $headerHtml;
            }
            // Get data model
            $moduleInformation = explode(
                '|',
                $element->getGroup()['fieldset_css']
            ); // First part: module name (Xtento_Abc), second part: data model
            $moduleName = $moduleInformation[0];
            $dataModelName = $moduleInformation[1];
            $cacheKey = 'info_' . $moduleName;
            if ($moduleName !== "") {
                $moduleVersion = $this->moduleList->getOne($moduleName)['setup_version'];
                if (!empty($moduleVersion)) {
                    $cacheKey .= '_' . str_replace('.', '_', $moduleVersion);
                }
            }
            // Is the response cached?
            $cachedHtml = $cache->load($cacheKey);
            #$cachedHtml = false; // Test: disable cache
            if ($cachedHtml !== false && $cachedHtml !== '') {
                $storeHtml = $cachedHtml;
            } else {
                try {
                    $dataModel = $this->objectManager->get($dataModelName);
                    $dataModel->afterLoad();
                    // Fetch info whether updates for the module are available
                    $url = 'ht' . 'tp://w' . 'ww.' . 'xte' . 'nto.' . 'co' . 'm/li' . 'cense/info/';
                    $version = $this->productMetadata->getVersion();
                    $extensionVersion = $dataModel->getValue();
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        $storeHtml = file_get_contents($url . '?version=' . $version . '&d=' . $extensionVersion);
                    } else {
                        $client = new \Zend_Http_Client($url, ['timeout' => 10]);
                        $client->setParameterGet('version', $version);
                        $client->setParameterGet('d', $extensionVersion);
                        $response = $client->request('GET');
                        // Post version
                        /*$client = new Zend_Http_Client($url, ['timeout' => 10)];
                        $client->setParameterPost('version', $version);
                        $client->setParameterPost('d', $extensionVersion);
                        $response = $client->request('POST');*/
                        $storeHtml = $response->getBody();
                    }
                    $cache->save($storeHtml, $cacheKey);
                } catch (\Exception $e) {
                    return '------------------------------------------------<div style="display:none">Exception: ' .
                        $e->getMessage() . '</div>' . $headerHtml;
                }
            }
            if (preg_match('/There has been an error processing your request/', $storeHtml)) {
                return $headerHtml;
            }
            $moduleHelper = $this->objectManager->create(str_replace('_', '\\', $moduleName).'\\Helper\\Module');
            if ($moduleHelper && $moduleHelper->isWrongEdition()) {
                $storeHtml = '<div style="color: red; font-weight: bold; font-size: 120%; padding: 5px; border: 2px solid red;">Attention: The installed extension version is not compatible with Magento Enterprise Edition. The compatibility of the currently installed extension version has only been confirmed with Magento Community Edition. Please go to <a href="https://www.xtento.com" target="_blank">www.xtento.com</a> to purchase or download the Enterprise Edition version of this extension.</div><br/><br/>' . $storeHtml;
            }
            if ($this->_scopeConfig->isSetFlag('advanced/modules_disable_output/' . $moduleName)) {
                $storeHtml = "<div style='padding: 5px; border: 1px solid #000; line-height: 20px; color: red; font-size: 14px; font-weight: bold'>Warning: The modules output has been disabled at Stores > Configuration > Advanced > Advanced. You may not see the module in the backend/frontend.</div><br/>" . $storeHtml;
            }
            $headerHtml = str_replace(
                '</div><table cellspacing="0" class="form-list">',
                $storeHtml . '</div><table cellspacing="0" class="form-list">',
                $headerHtml
            );
        }
        return $headerHtml;
    }
}
