<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Customer\Model\Address;
use Riki\SubscriptionPage\Helper\Data;
use Magento\Config\Model\ResourceModel\Config;

class SubProfileNextDeliveryShipmentHelper extends AbstractHelper
{
    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_SHIPMENT_LOCAL_PATH = 'di_data_export_setup/data_cron_subscription_next_delivery/folder_local_shipment';
    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_SHIPMENT_SFTP_PATH  = 'di_data_export_setup/data_cron_subscription_next_delivery/folder_ftp_shipment';
    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_SHIPMENT_SFTP_REPORT_PATH  = 'di_data_export_setup/data_cron_subscription_next_delivery/folder_ftp_report_shipment';

    protected $_directoryList;
    protected $_datetime;
    protected $_addressModel;
    protected $_subscriptionPageHelper;
    const DS = '/';
    protected   $_resourceConfig;

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
        $newLog = $varDir .$backupFolder. self::DS . $name . '_'.$this->_datetime->date('YmdHis').'.log';
        if($fileSystem->isWritable($localPath) && $fileSystem->isExists($fileLog))
        {
            $fileSystem->rename($fileLog,$newLog);
        }
    }

    /**
     * @return mixed
     */
    public function getPathLocalSubShipmentExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_SHIPMENT_LOCAL_PATH,$storeScope);
        return $LocalPath;
    }

    /**
     * @return mixed
     */
    public function getPathLocalSubShipmentDetailExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_SHIPMENT_LOCAL_PATH,$storeScope);
        if($LocalPath){
            $LocalPath = rtrim($LocalPath,'/').'_detail';
        }
        return $LocalPath;
    }

    /**
     * @return mixed
     */
    public function getSFTPPathExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_SHIPMENT_SFTP_PATH,$storeScope);
        return $LocalPath;
    }
    /**
     * @return mixed
     */
    public function getSFTPPathReportExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_SHIPMENT_SFTP_REPORT_PATH,$storeScope);
        return $LocalPath;
    }
}