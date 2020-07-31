<?php
namespace Riki\SapIntegration\Plugin\SapIntegration\Cron\Shipment;

use Riki\SapIntegration\Api\ConfigInterface;

class Sftp
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
     * @var \Riki\SapIntegration\Plugin\SapIntegration\Cron\Exporter\Orders\Sftp
     */
    protected $sftpOrdersPlugin;

    /**
     * Sftp constructor.
     *
     * @param \Riki\SapIntegration\Plugin\SapIntegration\Cron\Exporter\Orders\Sftp $sftpOrdersPlugin
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     * @param \Riki\Framework\Helper\Sftp $sftpHelper
     */
    public function __construct(
        \Riki\SapIntegration\Plugin\SapIntegration\Cron\Exporter\Orders\Sftp $sftpOrdersPlugin,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper,
        \Riki\Framework\Helper\Sftp $sftpHelper
    ) {
        $this->sftpOrdersPlugin = $sftpOrdersPlugin;
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->sftpHelper = $sftpHelper;
    }

    /**
     * Initialize sftp connection
     *
     * @param \Riki\SapIntegration\Cron\ShipmentV2 $subject
     * @param $result
     *
     * @return bool
     */
    public function afterIsEnabled(\Riki\SapIntegration\Cron\ShipmentV2 $subject, $result)
    {
        if (!$result) {
            return $result;
        }

        $params = [
            'host' => $this->scopeConfigHelper->read(ConfigInterface::class)
                ->sapIntegration()
                ->sftp()
                ->host(),
            'port' => $this->scopeConfigHelper->read(ConfigInterface::class)
                ->sapIntegration()
                ->sftp()
                ->port(),
            'username' => $this->scopeConfigHelper->read(ConfigInterface::class)
                ->sapIntegration()
                ->sftp()
                ->username(),
            'password' => $this->scopeConfigHelper->read(ConfigInterface::class)
                ->sapIntegration()
                ->sftp()
                ->password()
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
                ->sapIntegration()
                ->exportShipment()
                ->remoteDir()
        ];

        if (strpos($dirs['remote'], $this->sftpHelper->dirSep()) !== 0) {
            $dirs['remote'] = rtrim($this->sftpHelper->getDefaultDir(), $this->sftpHelper->dirSep()) . $this->sftpHelper->dirSep() . $dirs['remote'];
        }

        foreach ($dirs as $dir) {
            if ($this->sftpHelper->isDirExist($dir)) {
                continue;
            }

            if (!$this->sftpHelper->mkdir($dir)) {
                $subject->getLogger()->critical(sprintf('The directory %s does not exists on sftp server', $dir));
                return false;
            }
        }

        return $result;
    }

    /**
     * Pass failed records
     *
     * @param \Riki\SapIntegration\Cron\ShipmentV2 $subject
     * @param $result
     *
     * @return array
     */
    public function afterExport(\Riki\SapIntegration\Cron\ShipmentV2 $subject, $result)
    {
        foreach ($this->sftpOrdersPlugin->getFailedIds() as $id) {
            $subject->trackFailed($id);
        }

        return $result;
    }
}
