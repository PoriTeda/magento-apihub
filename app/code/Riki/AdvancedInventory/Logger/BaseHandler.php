<?php
namespace Riki\AdvancedInventory\Logger;
#class Logger
use Monolog\Logger;

class BaseHandler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/logger.log';

    public function __construct(
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
        $filePath = null,
        $fileName = ""
    ) {
        if (!empty($fileName)) {
            $this->fileName = $fileName;
        }
        parent::__construct($filesystem, $filePath);
    }

    /**
     * get name of log file
     * @return mixed
     */
    public function getLogFileName()
    {
        return str_replace('/var/log/', '', $this->fileName);
    }

}