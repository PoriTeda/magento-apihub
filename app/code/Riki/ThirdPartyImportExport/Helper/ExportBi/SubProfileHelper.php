<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Customer\Model\Address;
use Riki\SubscriptionPage\Helper\Data;
use Magento\Config\Model\ResourceModel\Config;

class SubProfileHelper extends AbstractHelper
{
    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_LOCAL_PATH = 'di_data_export_setup/data_cron_subscription_profile/folder_local';
    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_LAST_TIME_RUN = 'di_data_export_setup/data_cron_subscription_profile/cron_last_time_run';
    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_SFTP_PATH = 'di_data_export_setup/data_cron_subscription_profile/folder_ftp';

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

    public function getDetailAddress($addressId)
    {
        $arrResult = array();
        if ($addressId) {
            $addressObj = $this->_addressModel->load($addressId);
            if ($addressObj) {
                $arrResult['postcode'] = $addressObj->getPostcode();
                $arrResult['region_code'] = $addressObj->getRegionCode();
                $arrResult['city'] = $addressObj->getCity();
                $arrResult['street'] = $addressObj->getStreet()[0];
                $arrResult['telephone'] = $addressObj->getTelephone();
            } else {
                $arrResult['postcode'] = '';
                $arrResult['region_code'] = '';
                $arrResult['city'] = '';
                $arrResult['street'] = '';
                $arrResult['telephone'] = '';
            }
        } else {
            $arrResult['postcode'] = '';
            $arrResult['region_code'] = '';
            $arrResult['city'] = '';
            $arrResult['street'] = '';
            $arrResult['telephone'] = '';
        }
        return $arrResult;
    }

    public function getPlanType($courseId)
    {
        if ($this->_subscriptionPageHelper->getSubscriptionType($courseId) == 'hanpukai') {
            return 1;
        } else {
            return 0;
        }
    }

    public function getSFTPPathExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_SFTP_PATH,$storeScope);
        return $LocalPath;
    }

    public function setLastRunToCron($time){
        $this->_resourceConfig->saveConfig(self::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_LAST_TIME_RUN,$time,'default',0);
    }
}