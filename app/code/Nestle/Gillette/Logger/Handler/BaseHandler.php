<?php
namespace Nestle\Gillette\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Logger;

class BaseHandler extends Base
{
    protected $fileName = '/var/log/gillette_api.log';
    protected $loggerType = Logger::INFO;
    /**
     * BaseHandler constructor.
     *
     * @param DriverInterface $filesystem
     * @param null $filePath
     * @param null $fileName
     * @param int $loggerType
     */
    public function __construct(
        DriverInterface $filesystem,
        $filePath = null,
        $fileName = null,
        $loggerType = Logger::DEBUG
    ) {
        $this->fileName = $fileName;
        $this->loggerType = $loggerType;
        parent::__construct($filesystem, $filePath);
    }
}
