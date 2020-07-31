<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

use Magento\Framework\App\Helper\AbstractHelper;

class PromotionHelper extends AbstractHelper
{
    const XML_PATH_FTP_CSV = 'di_data_export_setup/data_cron_promotion/csvexport_order_folder_ftp';
    const XML_PATH_REPORT_CSV = 'di_data_export_setup/data_cron_promotion/csvexport_promotion_folder_ftp_report';
    const XML_PATH_LOCAL_CSV = 'di_data_export_setup/data_cron_promotion/csvexport_order_folder_local';
    const XML_CONFIG_LAST_CRON = 'di_data_export_setup/data_cron_promotion/bi_last_run_to_cron';
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
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        return $this->_storeManager->getStore();
    }

    /**
     * @return string
     */
    public function getLocalPath()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $path = $this->scopeConfig->getValue(self::XML_PATH_LOCAL_CSV,$storeScope);
        $path = ltrim($path, '/');
        $path = rtrim($path, '/');
        return $path;
    }

    /**
     * @return string
     */
    public function getFtpPath()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $path = $this->scopeConfig->getValue(self::XML_PATH_FTP_CSV,$storeScope);
        $path = ltrim($path, '/');
        $path = rtrim($path, '/');
        return $path;
    }

    /**
     * @return string
     */
    public function getReportPath()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $path = $this->scopeConfig->getValue(self::XML_PATH_REPORT_CSV,$storeScope);
        $path = ltrim($path, '/');
        $path = rtrim($path, '/');
        return $path;
    }

    /**
     * @return mixed
     */
    public function getLastCronRun()
    {
        $LocalPath = $this->scopeConfig->getValue(self::XML_CONFIG_LAST_CRON,'default');
        return $LocalPath;
    }

    /**
     * @param string $time
     */
    public function setLastCronRun($time)
    {
        $this->_resourceConfig->saveConfig(self::XML_CONFIG_LAST_CRON, $time,'default',0);
    }
}