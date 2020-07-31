<?php
namespace Riki\ThirdPartyImportExport\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Sftp extends \Magento\Framework\App\Helper\AbstractHelper
{

    const TIMEOUT = 300;
    const STORAGE_DIR = 'riki_thirdpartyimportexport_order';
    const BACKUP_DIR = 'backup';
    const BACKUP_DIR_DONE = 'done';
    const BACKUP_DIR_ERROR = 'error';
    const FILE_EXTENSION = 'csv';

    protected $_host;
    protected $_port;
    protected $_username;
    protected $_password;
    protected $_initialized;
    protected $_storagePath;

    /**
     * @var \Magento\Framework\Filesystem\Io\Sftp
     */
    protected $_sftp;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_fileSystem;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File $filesystemDriver
     */
    protected $fileSystemDriver;

    /**
     * Sftp constructor.
     * @param \Magento\Framework\Filesystem\Io\Sftp $sftp
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Filesystem\Driver\File $filesystemDriver
     */
    public function __construct(
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem\Driver\File $filesystemDriver
    )
    {
        $this->_fileSystem = $filesystem;
        $this->_sftp = $sftp;
        $this->fileSystemDriver = $filesystemDriver;
        parent::__construct($context);
    }

    /**
     * Connect to sftp server
     *
     * @param $host
     * @param $port
     * @param $username
     * @param $password
     * @return bool|\Exception
     */
    public function connect($host, $port, $username, $password)
    {
        try {
            $this->_sftp->open([
                'host' => $host . ':' . $port,
                'username' => $username,
                'password' => $password,
                'timeout' => self::TIMEOUT
            ]);
        } catch (\Exception $e) {
            return $e;
        }

        $this->_host = $host;
        $this->_port = $port;
        $this->_username = $username;
        $this->_password = $password;
        $this->_initialized = true;

        return true;
    }

    /**
     * Execute cd command
     *
     * @param $path
     * @return bool
     */
    public function cd($path)
    {
        if (!$this->_initialized) {
            return false;
        }

        if (!$this->_sftp->cd($path)) {
            return false;
        }

        return true;
    }

    /**
     * Download a remote file to local file
     *
     * @param $file
     * @return bool|string
     */
    public function read($file)
    {
        $path = explode('/', $file);
        if (count($path) != 1) {
            $file = $path[count($path) - 1];
            unset($path[count($path) - 1]);
            $path = implode('/', $path);
            if (!$this->cd($path)) {
                return false;
            }
        }

        $localFile = $this->getStoragePath() . DIRECTORY_SEPARATOR . date('Y-m-d') . '_' . date('H-i-s') . '_' . $file;
        if (!$this->_sftp->read($file, $localFile)) {
            return false;
        }

        $tmpDir = $this->_fileSystem->getDirectoryWrite(DirectoryList::TMP);
        $tmpDir->changePermissions(str_replace($tmpDir->getAbsolutePath(), '', $localFile), 0777);

        return $localFile;
    }


    /**
     * Converts names for normalize & store
     *
     * @param string $name
     * @return string
     */
    protected function _underscore($name)
    {
        return strtolower(trim(preg_replace('/([A-Z]|[0-9]+)/', "_$1", preg_replace('/\s+/', '', $name)), '_'));
    }

    /**
     * Return list files match pattern
     *
     * @param $pattern
     * @return array
     */
    public function filter($pattern)
    {
        return preg_grep('/' . $pattern . '/', array_keys($this->_sftp->rawls()));
    }

    /**
     * Get path of where store copied file from sftp
     *
     * @return string
     */
    public function getStoragePath()
    {
        if ($this->_storagePath) {
            return $this->_storagePath;
        }

        $tmpDir = $this->_fileSystem->getDirectoryWrite(DirectoryList::TMP);
        if (!$tmpDir->isExist(self::STORAGE_DIR)) {
            $tmpDir->create(self::STORAGE_DIR);
            $tmpDir->changePermissions(self::STORAGE_DIR, 0777);
        }

        $this->_storagePath = $tmpDir->getAbsolutePath(self::STORAGE_DIR);

        return $this->_storagePath;
    }

    /**
     * back up a file
     *
     * @param $file
     */
    public function backup($file)
    {
        $fileName = $file;
        $path = explode('/', $file);
        if (count($path) != 1) {
            $fileName = $path[count($path) - 1];
            unset($path[count($path) - 1]);
            $path = implode('/', $path);
        }

        $this->_sftp->mv($file, $path . '/' . self::BACKUP_DIR . '/' . $fileName);
    }

    /**
     * back up a file
     *
     * @param $file
     */
    public function backupPostFix($file,$postFix)
    {
        $fileName = $file;
        $path = explode('/', $file);
        if (count($path) != 1) {
            $fileName = $path[count($path) - 1];
        }

        $fileNewName = $this->addPostFixFileName($fileName,$postFix);

        $this->_sftp->mv($fileName, '../'.self::BACKUP_DIR_DONE . '/' . $fileNewName);
    }

    /**
     * back up a file error
     *
     * @param $file
     */
    public function backupPostFixError($file,$postFix)
    {
        $fileName = $file;
        $path = explode('/', $file);
        if (count($path) != 1) {
            $fileName = $path[count($path) - 1];
        }

        $fileNewName = $this->addPostFixFileName($fileName,$postFix);

        $this->_sftp->mv($fileName, '../'.self::BACKUP_DIR_ERROR . '/' . $fileNewName);
    }


    /**
     * Initialize backup dir import cedyna
     * @param null $path
     * @return $this
     */
    public function initBackupImportCedyna($path = null)
    {
        if ($path) {
            $parentPath = $this->fileSystemDriver->getParentDirectory($path);
            $this->cd($parentPath);
        }

        $this->_sftp->mkdir('/' . self::BACKUP_DIR_DONE);
        $this->_sftp->mkdir('/' . self::BACKUP_DIR_ERROR);

        if($path && $parentPath){
            $mainPath = str_replace($parentPath,"",$path);
            $mainPath = ltrim($mainPath,'/');
            $this->cd($mainPath);
        }
        return $this;
    }

    /**
     * @param $sFileName
     * @param $postfix
     * @return mixed
     */
    public function addPostFixFileName($sFileName,$postFix){
        return str_replace(".csv",'_'.$postFix.".csv",$sFileName);
    }

    /**
     * Initialize backup dir
     * @param null $path
     * @return $this
     */
    public function initBackup($path = null)
    {
        if ($path) {
            $this->cd($path);
        }
        $this->_sftp->mkdir('/' . self::BACKUP_DIR);
        return $this;
    }
}
