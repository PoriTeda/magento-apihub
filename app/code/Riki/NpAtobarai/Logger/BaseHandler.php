<?php
namespace Riki\NpAtobarai\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Logger;

class BaseHandler extends \Magento\Framework\Logger\Handler\Base
{
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
