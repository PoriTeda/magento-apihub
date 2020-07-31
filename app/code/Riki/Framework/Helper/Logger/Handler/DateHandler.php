<?php
namespace Riki\Framework\Helper\Logger\Handler;

use Magento\Framework\App\Filesystem\DirectoryList;

class DateHandler extends \Magento\Framework\Logger\Handler\Base implements HandlerInterface
{
    /**
     * @var string
     */
    protected $fileName = '';

    /**
     * @var string
     */
    protected $identifier = '';

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $file;

    /**
     * DateHandler constructor.
     * @param \Magento\Framework\Filesystem $file
     * @param \Magento\Framework\Filesystem\DriverInterface $filesystem
     * @param string $filePath
     * @param string $identifier
     * @param string $fileName
     */
    public function __construct(
        \Magento\Framework\Filesystem $file,
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
        $filePath = '',
        $identifier = '',
        $fileName = ''
    ) {
        $this->file = $file;
        $this->identifier = $identifier;

        if (strpos($fileName, '.log') === (strlen($fileName) - strlen('.log'))) {
            $this->fileName = $fileName;
        } else {
            $this->fileName = $fileName . '_' . date('Y-m-d_H-i') . '.log';
        }

        $filePath = $this->getAbsolutePath();
        parent::__construct($filesystem, $filePath);
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getAbsolutePath()
    {
        $logDir = $this->file->getDirectoryWrite(DirectoryList::LOG);

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

        return $this->identifier . DIRECTORY_SEPARATOR
            . $year . DIRECTORY_SEPARATOR
            . $month . DIRECTORY_SEPARATOR
            . $day . DIRECTORY_SEPARATOR;
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

    /**
     * Get log content
     *
     * @return string
     */
    public function getLogContent()
    {
        $logDir = $this->file->getDirectoryRead(DirectoryList::LOG);
        $path = $this->getRelativePath() . $this->getFileName();
        if (!$logDir->isExist($path)) {
           return '';
        }

        return $logDir->readFile($path);
    }
}