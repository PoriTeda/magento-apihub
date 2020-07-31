<?php
namespace Riki\ThirdPartyImportExport\Logger\Handler;

class ExportCvsOrder extends \Magento\Framework\Logger\Handler\Base
{
    const LOG_DIR_NAME = 'ExportCvsOrderLog';

    protected $fileName = 'export_cvs_order.log';

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_file;

    /**
     * ExportCvsOrder constructor.
     * @param \Magento\Framework\Filesystem $file
     * @param \Magento\Framework\Filesystem\DriverInterface $filesystem
     * @param string $filePath
     */
    public function __construct(
        \Magento\Framework\Filesystem $file,
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
        $filePath = ''
    )
    {
        $this->_file = $file;

        $filePath = $this->getAbsolutePath();
        $this->fileName = 'log' . date('YmdHis') . '.log';

        parent::__construct($filesystem, $filePath);
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getAbsolutePath()
    {
        $logDir = $this->_file->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::LOG);

        $path = $this->getRelativePath();
        if (!$logDir->isExist($path)) {
            $logDir->create($path);
        }

        return $logDir->getAbsolutePath($path);
    }

    /**
     * Get relative path
     *
     * @return string
     */
    public function getRelativePath()
    {
        $year = date('Y');
        $month = date('m');
        $day = date('d');

        return self::LOG_DIR_NAME . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR . $month . DIRECTORY_SEPARATOR . $day . DIRECTORY_SEPARATOR;
    }

    /**
     * Get file name
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }
}
