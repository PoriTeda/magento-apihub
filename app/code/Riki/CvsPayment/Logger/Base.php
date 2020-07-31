<?php
/**
 * CvsPayment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\CvsPayment\Logger;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Base
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Logger
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Base extends \Magento\Framework\Logger\Monolog
{
    /**
     * File
     *
     * @var \Magento\Framework\Filesystem
     */
    protected $file;

    /**
     * Base constructor.
     *
     * @param \Magento\Framework\Filesystem $file       file
     * @param string                        $name       name
     * @param array                         $handlers   handlers
     * @param array                         $processors processors
     */
    public function __construct(
        \Magento\Framework\Filesystem $file,
        $name = 'BaseLogger',
        $handlers = [],
        $processors = []
    ) {
        $this->file = $file;
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * Get handler
     *
     * @return \Monolog\Handler\HandlerInterface
     */
    public function getPrimaryHandler()
    {
        $handlers = $this->getHandlers();
        foreach ($handlers as $handler)
        {
            if(get_class($handler) == "Riki\CvsPayment\Logger\Handler\Primary")
                return $handler;
        }
    }

    /**
     * Get current log file name
     *
     * @return string
     */
    public function getLogFileName()
    {
        /**
         * Handler
         *
         * @var \Riki\ThirdPartyImportExport\Logger\Handler\ExportCvsOrder $handler
         */
        $handler = $this->getPrimaryHandler();

        return $handler->getFileName();
    }

    /**
     * Get current log path
     *
     * @return string
     */
    public function getLogFilePath()
    {
        /**
         * Handler
         *
         * @var \Riki\ThirdPartyImportExport\Logger\Handler\ExportCvsOrder $handler
         */
        $handler = $this->getPrimaryHandler();

        return $handler->getRelativePath();
    }

    /**
     * Get log content
     *
     * @return string
     */
    public function getLogContent()
    {
        $logDir = $this->file->getDirectoryRead(DirectoryList::LOG);
        $path = $this->getLogFilePath() . $this->getLogFileName();
        if (!$logDir->isExist($path)) {
            return '';
        }
        $content = $logDir->readFile($path);

        return $content;
    }
}
