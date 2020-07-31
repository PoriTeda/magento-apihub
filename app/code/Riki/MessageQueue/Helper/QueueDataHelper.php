<?php


namespace Riki\MessageQueue\Helper;


use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\MaintenanceMode;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;

/**
 * Class QueueDataHelper
 * @package Riki\MessageQueue\Helper
 */
class QueueDataHelper
{
    /**
     * @var Filesystem\Directory\WriteInterface
     */
    private $flagDir;

    const HIDDEN_FLAG_FILENAME = '.magento-consumer-stop.flag';
    const FLAG_FILENAME = 'magento-consumer-stop.flag';

    /**
     * Maintenance flag dir
     */
    const FLAG_DIR = DirectoryList::VAR_DIR;
    /**
     * @var MaintenanceMode
     */
    private $maintenanceMode;

    /**
     * QueueDataHelper constructor.
     * @param Filesystem $filesystem
     * @param MaintenanceMode $maintenanceMode
     * @throws FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        MaintenanceMode $maintenanceMode)
    {
        $this->flagDir = $filesystem->getDirectoryWrite(self::FLAG_DIR);
        $this->maintenanceMode = $maintenanceMode;
    }

    /**
     * @return bool
     */
    public function isDisable()
    {
        return $this->maintenanceMode->isOn() ||
            $this->flagDir->isExist(self::FLAG_FILENAME) ||
            $this->flagDir->isExist(self::HIDDEN_FLAG_FILENAME);
    }

    /**
     * @param bool $isDisable
     * @return $this
     * @throws FileSystemException
     */
    public function setDisable($isDisable = true)
    {
        if ($isDisable) {
            $this->flagDir->touch(self::FLAG_FILENAME);
            $this->flagDir->touch(self::HIDDEN_FLAG_FILENAME);
        } else {
            if ($this->flagDir->isExist(self::FLAG_FILENAME)) {
                $this->flagDir->delete(self::FLAG_FILENAME);
            }

            if ($this->flagDir->isExist(self::HIDDEN_FLAG_FILENAME)) {
                $this->flagDir->delete(self::HIDDEN_FLAG_FILENAME);
            }
        }

        return $this;
    }
}