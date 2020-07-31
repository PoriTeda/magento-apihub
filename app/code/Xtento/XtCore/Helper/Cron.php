<?php

/**
 * Product:       Xtento_XtCore (2.1.0)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2017-08-16T08:52:13+00:00
 * File:          app/code/Xtento/XtCore/Helper/Cron.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\XtCore\Helper;

class Cron extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CRON_PATH_PREFIX = 'crontab/default/jobs/xtento_';

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \Xtento\XtCore\Model\ResourceModel\Config
     */
    protected $xtCoreConfig;

    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $configValueFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Cron constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Xtento\XtCore\Model\ResourceModel\Config $xtCoreConfig
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Xtento\XtCore\Model\ResourceModel\Config $xtCoreConfig,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory
    ) {
        parent::__construct($context);
        $this->resourceConnection = $resourceConnection;
        $this->xtCoreConfig = $xtCoreConfig;
        $this->configValueFactory = $configValueFactory;
        $this->scopeConfig = $context->getScopeConfig();
    }

    /**
     * Get timestapm when the XtCore module was installed for the first time
     *
     * @return mixed
     */
    public function getInstallationDate()
    {
        return $this->scopeConfig->getValue('xtcore/adminnotification/installation_date');
    }

    public function isCronRunning()
    {
        $lastExecution = $this->getLastCronExecution();
        if (empty($lastExecution)) {
            return false;
        }
        $differenceInSeconds = $this->getTimestamp() - $lastExecution;
        // If the cronjob has been executed within the last 15 minutes, return true
        return $differenceInSeconds < (60 * 15);
    }

    public function getLastCronExecution()
    {
        return $this->xtCoreConfig->getConfigValue('xtcore/crontest/last_execution');
    }

    public function getTimestamp()
    {
        return (string)time();
    }

    /**
     * Add cronjob to database
     *
     * @param $cronIdentifier
     * @param $cronExpression
     * @param $cronRunModel
     * @return $this
     */
    public function addCronjob($cronIdentifier, $cronExpression, $cronRunModel)
    {
        $this->configValueFactory->create()->load(
            $this->getCronExpressionConfigPath($cronIdentifier),
            'path'
        )->setValue(
            $cronExpression
        )->setPath(
            $this->getCronExpressionConfigPath($cronIdentifier)
        )->save();

        $this->configValueFactory->create()->load(
            $this->getCronRunModelConfigPath($cronIdentifier),
            'path'
        )->setValue(
            $cronRunModel
        )->setPath(
            $this->getCronRunModelConfigPath($cronIdentifier)
        )->save();

        return $this;
    }

    /**
     * Remove cronjob from database
     *
     * @param $cronIdentifier
     * @return $this
     */
    public function removeCronjob($cronIdentifier)
    {
        $this->configValueFactory->create()
            ->load($this->getCronExpressionConfigPath($cronIdentifier), 'path')->delete();
        $this->configValueFactory->create()
            ->load($this->getCronRunModelConfigPath($cronIdentifier), 'path')->delete();

        return $this;
    }

    /**
     *
     * Remove cronjobs "like" from database,
     * $cronIdentifier should contain %
     *
     * @param $cronIdentifier
     *
     * @return $this
     */
    public function removeCronjobsLike($cronIdentifier)
    {
        if (empty($cronIdentifier)) {
            return $this;
        }

        $configTable = $this->resourceConnection->getTableName('core_config_data');
        $connection = $this->resourceConnection->getConnection();
        $connection->delete($configTable, ['path LIKE ?' => self::CRON_PATH_PREFIX . $cronIdentifier]);

        return $this;
    }

    /**
     * Get config path to save cron expression in
     *
     * @param $cronIdentifier
     * @return string
     */
    protected function getCronExpressionConfigPath($cronIdentifier)
    {
        return self::CRON_PATH_PREFIX . $cronIdentifier . '/schedule/cron_expr';
    }

    /**
     * Get config path to save cron run model in
     *
     * @param $cronIdentifier
     * @return string
     */
    protected function getCronRunModelConfigPath($cronIdentifier)
    {
        return self::CRON_PATH_PREFIX . $cronIdentifier . '/run/model';
    }
}
