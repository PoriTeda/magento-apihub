<?php

namespace Riki\ThirdPartyImportExport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    /**
     * Shipping exporter configuration : enable/disable
     */
    const CONFIG_SE_ENABLE = 'thirdpartyimportex/secommon/shipmentexport_enable';
    /**
     * Shipping exporter configuration : ftp ip
     */
    const CONFIG_SE_FTP_IP = 'thirdpartyimportex/seftp/shipmentexport_ftp_id';
    /**
     * Shipping exporter configuration :ftp port
     */
    const CONFIG_SE_FTP_PORT = 'thirdpartyimportex/seftp/shipmentexport_ftp_port';
    /**
     * Shipping exporter configuration : ftp user
     */
    const CONFIG_SE_FTP_USER = 'thirdpartyimportex/seftp/shipmentexport_ftp_user';
    /**
     * Shipping exporter configuration : ftp pass
     */
    const CONFIG_SE_FTP_PASS = 'thirdpartyimportex/seftp/shipmentexport_ftp_pass';
    /**
     * Shipping exporter configuration : sftp export
     */
    const XML_PATH_FTP_CSV = 'thirdpartyimportex/thirdpartyimportex_location/csvexport_order_folder_ftp';
    /**
     * Shipping exporter configuration :  export local
     */
    const XML_PATH_LOCAL_CSV = 'thirdpartyimportex/thirdpartyimportex_location/csvexport_order_folder_local';
    /**
     * Shipping exporter configuration : email template
     */
    const CONFIG_SE_EMAIL_TEMPLATE = 'thirdpartyimportex/seemail/shipmentexport_email_template';
    /**
     * Shipping exporter configuration : email alert
     */
    const CONFIG_SE_EMAIL_ALERT = 'thirdpartyimportex/seemail/shipmentexport_email_alert';
    const CONFIG_ORDER_IMPORT_ANCHOR_DATE = 'order_import/common/anchor_date';
    const CONFIG_ORDER_IMPORT_X_YEAR = 'order_import/common/x_year';
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected  $_posFactory;
    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory
     */

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory

    ) {
        $this->_scopeConfig = $context;
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_posFactory = $posFactory;
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
     * Check whether or not the module output is enabled in Configuration
     *
     * @return bool
     */
    public function isEnable()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $isEnabled = $this->scopeConfig->getValue(self::CONFIG_SE_ENABLE, $storeScope);
        return $isEnabled;
    }

    /**
     * Get order paging value config
     *
     * @return mixed
     */
    public function getSftpHost()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $fptId  = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_IP, $storeScope);
        return $fptId;
    }

    /**
     * @return mixed
     */
    public function getSftpPort()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $ftpPort = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_PORT,$storeScope);
        return $ftpPort;
    }

    /**
     * @return mixed
     */
    public function getSftpUser()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $ftpUser = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_USER,$storeScope);
        return $ftpUser;
    }

    /**
     * @return mixed
     */
    public function getSftpPass()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $ftpPass = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_PASS,$storeScope);
        return $ftpPass;
    }

    public function getAnchorDate()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $anchorDate = $this->scopeConfig->getValue(self::CONFIG_ORDER_IMPORT_ANCHOR_DATE, $storeScope);
        return $anchorDate;
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
    public function getEmailAlert()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $emailAlert = $this->scopeConfig->getValue(self::CONFIG_SE_EMAIL_ALERT,$storeScope);
        return explode(';',$emailAlert);
    }
    /**
     * @return mixed
     */
    public function getEmailTemplate()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $template = $this->scopeConfig->getValue(self::CONFIG_SE_EMAIL_TEMPLATE, $storeScope);
        return $template;

    }

    /**
     * @return mixed
     */
    public function getSenderEmail()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/email',$storeScope);
    }

    /**
     * @return mixed
     */
    public function getSenderName()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/name',$storeScope);
    }
    /**
     * @param $emailTemplateVariables
     * @return $this
     */
    public function generateTemplate($emailTemplateVariables)
    {
        $senderInfo = [
            'name' => $this->getSenderName() , 'email' => $this->getSenderEmail()
        ];
        $this->_transportBuilder->setTemplateIdentifier($this->getEmailTemplate())->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo($this->getEmailAlert());
        return $this;
    }
    /**
     * @param $emailTemplateVariables
     */
    public function sendMailShipmentExporting($emailTemplateVariables)
    {
        $this->inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables);
        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }

    public function getConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue($path,$storeScope);
    }
}