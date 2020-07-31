<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Customer\Model\Address;
use Riki\SubscriptionPage\Helper\Data;
use Magento\Config\Model\ResourceModel\Config;

class SubProfileNextDeliveryHelper extends AbstractHelper
{
    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_LAST_TIME_RUN   = 'di_data_export_setup/data_cron_subscription_next_delivery/cron_last_time_run';

    protected $_directoryList;
    protected $_datetime;
    protected $_addressModel;
    protected $_subscriptionPageHelper;
    const DS = '/';
    protected   $_resourceConfig;

    /**
     * SubProfileNextDeliveryHelper constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param DirectoryList $directoryList
     * @param DateTime $dateTime
     * @param Address $addressModel
     * @param Data $subscriptionPageHelper
     * @param Config $resourceConfig
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        DirectoryList $directoryList,
        DateTime $dateTime,
        Address $addressModel,
        Data $subscriptionPageHelper,
        Config $resourceConfig
    ){
        parent::__construct($context);
        $this->_subscriptionPageHelper = $subscriptionPageHelper;
        $this->_directoryList = $directoryList;
        $this->_datetime = $dateTime;
        $this->_addressModel = $addressModel;
        $this->_resourceConfig = $resourceConfig;
    }

    /**
     * @param $name
     * @param $log
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function backupLog($name,$log)
    {

        $varDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $fileSystem = new File();
        $backupFolder = self::DS.'log'.self::DS.'BiExportData';
        $localPath = $varDir.$backupFolder;

        if(!$fileSystem->isDirectory($localPath)){
            if(!$fileSystem->createDirectory($localPath)){
                $log->info(__('Can not create dir file').$localPath);
                return;
            }
        }
        $fileLog = $varDir.self::DS.'log'.self::DS.$name.'.log';
        $newLog = $varDir.$backupFolder. self::DS . $name . '_'.$this->_datetime->date('YmdHis').'.log';
        if($fileSystem->isWritable($localPath) && $fileSystem->isExists($fileLog))
        {
            $fileSystem->rename($fileLog,$newLog);
        }
    }

    /**
     * SetLastRunToCron
     *
     * @param $time
     */
    public function setLastRunToCron($time){
        $this->_resourceConfig->saveConfig(self::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_LAST_TIME_RUN,$time,'default',0);
    }
}