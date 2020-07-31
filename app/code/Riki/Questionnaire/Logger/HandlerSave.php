<?php
namespace Riki\Questionnaire\Logger;

use Monolog\Logger;

class HandlerSave extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/question/questionnaire_save.log';
}