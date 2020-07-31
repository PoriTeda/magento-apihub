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
namespace Riki\CvsPayment\Logger\Handler;

use Magento\Framework\App\Filesystem\DirectoryList;
use Monolog\Logger;

/**
 * Class Primary
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Logger
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Primary extends \Magento\Framework\Logger\Handler\Base
{
    protected $fileName = '';

    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    protected $logIdentifier = '';

    /**
     * File
     *
     * @var \Magento\Framework\Filesystem
     */
    protected $file;

    /**
     * Primary constructor.
     *
     * @param \Magento\Framework\Filesystem                 $file          file
     * @param \Magento\Framework\Filesystem\DriverInterface $filesystem    fileSys
     * @param string                                        $filePath      filePath
     * @param string                                        $logIdentifier logId
     */
    public function __construct(
        \Magento\Framework\Filesystem $file,
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
        $filePath = '',
        $logIdentifier = 'Riki_CvsPayment_Log'
    ) {
        $this->file = $file;
        $this->logIdentifier = $logIdentifier;

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

        return $this->logIdentifier . DIRECTORY_SEPARATOR
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
}
