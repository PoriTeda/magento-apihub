<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

use Magento\Framework\App\Helper\AbstractHelper;

class OrderHelperPromotion extends AbstractHelper
{
    const XML_PATH_FTP_CSV = 'di_data_export_setup/data_cron_order_promotion/csvexport_order_folder_ftp';
    const XML_PATH_FTP_REPORT_CSV = 'di_data_export_setup/data_cron_order_promotion/csvexport_order_folder_report_ftp';
    const XML_PATH_LOCAL_CSV = 'di_data_export_setup/data_cron_order_promotion/csvexport_order_folder_local';
    const CONFIG_SE_EMAIL_TEMPLATE = 'thirdpartyimportex/seemail/shipmentexport_email_template';
    const CONFIG_SE_EMAIL_ALERT = 'thirdpartyimportex/seemail/shipmentexport_email_alert';
    const CONFIG_CRON_EXPORT_ORDER_RUN_LAST_TIME = 'di_data_export_setup/data_cron_order_promotion/csvexport_order_last_time_cron_run';

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected   $_resourceConfig;

    /**
     * OrderHelper constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig
    ) {
        parent::__construct($context);
        $this->_resourceConfig = $resourceConfig;
    }

    /**
     * @return mixed
     */
    public function getLocalPathExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::XML_PATH_LOCAL_CSV,$storeScope);
        return $LocalPath;
    }
    /**
     * @return mixed
     */
    public function getSFTPPathExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::XML_PATH_FTP_CSV,$storeScope);
        return $LocalPath;
    }
    /**
     * @return mixed
     */
    public function getSFTPPathReportExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::XML_PATH_FTP_REPORT_CSV,$storeScope);
        return $LocalPath;
    }
    public function getLastTimeCronRun()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::CONFIG_CRON_EXPORT_ORDER_RUN_LAST_TIME, $storeScope);
    }
    /**
     * @param $time
     */
    public function setLastRunToCron($time){
        $this->_resourceConfig->saveConfig(self::CONFIG_CRON_EXPORT_ORDER_RUN_LAST_TIME,$time,'default',0);
    }
}