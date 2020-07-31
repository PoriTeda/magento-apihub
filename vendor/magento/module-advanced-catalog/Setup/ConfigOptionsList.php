<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCatalog\Setup;

use Magento\Framework\Config\Data\ConfigData;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Setup\ConfigOptionsListInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;

/**
 * Deployment configuration options needed for AdvancedCatalog module
 *
 */
class ConfigOptionsList implements ConfigOptionsListInterface
{
    /**
     * Path to the values in the deployment config
     */
    const CONFIG_PATH_DB_CONNECTION_INDEXER = 'db/connection/indexer';

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function createConfig(array $options, DeploymentConfig $deploymentConfig)
    {
        $configData = new ConfigData(ConfigFilePool::APP_ENV);

        $optional = [
            ConfigOptionsListConstants::INPUT_KEY_DB_HOST => ConfigOptionsListConstants::KEY_HOST,
            ConfigOptionsListConstants::INPUT_KEY_DB_NAME => ConfigOptionsListConstants::KEY_NAME,
            ConfigOptionsListConstants::INPUT_KEY_DB_USER => ConfigOptionsListConstants::KEY_USER,
            ConfigOptionsListConstants::INPUT_KEY_DB_PASSWORD => ConfigOptionsListConstants::KEY_PASSWORD,
            ConfigOptionsListConstants::INPUT_KEY_DB_MODEL => ConfigOptionsListConstants::KEY_MODEL,
            ConfigOptionsListConstants::INPUT_KEY_DB_ENGINE => ConfigOptionsListConstants::KEY_ENGINE,
            ConfigOptionsListConstants::INPUT_KEY_DB_INIT_STATEMENTS => ConfigOptionsListConstants::KEY_INIT_STATEMENTS,
        ];

        foreach ($optional as $key => $value) {
            if (isset($options[$key])) {
                $configData->set(
                    self::CONFIG_PATH_DB_CONNECTION_INDEXER . '/' . $value,
                    $options[$key]
                );
            }
        }

        $configData->set(self::CONFIG_PATH_DB_CONNECTION_INDEXER . '/' . ConfigOptionsListConstants::KEY_ACTIVE, '1');
        /** forcing non-persistent connection for temporary tables */
        $configData->set(self::CONFIG_PATH_DB_CONNECTION_INDEXER . '/persistent', null);

        return [$configData];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $options, DeploymentConfig $deploymentConfig)
    {
        return [];
    }
}
