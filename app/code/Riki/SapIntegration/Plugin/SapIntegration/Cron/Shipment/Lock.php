<?php
namespace Riki\SapIntegration\Plugin\SapIntegration\Cron\Shipment;

use \Magento\Framework\App\Filesystem\DirectoryList;

class Lock
{
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Riki\SapIntegration\Plugin\SapIntegration\Cron\Shipment\EmailNotification
     */
    protected $emailNotificationPlugin;

    /**
     * Lock constructor.
     *
     * @param \Riki\SapIntegration\Plugin\SapIntegration\Cron\Shipment\EmailNotification $emailNotificationPlugin
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \Riki\SapIntegration\Plugin\SapIntegration\Cron\Shipment\EmailNotification $emailNotificationPlugin,
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->emailNotificationPlugin = $emailNotificationPlugin;
        $this->filesystem = $filesystem;
    }

    /**
     * Get lock path
     *
     * @return string
     */
    public function getLockPath()
    {
        return 'CronShipmentSapExport' . DIRECTORY_SEPARATOR . '.lock';
    }

    /**
     * Lock
     *
     * @param \Riki\SapIntegration\Cron\ShipmentV2 $subject
     * @param $result
     * @return mixed
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterIsEnabled(\Riki\SapIntegration\Cron\ShipmentV2 $subject, $result)
    {
        if ($result) {
            $logDir = $this->filesystem->getDirectoryWrite(DirectoryList::LOG);
            if ($logDir->isExist($this->getLockPath())) {
                $subject->getLogger()->critical(__('There are a cron job which haven’t finish yet.'));
                $this->emailNotificationPlugin->afterExecute($subject, false);

                throw new \Magento\Framework\Exception\LocalizedException(__('There are a cron job which haven’t finish yet.'));
            }

            $logDir->create($this->getLockPath());
        }

        return $result;
    }

    /**
     * Un-lock
     *
     * @param \Riki\SapIntegration\Cron\ShipmentV2 $subject
     * @param $result
     *
     * @return bool
     */
    public function afterExecute(\Riki\SapIntegration\Cron\ShipmentV2 $subject, $result)
    {
        $logDir = $this->filesystem->getDirectoryWrite(DirectoryList::LOG);
        $logDir->delete($this->getLockPath());

        return $result;
    }
}