<?php
namespace Riki\ThirdPartyImportExport\Plugin\ThirdPartyImportExport\Cron\Order\Import;

use Riki\ThirdPartyImportExport\Api\ConfigInterface;

class Files
{
    /**
     * @var \Riki\Framework\Helper\Sftp
     */
    protected $sftpHelper;

    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;

    /**
     * Files constructor.
     *
     * @param \Riki\Framework\Helper\Sftp $sftpHelper
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     */
    public function __construct(
        \Riki\Framework\Helper\Sftp $sftpHelper,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
    ) {
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->sftpHelper = $sftpHelper;
    }

    /**
     * Prepare files which will be imported
     *
     * @param \Riki\ThirdPartyImportExport\Cron\Order\Import1 $subject
     *
     * @return array
     */
    public function beforeExecute(\Riki\ThirdPartyImportExport\Cron\Order\Import1 $subject)
    {
        if (!$subject->getInitialized()) {
            return [];
        }

        $remoteDir = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->orderImport()
            ->sftp()
            ->remotePath();
        if (strpos($remoteDir, $this->sftpHelper->dirSep()) !== 0) {
            $remoteDir = rtrim($this->sftpHelper->getDefaultDir(), $this->sftpHelper->dirSep()) . $this->sftpHelper->dirSep() . $remoteDir;
        }
        $this->sftpHelper->cd($remoteDir);

        $files = [];
        $patterns = [];
        $patterns['order'] = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->orderImport()
            ->sftp()
            ->remoteFileOrder();
        $patterns['order_detail'] = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->orderImport()
            ->sftp()
            ->remoteFileOrderDetail();
        $patterns['shipping'] = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->orderImport()
            ->sftp()
            ->remoteFileShipping();
        $patterns['shipping_detail'] = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->orderImport()
            ->sftp()
            ->remoteFileShippingDetail();

        foreach ($patterns as $key => $pattern) {
            $remoteFiles = $this->sftpHelper->filter($pattern);
            foreach ($remoteFiles as $remoteFile) {
                $file = [];
                $file['importer'] = $key;
                $file['remote_file'] = $remoteFile;
                $file['local_file'] = $subject->getTmpStorageDir() . DIRECTORY_SEPARATOR . $file['remote_file'] . '.' . time();
                if (!$this->sftpHelper->read($file['remote_file'], $file['local_file'])) {
                    $file['status'] = 'error'; //should use constant
                    $files[] = $file;
                    continue;
                }
                $file['status'] = 'success'; //should use constant
                $files[] = $file;
            }
        }

        $subject->setFiles($files);

        return [];
    }

    /**
     * Move file to backup folder
     *
     * @param \Riki\ThirdPartyImportExport\Cron\Order\Import1 $subject
     * @param $result
     *
     * @return mixed
     */
    public function afterExecute(\Riki\ThirdPartyImportExport\Cron\Order\Import1 $subject, $result)
    {
        if (!$subject->getInitialized()) {
            return $result;
        }

        $remoteDir = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->orderImport()
            ->sftp()
            ->remotePath();
        if (strpos($remoteDir, $this->sftpHelper->dirSep()) !== 0) {
            $remoteDir = rtrim($this->sftpHelper->getDefaultDir(), $this->sftpHelper->dirSep()) . $this->sftpHelper->dirSep() . $remoteDir;
        }

        $this->sftpHelper->cd($remoteDir);
        $pos = (int)strrpos(rtrim($remoteDir , $this->sftpHelper->dirSep()), $this->sftpHelper->dirSep());
        $baseDir = substr($remoteDir , 0, $pos);

        $errorDir = $baseDir . $this->sftpHelper->dirSep() . 'error'; // should use constant/config
        $doneDir = $baseDir . $this->sftpHelper->dirSep() . 'done'; // should use constant/config

        foreach ($subject->getFiles() as $file) {
            if (!isset($file['remote_file']) || !isset($file['status'])) {
                continue;
            }

            if ($file['status'] == 'success') {
                $this->sftpHelper->mv(
                    $remoteDir . $this->sftpHelper->dirSep(). $file['remote_file'],
                    $doneDir . $this->sftpHelper->dirSep() . $file['remote_file']
                );
                continue;
            }

            $this->sftpHelper->mv(
                $remoteDir . $this->sftpHelper->dirSep(). $file['remote_file'],
                $errorDir . $this->sftpHelper->dirSep() . $file['remote_file']
            );
        }

        return $result;
    }

}