<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Plugin;

class IndexerConfigData
{
    /**
     * @var \Magento\CatalogPermissions\App\Config
     */
    protected $config;

    /**
     * @param \Magento\CatalogPermissions\App\Config $config
     */
    public function __construct(\Magento\CatalogPermissions\App\Config $config)
    {
        $this->config = $config;
    }

    /**
     *  Unset indexer data in configuration if flat is disabled
     *
     * @param \Magento\Indexer\Model\Config\Data $subject
     * @param array|mixed|null $data
     * @param string $path
     * @param mixed $default
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        \Magento\Indexer\Model\Config\Data $subject,
        $data,
        $path = null,
        $default = null
    ) {
        if (!$this->config->isEnabled()) {
            // Process Category indexer data
            $this->processData(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID, $path, $default, $data);
            // Process Product indexer data
            $this->processData(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID, $path, $default, $data);
        }

        return $data;
    }

    /**
     * @param int $indexerId
     * @param string $path
     * @param mixed $default
     * @param mixed $data
     * @return void
     */
    protected function processData($indexerId, $path, $default, &$data)
    {
        if (!$path && isset($data[$indexerId])) {
            unset($data[$indexerId]);
        } elseif ($path) {
            list($firstKey,) = explode('/', $path);
            if ($firstKey == $indexerId) {
                $data = $default;
            }
        }
    }
}
