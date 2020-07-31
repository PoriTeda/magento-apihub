<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;

class FileExportHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const DS = '/';
    /**
     * @var Filesystem
     */
    protected $_filesystem;
    /**
     * @var File
     */
    protected $_file;
    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        Filesystem $fileSystem,
        File $file,
        DirectoryList $directoryList
    ) {
        parent::__construct($context);
        $this->_filesystem = $fileSystem;
        $this->_file = $file;
        $this->_directoryList = $directoryList;

        if (defined('DS') === false) define('DS', DIRECTORY_SEPARATOR);
    }

    /**
     * Get root directory
     *
     * @return string
     */
    public function getRootDirectory()
    {
        return $this->_directoryList->getPath(DirectoryList::ROOT);
    }

    /**
     * get list file from directory
     *
     * @param $path
     * @return array
     */
    public function getListFileFromDirectory($path)
    {
        /*Create an instance of directory with read permissions*/
        $directory = $this->_filesystem->getDirectoryRead(DirectoryList::ROOT);

        return $directory->read(self::DS.$path.self::DS);
    }

    /**
     * Get file name from path
     *
     * @param $path
     * @return bool|mixed
     */
    public function getFileName($path)
    {
        $fileName = explode(self::DS, $path);
        if ($fileName) {
            return end($fileName);
        } else {
            return false;
        }
    }

    /**
     * move file from old path to new path
     *
     * @param $oldPath
     * @param $newPath
     */
    public function move($oldPath, $newPath)
    {
        if ($this->_file->isExists($oldPath)) {
            $this->_file->rename($oldPath,$newPath);
        }
    }

    /**
     * Create backup log file
     *
     * @param $name
     * @param $logTime
     * @return bool
     * @throws \Exception
     */
    public function backupLog($name, $logTime)
    {
        /*get var directory*/
        $varDir = $this->_directoryList->getPath(DirectoryList::VAR_DIR);

        /*create new file*/
        $fileSystem = new File();

        /*backup folder*/
        $backupFolder = 'log'.DS.'BiExportData';

        /*exactly backup directory*/
        $localPath = $varDir.DS.$backupFolder;

        /*create new folder if not exist*/
        if (!$fileSystem->isDirectory($localPath)) {
            if (!$fileSystem->createDirectory($localPath)) {
                throw new LocalizedException(__('Can not create dir file %1', $localPath));
            }
        }

        /*old log file*/
        $oldLog = $varDir.DS.'log'.DS.$name.'.log';

        /*if old log file is exists, backup log*/
        if ($fileSystem->isWritable($localPath) && $fileSystem->isExists($oldLog)) {
            /*new log file*/
            $newLog = $varDir.DS.$backupFolder.DS.$name.'_'.$logTime.'.log';

            /*move old log file to new path*/
            $fileSystem->rename($oldLog,$newLog);
        }
    }

    /**
     * validate export folder - can create new directory or is writable
     *
     * @param $path
     * @return bool
     * @throws \Exception
     */
    public function validateExportFolder($path)
    {
        if (!$this->_file->isDirectory($path)) {
            if (!$this->_file->createDirectory($path)) {
                throw new LocalizedException(__('Can not create dir file %1', $path));
            }
        } else {
            if (!$this->_file->isWritable($path)) {
                throw new LocalizedException(__('The folder %1 have to change permission to 755', $path));
            }
        }

        return true;
    }

    /**
     * Get file absolute path
     *
     * @param $file
     * @return string
     */
    public function getAbsolutePath($file)
    {
        $readInterface = $this->_filesystem->getDirectoryRead(DirectoryList::ROOT);
        return $readInterface->getAbsolutePath($file);
    }

    /**
     * Check file is exists or not
     *
     * @param $file
     * @return bool
     */
    public function isExists($file)
    {
        $readInterface = $this->_filesystem->getDirectoryRead(DirectoryList::ROOT);
        return $readInterface->isExist($file);
    }

    /**
     * create file - from root dir
     *
     * @param $file
     */
    public function createFile($file)
    {
        $writeInterface = $this->_filesystem->getDirectoryWrite(DirectoryList::ROOT);
        if (!$writeInterface->isExist($file)) {
            $writeInterface->create($file);
        }
    }

    /**
     * Delete file
     *
     * @param $file
     */
    public function deleteFile($file)
    {
        $writeInterface = $this->_filesystem->getDirectoryWrite(DirectoryList::ROOT);
        if ($writeInterface->isExist($file)) {
            $writeInterface->delete($file);
        }
    }

    /**
     * Get log file content
     *
     * @param $logFile
     * @return mixed
     */
    public function getLogContent($logFile)
    {
        $filePath = DS.'var'.DS.'log'.DS.$logFile.'.log';
        if ($this->_file->isExists($filePath)) {
            $reader = $this->_filesystem->getDirectoryRead(DirectoryList::ROOT);
            return $reader->openFile($filePath, 'r')->readAll();
        }
        return false;
    }


    /**
     * Remove BOM from a file
     *
     * @param string $sourceFile
     * @return $this
     */
    public function removeBom($sourceFile)
    {
        $varDirectory = $this->_filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $string =$varDirectory->readFile($varDirectory->getRelativePath($sourceFile));
        if ($string !== false && substr($string, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
            $string = substr($string, 3);
            $varDirectory->writeFile($varDirectory->getRelativePath($sourceFile), $string);
        }
        return $this;
    }


}