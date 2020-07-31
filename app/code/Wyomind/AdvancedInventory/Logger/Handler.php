<?php

namespace Wyomind\AdvancedInventory\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{

    public $fileName = '/var/log/AdvancedInventory-Assignation.log';
    public $loggerType = \Monolog\Logger::NOTICE;
}
