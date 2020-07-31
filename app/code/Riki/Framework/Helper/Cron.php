<?php
namespace Riki\Framework\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Cron
{
    /**
     * @var null
     */
    protected $lockFileName;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * Cron constructor.
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        \Magento\Framework\Filesystem\Driver\File $file,
        DirectoryList $directoryList
    ) {
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->lockFileName = null;
    }

    /**
     * @param $fileName
     * @return $this
     */
    public function setLockFileName($fileName)
    {
        $this->lockFileName = $fileName;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getLockFile()
    {
        if ($this->lockFileName) {
            return $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/' . $this->lockFileName;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        return $this->hasSameProcessRunning();
    }

    /**
     * @return bool|int
     */
    public function lockProcess()
    {
        return $this->createLockFile();
    }

    /**
     * @return Cron
     */
    public function unLockProcess()
    {
        return $this->removeLockFile();
    }

    /**
     * @return bool
     */
    private function hasSameProcessRunning()
    {
        if ($filePath = $this->getLockFile()) {
            try {
                return $this->file->isExists($filePath);
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * @return $this|int
     */
    private function createLockFile()
    {
        if ($filePath = $this->getLockFile()) {
            try {
                return $this->file->filePutContents($filePath, '');
            } catch (\Exception $e) {
                return $this;
            }
        }
    }

    /**
     * @return $this
     */
    private function removeLockFile()
    {
        if ($filePath = $this->getLockFile()) {
            try {
                $this->file->deleteFile($filePath);
            } catch (\Exception $e) {
                return $this;
            }
        }

        return $this;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getLockMessage()
    {
        return __('Please wait, system have a same process is running and haven\'t finish yet.');
    }
}