<?php
namespace Riki\DelayPayment\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem;

/**
 * Class Locker
 * @package Riki\DelayPayment\Helper
 */
class Locker
{
    const LOG_PATH = 'lock/cancel_authorization';
    /**
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * @var Filesystem
     */
    protected $fileSystem;
    /**
     * @var File
     */
    protected $file;

    /**
     * Locker constructor.
     * @param DirectoryList $directoryList
     * @param File $file
     * @param Filesystem $fileSystem
     */
    public function __construct(
        DirectoryList $directoryList,
        File $file,
        Filesystem $fileSystem
    ) {
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->fileSystem = $fileSystem;
    }

    /**
     * Get lock file
     *      this lock file is used to tracking that system have same process is running
     *
     * @return string
     */
    public function getLockFile()
    {
        return self::LOG_PATH . DIRECTORY_SEPARATOR . '.lock';
    }
    /**
     * before run Capture schedule - check same process is running
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initialLocker()
    {
        $baseDir = $this->directoryList->getPath(
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
        /*flag to check tmp folder can create new dir or is writable*/
        $validateLockFolder = $this->validateLockFolder($baseDir. DIRECTORY_SEPARATOR .self::LOG_PATH);
        if ($validateLockFolder) {
            /*tmp file to ensure that system do not run same mulit process at the same time*/
            $lockFile = $this->getLockFile();
            if ($this->isExists($lockFile)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Please wait, system have a same process is running and haven’t finish yet.')
                );
            } else {
                $this->createFile($lockFile);
            }
        }
    }
    /**
     * validate lock folder - can create new directory or is writable
     *
     * @param $path
     * @return bool
     * @throws \Exception
     */
    public function validateLockFolder($path)
    {
        if (!$this->file->isDirectory($path)) {
            if (!$this->file->createDirectory($path)) {
                throw new LocalizedException(__('Can not create dir file %1', $path));
            }
        } else {
            if (!$this->file->isWritable($path)) {
                throw new LocalizedException(__('The folder %1 have to change permission to 755', $path));
            }
        }
        return true;
    }
    /**
     * Check file is exists or not
     *
     * @param $file
     * @return bool
     */
    public function isExists($file)
    {
        $readInterface = $this->fileSystem->getDirectoryRead(
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
        return $readInterface->isExist($file);
    }

    /**
     * @param $file
     */
    public function createFile($file)
    {
        $writeInterface = $this->fileSystem->getDirectoryWrite(
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
        if (!$writeInterface->isExist($file)) {
            $writeInterface->create($file);
        }
    }
    /**
     * Delete file
     *
     */
    public function deleteLockFile()
    {
        $file = $this->getLockFile();
        $writeInterface = $this->fileSystem->getDirectoryWrite(
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
        if ($writeInterface->isExist($file)) {
            $writeInterface->delete($file);
        }
    }
}
