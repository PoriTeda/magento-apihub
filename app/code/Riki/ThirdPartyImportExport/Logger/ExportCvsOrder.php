<?php
namespace Riki\ThirdPartyImportExport\Logger;

class ExportCvsOrder extends \Magento\Framework\Logger\Monolog
{
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_file;

    public function __construct(
        \Magento\Framework\Filesystem $file,
        \Riki\ThirdPartyImportExport\Logger\Handler\ExportCvsOrder $exportCvsOrderHandler,
        $name = 'ExportCvsOrderLogger',
        $handlers = [],
        $processors = []
    )
    {
        $this->_file = $file;

        $handlers = ['primary' => $exportCvsOrderHandler];

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

        return $handlers['primary'];
    }

    /**
     * Get current log file name
     *
     * @return string
     */
    public function getLogFileName()
    {
        /** @var \Riki\ThirdPartyImportExport\Logger\Handler\ExportCvsOrder $handler */
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
        /** @var \Riki\ThirdPartyImportExport\Logger\Handler\ExportCvsOrder $handler */
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
        $logDir = $this->_file->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::LOG);
        $path = $this->getLogFilePath() . $this->getLogFileName();
        if (!$logDir->isExist($path)) {
            return '';
        }
        $content = $logDir->readFile($path);

        return $content;
    }
}
