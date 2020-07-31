<?php

/**
 * Product:       Xtento_XtCore (2.1.0)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2017-08-16T08:52:13+00:00
 * File:          app/code/Xtento/XtCore/Helper/Utils.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\XtCore\Helper;

use Magento\Framework\Exception\LocalizedException;

class Utils extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Module List
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * Utils constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    ) {
        parent::__construct($context);
        $this->moduleList = $moduleList;
        $this->productMetadata = $productMetadata;
    }

    public function mageVersionCompare($version1, $version2, $operator)
    {
        return version_compare($version1, $version2, $operator);
    }

    /**
     * Checks if an extension is installed and enabled
     *
     * @param $extensionIdentifier
     * @return bool
     */
    public function isExtensionInstalled($extensionIdentifier)
    {
        return $this->moduleList->has($extensionIdentifier);
    }

    /**
     * Is the module running in a Magento Enterprise Edition installation?
     *
     * @return bool
     */
    public function isMagentoEnterprise()
    {
        return ($this->productMetadata->getEdition() == 'Enterprise');
    }

    /**
     * Get Magento Version
     */
    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Either create a ZIP for multiple files or return the filename/file
     *
     * @param $fileArray
     * @return array
     * @throws \Exception
     */
    public function prepareFilesForDownload($fileArray)
    {
        if (count($fileArray) > 1) {
            // We need to zip multiple files and return a ZIP file to browser
            if (!@class_exists('ZipArchive') && !function_exists('gzopen')) {
                throw new LocalizedException(__(
                    'PHP ZIP extension not found. Please download files manually from the server, or install the ZIP extension, or export just one file with each profile.'
                ));
            }
            // ZIP creation
            $zipFile = false;
            if (@class_exists('ZipArchive')) {
                // Try creating it using the PHP ZIP functions
                $zipArchive = new \ZipArchive();
                $zipFile = tempnam(sys_get_temp_dir(), 'zip');
                if (!$zipFile) {
                    throw new LocalizedException(__(
                        'Could not generate temporary file in tmp folder to store ZIP file. Please contact your hoster and make sure the PHP "tmp" (tempnam(sys_get_temp_dir())) directory is writable. ZIP creation failed.'
                    ));
                }
                if ($zipArchive->open($zipFile, \ZipArchive::CREATE) !== true) {
                    throw new LocalizedException(__('Could not open file ' . $zipFile . '. ZIP creation failed.'));
                }
                foreach ($fileArray as $filename => $content) {
                    $zipArchive->addFromString($filename, $content);
                }
                $zipArchive->close();
            } else {
                if (function_exists('gzopen')) {
                    // Try creating it using the PclZip class
                    $zipFile = tempnam(sys_get_temp_dir(), 'zip');
                    if (!$zipFile) {
                        throw new LocalizedException(__(
                            'Could not generate temporary file in tmp folder to store ZIP file. Please contact your hoster and make sure the PHP "tmp" (tempnam(sys_get_temp_dir())) directory is writable. ZIP creation failed.'
                        ));
                    }
                    $zipArchive = new \Xtento\XtCore\lib\PclZip($zipFile);
                    if (!$zipArchive) {
                        throw new LocalizedException(__('Could not open file ' . $zipFile . '. ZIP creation failed.'));
                    }
                    foreach ($fileArray as $filename => $content) {
                        $zipArchive->add(
                            [
                                [
                                    PCLZIP_ATT_FILE_NAME => $filename,
                                    PCLZIP_ATT_FILE_CONTENT => $content
                                ]
                            ]
                        );
                    }
                }
            }
            if (!$zipFile) {
                throw new LocalizedException(__('ZIP file couldn\'t be created.'));
            }
            $zipData = file_get_contents($zipFile);
            @unlink($zipFile);
            return ['filename' => 'export_' . time() . '.zip', 'data' => $zipData];
        } else {
            // Just one file, output to browser
            foreach ($fileArray as $filename => $content) {
                return ['filename' => $filename, 'data' => $content];
            }
        }
        return [];
    }
}
