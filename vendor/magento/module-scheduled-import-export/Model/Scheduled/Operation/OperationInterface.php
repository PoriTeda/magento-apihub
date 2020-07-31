<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Scheduled operation interface
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\ScheduledImportExport\Model\Scheduled\Operation;

/**
 * Interface \Magento\ScheduledImportExport\Model\Scheduled\Operation\OperationInterface
 *
 */
interface OperationInterface
{
    /**
     * Run operation through cron
     *
     * @param \Magento\ScheduledImportExport\Model\Scheduled\Operation $operation
     * @return bool
     */
    public function runSchedule(\Magento\ScheduledImportExport\Model\Scheduled\Operation $operation);

    /**
     * Initialize operation model from scheduled operation
     *
     * @param \Magento\ScheduledImportExport\Model\Scheduled\Operation $operation
     * @return object operation instance
     */
    public function initialize(\Magento\ScheduledImportExport\Model\Scheduled\Operation $operation);

    /**
     * Log debug data to file.
     *
     * @param mixed $debugData
     * @return object
     */
    public function addLogComment($debugData);

    /**
     * Return human readable debug trace.
     *
     * @return array
     */
    public function getFormatedLogTrace();
}
