<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Customer\Model\Address;
use Riki\SubscriptionPage\Helper\Data;
use Magento\Config\Model\ResourceModel\Config;

class SubProfileNextDeliveryOrderHelper extends AbstractHelper
{
    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_LOCAL_PATH = 'di_data_export_setup/data_cron_subscription_next_delivery/folder_local_profile';
    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_SFTP_PATH  = 'di_data_export_setup/data_cron_subscription_next_delivery/folder_ftp_profile';
    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_SFTP_REPORT_PATH  = 'di_data_export_setup/data_cron_subscription_next_delivery/folder_ftp_profile_report';

    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_VERSION_LOCAL_PATH = 'di_data_export_setup/data_cron_subscription_next_delivery/folder_local_profile_version';
    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_VERSION_SFTP_PATH  = 'di_data_export_setup/data_cron_subscription_next_delivery/folder_ftp_profile_version';
    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_VERSION_SFTP_REPORT_PATH  = 'di_data_export_setup/data_cron_subscription_next_delivery/folder_ftp_profile_version_report';


    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_CART_LOCAL_PATH = 'di_data_export_setup/data_cron_subscription_next_delivery/folder_local_order';
    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_CART_SFTP_PATH  = 'di_data_export_setup/data_cron_subscription_next_delivery/folder_ftp_order';
    const CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_CART_SFTP_REPORT_PATH  = 'di_data_export_setup/data_cron_subscription_next_delivery/folder_ftp_order_report';



    protected $_directoryList;
    protected $_datetime;
    protected $_addressModel;
    protected $_subscriptionPageHelper;
    const DS = '/';
    protected   $_resourceConfig;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected   $_searchCriteriaBuilder;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected   $_orderRepository;

    /**
     * SubProfileNextDeliveryOrderHelper constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param DirectoryList $directoryList
     * @param DateTime $dateTime
     * @param Address $addressModel
     * @param Data $subscriptionPageHelper
     * @param Config $resourceConfig
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        DirectoryList $directoryList,
        DateTime $dateTime,
        Address $addressModel,
        Data $subscriptionPageHelper,
        Config $resourceConfig,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ){
        parent::__construct($context);
        $this->_subscriptionPageHelper = $subscriptionPageHelper;
        $this->_directoryList = $directoryList;
        $this->_datetime = $dateTime;
        $this->_addressModel = $addressModel;
        $this->_resourceConfig = $resourceConfig;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_orderRepository = $orderRepository;
    }

    /**
     * BackupLog
     *
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
     * @return mixed
     */
    public function getPathLocalExportProfile()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_LOCAL_PATH,$storeScope);
        return $LocalPath;
    }

    /**
     * @return mixed
     */
    public function getPathLocalExportProfileCart()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_CART_LOCAL_PATH,$storeScope);
        return $LocalPath;
    }


    /**
     * @return mixed
     */
    public function getSFTPPathExportProfile()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_SFTP_PATH,$storeScope);
        return $LocalPath;
    }

    /**
     * @return mixed
     */
    public function getSFTPPathExportProfileVersion()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_VERSION_SFTP_PATH,$storeScope);
        return $LocalPath;
    }

    /**
     * @return mixed
     */
    public function getSFTPPathProfileReportExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_SFTP_REPORT_PATH,$storeScope);
        return $LocalPath;
    }

    /**
     * @return mixed
     */
    public function getSFTPPathProfileVersionReportExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_VERSION_SFTP_REPORT_PATH,$storeScope);
        return $LocalPath;
    }


    /**
     * @return mixed
     */
    public function getSFTPPathExportProfileCart()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_CART_SFTP_PATH,$storeScope);
        return $LocalPath;
    }
    /**
     * @return mixed
     */
    public function getSFTPPathProfileCartReportExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_CART_SFTP_REPORT_PATH,$storeScope);
        return $LocalPath;
    }


    /**
     * @param $subProfileData
     * @return null
     */
    public function getLastOrder($subProfileData){
        $order = null;

        if(isset($subProfileData['profile_id'])){

            $criteria = $this->_searchCriteriaBuilder->addFilter('subscription_profile_id', $subProfileData['profile_id'] )->create();

            $orderCollection = $this->_orderRepository->getList($criteria);

            foreach($orderCollection->getItems() as $oFirstOrderItem){
                $order = $oFirstOrderItem;
                break;
            }
        }


        return $order;
    }
}