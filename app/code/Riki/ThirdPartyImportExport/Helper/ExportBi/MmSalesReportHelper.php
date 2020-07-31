<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

use Magento\Framework\App\Helper\AbstractHelper;

class MmSalesReportHelper extends AbstractHelper
{
    const XML_PATH_FTP_CSV = 'di_data_export_setup/data_cron_sales_report/folder_ftp';
    const XML_PATH_FTP_REPORT_CSV = 'di_data_export_setup/data_cron_sales_report/folder_ftp_report';
    const XML_PATH_LOCAL_CSV = 'di_data_export_setup/data_cron_sales_report/folder_local';
    const CONFIG_CRON_EXPORT_MM_RUN_LAST_TIME = 'di_data_export_setup/data_cron_sales_report/time_crontab';
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected   $_resourceConfig;
    /**
     * Enquiry constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig

    ) {
        $this->_scopeConfig = $context;
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_resourceConfig = $resourceConfig;
    }
    /**
     * Return store
     *
     * @return Store
     */
    public function getStore()
    {
        return $this->_storeManager->getStore();
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
    public function getSFTPPathExportReport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::XML_PATH_FTP_REPORT_CSV,$storeScope);
        return $LocalPath;
    }

    public function getLastTimeToRun()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::CONFIG_CRON_EXPORT_MM_RUN_LAST_TIME, $storeScope);
    }
    /**
     * @param $time
     */
    public function setLastRunToCron($time){
        $this->_resourceConfig->saveConfig(self::CONFIG_CRON_EXPORT_MM_RUN_LAST_TIME,$time,'default',0);
    }
}