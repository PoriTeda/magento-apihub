<?php
namespace Riki\ThirdPartyImportExport\Plugin\ThirdPartyImportExport\Cron\Order\Import;

use Riki\ThirdPartyImportExport\Api\ConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Encryption\EncryptorInterface;

class Initialize
{
    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;

    /**
     * @var \Riki\Framework\Helper\Sftp
     */
    protected $sftpHelper;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;
    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;

    /**
     * Initialize constructor.
     *
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     * @param \Riki\Framework\Helper\Sftp $sftpHelper
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper,
        \Riki\Framework\Helper\Sftp $sftpHelper,
        EncryptorInterface $encryptor
    ) {
        $this->filesystem = $filesystem;
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->sftpHelper = $sftpHelper;
        $this->_encryptor = $encryptor;
    }

    /**
     * Initialize environment before execute cron
     *
     * @param \Riki\ThirdPartyImportExport\Cron\Order\Import1 $subject
     *
     * @return array
     */
    public function beforeExecute(\Riki\ThirdPartyImportExport\Cron\Order\Import1 $subject)
    {
        foreach (get_class_methods($this) as $method) {
            if (strpos($method, 'init') !== 0) {
                continue;
            }

            if (!$this->$method($subject)) {
                $subject->setInitialized(false);
                return [];
            }
        }

        $subject->setInitialized(true);
        return [];
    }

    /**
     * Initialize sftp
     *
     * @param \Riki\ThirdPartyImportExport\Cron\Order\Import1 $subject
     *
     * @return bool
     */
    public function initSftp(\Riki\ThirdPartyImportExport\Cron\Order\Import1 $subject)
    {
        $params = [
            'host' => $this->scopeConfigHelper->read(ConfigInterface::class)
                ->orderImport()
                ->sftp()
                ->host(),
            'port' => $this->scopeConfigHelper->read(ConfigInterface::class)
                ->orderImport()
                ->sftp()
                ->port(),
            'username' => $this->scopeConfigHelper->read(ConfigInterface::class)
                ->orderImport()
                ->sftp()
                ->username(),
            'password' =>
                 $this->_encryptor->decrypt(
                     $this->scopeConfigHelper->read(ConfigInterface::class)
                     ->orderImport()
                     ->sftp()
                     ->password())
                
        ];

        if ($params != array_filter($params)) {
            // config missing, not execute
            return false;
        }
        try {
            $params['host'] = isset($params['port'])
                ? $params['host'] . ':' . $params['port']
                : $params['host'];
            $this->sftpHelper->open($params);
        } catch (\Exception $e) {
            $subject->getLogger()->critical($e);
            return false;
        }

        $dirs = [
            'remote' => $this->scopeConfigHelper->read(ConfigInterface::class)
                ->orderImport()
                ->sftp()
                ->remotePath()
        ];
        if (strpos($dirs['remote'], $this->sftpHelper->dirSep()) !== 0) {
            $dirs['remote'] = rtrim($this->sftpHelper->getDefaultDir(), $this->sftpHelper->dirSep()) . $this->sftpHelper->dirSep() . $dirs['remote'];
        }

        $pos = (int)strrpos(rtrim($dirs['remote'], $this->sftpHelper->dirSep()), $this->sftpHelper->dirSep());
        $baseDir = substr($dirs['remote'], 0, $pos);
        $dirs['done'] = $baseDir . $this->sftpHelper->dirSep() . 'done'; // should use constant or config
        $dirs['error'] = $baseDir . $this->sftpHelper->dirSep() . 'error'; // should use constant or config
        foreach ($dirs as $dir) {
            if ($this->sftpHelper->isDirExist($dir)) {
                continue;
            }

            if (!$this->sftpHelper->mkdir($dir)) {
                $subject->getLogger()->critical(sprintf('The directory %s does not exists on sftp server', $dir));
                return false;
            }
        }

        return true;
    }

    /**
     * Initialize temporary storage
     *
     * @param \Riki\ThirdPartyImportExport\Cron\Order\Import1 $subject
     *
     * @return bool
     */
    public function initTmpStorageDir(\Riki\ThirdPartyImportExport\Cron\Order\Import1 $subject)
    {
        $localStorageDir = 'CronOrderImport'; // should use const/config
        $dir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        if (!$dir->isDirectory($localStorageDir)
            && !$dir->create($localStorageDir)
        ) {
            $subject->getLogger()->critical(sprintf('The directory %s does not exists on local server.', $dir->getAbsolutePath($localStorageDir)));
            return false;
        }

        if (!$dir->isWritable($localStorageDir)) {
            $dir->changePermissions($localStorageDir, 0777);
        }

        if (!$dir->isWritable($localStorageDir)) {
            $subject->getLogger()->critical(sprintf('The directory %s does not writable on local server.', $dir->getAbsolutePath($localStorageDir)));
            return false;
        }

        $subject->setTmpStorageDir($dir->getAbsolutePath($localStorageDir));

        return true;
    }
}