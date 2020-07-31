<?php

/**
 * Product:       Xtento_XtCore (2.1.0)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2017-08-16T08:52:13+00:00
 * File:          app/code/Xtento/XtCore/Cron/RegisterCronExecution.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\XtCore\Cron;

class RegisterCronExecution
{
    /**
     * @var \Xtento\XtCore\Model\ResourceModel\Config
     */
    protected $xtCoreConfig;

    /**
     * RegisterCronExecution constructor.
     * @param \Xtento\XtCore\Model\ResourceModel\Config $xtCoreConfig
     */
    public function __construct(
        \Xtento\XtCore\Model\ResourceModel\Config $xtCoreConfig
    ) {
        $this->xtCoreConfig = $xtCoreConfig;
    }

    /**
     * Register last cronjob execution
     *
     * @return void
     */
    public function execute()
    {
        $this->xtCoreConfig->saveConfig('xtcore/crontest/last_execution', time());
    }
}
