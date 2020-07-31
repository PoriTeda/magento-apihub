<?php

namespace Riki\SapIntegration\Plugin\SapIntegration\Cron\Exporter\Returns;

use Riki\SapIntegration\Api\ConfigInterface;

class Sftp
{
    /**
     * @var array
     */
    protected $failedIds = [];

    /**
     * @var \Riki\Framework\Helper\Sftp
     */
    protected $sftpHelper;

    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;

    /**
     * Sftp constructor.
     *
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     *
     * @param \Riki\Framework\Helper\Sftp $sftpHelper
     */
    public function __construct(
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper,
        \Riki\Framework\Helper\Sftp $sftpHelper
    ) {
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->sftpHelper = $sftpHelper;
    }

    /**
     * Get failed ids
     *
     * @return array
     */
    public function getFailedIds()
    {
        return $this->failedIds;
    }

    /**
     * Export xml to sftp
     *
     * @param \Riki\SapIntegration\Cron\Exporter\Returns
     * @param $result
     *
     * @return mixed
     */
    public function afterExportToXml(\Riki\SapIntegration\Cron\Exporter\Returns $subject, $result)
    {
        if (!$result) {
            return $result;
        }

        if (!$this->sftpHelper->getDefaultDir()) {
            return $result;
        }

        $remoteDir = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->sapIntegration()
            ->exportRma()
            ->remoteDir();
        $path = $this->sftpHelper->getDefaultDir() . DIRECTORY_SEPARATOR . $remoteDir . DIRECTORY_SEPARATOR . $subject->getFileName();

        if (!$this->sftpHelper->write($path, $subject->getExportFile())) {
            $this->failedIds = array_unique(array_merge($this->failedIds, array_keys($subject->getBatchIds())));
        }

        return $result;
    }
}