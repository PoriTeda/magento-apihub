<?php
/**
 * Shipment Exporter
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShipmentExporter\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\ShipmentExporter\Helper;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Sales\Api\ShipmentRepositoryInterface;

/**
 * Class Data
 *
 * @category  RIKI
 * @package   Riki\ShipmentExporter\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Data extends AbstractHelper
{
    /**
     * Shipping exporter configuration : enable/disable
     */
    const CONFIG_SE_ENABLE = 'shipmentexporter/secommon/shipmentexport_enable';
    /**
     * Enable or disable logger
     */
    const CONFIG_SE_ENABLE_LOGGER = 'shipmentexporter/secommon/shipmentexport_logger_enable';
    /**
     * Warehouses will be export
     */
    const CONFIG_SE_WAREHOUSES = 'shipmentexporter/secommon/warehouses';
    /**
     * Shipping exporter configuration : ftp ip
     */
    const CONFIG_SE_FTP_IP = 'setting_sftp/setup_ftp/ftp_id';
    /**
     * Shipping exporter configuration :ftp port
     */
    const CONFIG_SE_FTP_PORT = 'setting_sftp/setup_ftp/ftp_port';
    /**
     * Shipping exporter configuration : ftp user
     */
    const CONFIG_SE_FTP_USER = 'setting_sftp/setup_ftp/ftp_user';
    /**
     * Shipping exporter configuration : ftp pass
     */
    const CONFIG_SE_FTP_PASS = 'setting_sftp/setup_ftp/ftp_pass';
    /**
     * Shipping exporter configuration : ftp folder
     */
    const CONFIG_SE_FTP_FOLDER = 'shipmentexporter/selocation/shipmentexport_shipment_folder';

    const CONFIG_MAX_CUSTOMER_LENGTH = 102;
    /**
     *
     */
    const CONFIG_SE_FTP_FOLDER_COPY = 'shipmentexporter/selocation/shipmentexport_shipment_backup';
    /**
     * Shipping exporter configuration : ftp local sap folder
     */
    const CONFIG_SE_LOCAL_SAP_FOLDER = 'shipmentexporter/selocation/shipmentexport_local_sap_folder';
    /**
     * Shipping exporter configuration : ftp local sap copy folder
     */
    const CONFIG_SE_LOCAL_SAP_COPY_FOLDER = 'shipmentexporter/selocation/shipmentexport_local_sap_copy_folder';
    /**
     *
     */
    const CONFIG_SE_EMAIL_ENABLE = 'shipmentexporter/seemail/enable';
    /**
     * Shipping exporter configuration : email alert
     */
    const CONFIG_SE_EMAIL_ALERT = 'shipmentexporter/seemail/shipmentexport_email_alert';
    /**
     * Shipping exporter configuration : email subject
     */
    const CONFIG_SE_EMAIL_SUBJECT = 'shipmentexporter/seemail/shipmentexport_email_subject';

    /**
     * Shipping exporter configuration : email template
     */
    const CONFIG_SE_EMAIL_TEMPLATE = 'shipmentexporter/seemail/shipmentexport_email_template';
    /**
     * Shipping exporter configuration : ph code
     */

    const CONFIG_SE_ZSIM_PH_CODE = 'shipmentexporter/zsim/shipmentexport_phcode';
    /**
     * Shipping exporter configuration : product material
     */
    const CONFIG_SE_ZSIM_MATERIAL = 'shipmentexporter/zsim/shipmentexport_material';

    const CONFIG_SE_BUFFER_DAY = 'shipleadtime/shipping_buffer_days/shipping_couriers_common_buffer';

    const CONFIG_SE_B2B_PREFECTURES = 'shipmentexporter/b2b/prefecturelist';

    const CONFIG_TIME_CONNECT_SFTP = 5;

    const CONFIG_LIMIT_ORDERS_IN_SHIPMENT_CREATOR = 'shipmentexporter/secommon/limit_order';

    const CONFIG_LIMIT_SHIMENT_EXPORTING_EXP1= 'shipmentexporter/seexp/shipmentexport_cron_exp1_limit';

    const CONFIG_LIMIT_SHIMENT_EXPORTING_EXP2= 'shipmentexporter/seexp/shipmentexport_cron_exp2_limit';

    const CONFIG_LIMIT_SHIMENT_EXPORTING_EXP3= 'shipmentexporter/seexp/shipmentexport_cron_exp3_limit';

    const CONFIG_CUTTING_BYTE = '8bit';

    /**
     * @var
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var
     */
    protected $tempId;
    /**
     * @var
     */
    protected $posFactory;
    /**
     *
     */
    protected $emailHelper;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;
    /**
     * @var
     */
    protected $shipmentRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteria;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;
    /**
     * @var File
     */
    protected $fileObject;
    /**
     * @var
     */
    protected $productFactory;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;
    /**
     * @var \Riki\ShipmentExporter\Logger\LoggerShip
     */
    protected $logger;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory
     * @param Email $emailHelper
     * @param DirectoryList $directoryList
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Filesystem $filesystem
     * @param File $fileObject
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Riki\ShipmentExporter\Logger\LoggerShip $loggerShip
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory,
        \Riki\ShipmentExporter\Helper\Email $emailHelper,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Driver\File $fileObject,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Riki\ShipmentExporter\Logger\LoggerShip $loggerShip
    ) {
        $this->scopeConfig = $context;
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->posFactory = $posFactory;
        $this->emailHelper = $emailHelper;
        $this->directoryList = $directoryList;
        $this->shipmentRepository = $shipmentRepository;
        $this->searchCriteria = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->fileSystem = $filesystem;
        $this->fileObject = $fileObject;
        $this->productFactory = $productFactory;
        $this->encryptor = $encryptor;
        $this->logger = $loggerShip;
    }

    /**
     * Return store
     *
     * @return Store
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
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
     * @return mixed
     */
    public function getLimitOrders()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $isEnabled = $this->scopeConfig->getValue(self::CONFIG_LIMIT_ORDERS_IN_SHIPMENT_CREATOR, $storeScope);
        $isEnabled = $isEnabled ? $isEnabled: 100;
        return $isEnabled;
    }
    /**
     * @return mixed
     */
    public function isEnableLogger()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $isEnabled = $this->scopeConfig->getValue(self::CONFIG_SE_ENABLE_LOGGER, $storeScope);
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
        $ftpPort = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_PORT, $storeScope);
        return $ftpPort;
    }

    /**
     * @return mixed
     */
    public function getSftpUser()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $ftpUser = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_USER, $storeScope);
        return $ftpUser;
    }

    /**
     * @return mixed
     */
    public function getSftpPass()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $ftpPass = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_PASS, $storeScope);
        return $this->encryptor->decrypt($ftpPass);
    }

    /**
     * @return mixed
     */
    public function getSftpLocation()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $shipmentFolder = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_FOLDER, $storeScope);
        return $shipmentFolder;
    }

    /**
     * @return mixed
     */
    public function getSftpLocationCopy()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $shipmentFolder = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_FOLDER_COPY, $storeScope);
        return $shipmentFolder;
    }
    /**
     * @return mixed
     */
    public function getLocalSapFolderOrigin()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $localSapFolder = $this->scopeConfig->getValue(self::CONFIG_SE_LOCAL_SAP_FOLDER, $storeScope);
        if (substr($localSapFolder, -1) !='/') {
            $localSapFolder.='/';
        }
        return $localSapFolder;
    }
    public function getLocalSapFolderOriginCopy()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $localSapFolder = $this->scopeConfig->getValue(self::CONFIG_SE_LOCAL_SAP_COPY_FOLDER, $storeScope);
        if (substr($localSapFolder, -1) !='/') {
            $localSapFolder.='/';
        }
        return $localSapFolder;
    }
    /**
     * @param $warehouse
     * @param bool $copy
     * @return string
     */
    public function getExportLocationFolder($warehouse, $copy = false)
    {
        $type = '1003';
        $folderName = '';
        switch ($warehouse) {
            case 'TOYO':
                $folderName = 'XRXT';
                break;
            case 'BIZEX':
                $folderName = 'XRXB';
                break;
            case 'HITACHI':
                $folderName = 'XRXH';
                break;
        }
        if ($copy) {
            $path = $this->getLocalSapFolderOriginCopy();
        } else {
            $path = $this->getLocalSapFolderOrigin();
        }
        return $path.$folderName.$type.'/remote/';
    }
    /**
     * @return mixed
     */
    public function getLocalSapCopyFolder()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $localSapCopyFolder = $this->scopeConfig->getValue(self::CONFIG_SE_LOCAL_SAP_COPY_FOLDER, $storeScope);
        return $localSapCopyFolder;
    }
    /**
     * @return mixed
     */
    public function getEmailEnable()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_SE_EMAIL_ENABLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }
    /**
     * @return mixed
     */
    public function getEmailAlert()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $emailAlert = $this->scopeConfig->getValue(self::CONFIG_SE_EMAIL_ALERT, $storeScope);
        if ($emailAlert) {
            return explode(';', $emailAlert);
        } else {
            return [];
        }
    }

    /**
     * @return array
     */
    public function getExportWarehouses()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $warehouseRaw = $this->scopeConfig->getValue(self::CONFIG_SE_WAREHOUSES, $storeScope);
        if ($warehouseRaw) {
            return explode(',', $warehouseRaw);
        } else {
            return [];
        }
    }
    /**
     * @return mixed
     */
    public function getEmailSubject()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $emailSubject = $this->scopeConfig->getValue(self::CONFIG_SE_EMAIL_SUBJECT, $storeScope);
        return $emailSubject;
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
        return $this->scopeConfig->getValue('trans_email/ident_support/email', $storeScope);
    }

    /**
     * @return mixed
     */
    public function getSenderName()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/name', $storeScope);
    }

    public function getBufferDay()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::CONFIG_SE_BUFFER_DAY, $storeScope);
    }
    /**
     * @return mixed
     */
    public function getMaterialType()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $materialType = $this->scopeConfig->getValue(self::CONFIG_SE_ZSIM_MATERIAL, $storeScope);
        if ($materialType) {
            return explode(',', $materialType);
        } else {
            return '';
        }
    }

    public function getPrefectureList()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $prefectures = $this->scopeConfig->getValue(self::CONFIG_SE_B2B_PREFECTURES, $storeScope);
        if ($prefectures) {
            return explode(',', $prefectures);
        } else {
            return false;
        }
    }
    /**
     * @return mixed
     */
    public function getPhcode()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $phcode = $this->scopeConfig->getValue(self::CONFIG_SE_ZSIM_PH_CODE, $storeScope);
        if ($phcode) {
            return explode(',', $phcode);
        } else {
            return '';
        }
    }
    /**
     * @param $emailTemplateVariables
     * @return $this
     */
    public function generateTemplate($emailTemplateVariables, $shipcreator = false)
    {
        $senderInfo = [
            'name' => $this->getSenderName() , 'email' => $this->getSenderEmail()
        ];

        if ($shipcreator) {
            $emailTemplate = 'general_email_support';
        } else {
            $emailTemplate = $this->getEmailTemplate();
        }
        $this->transportBuilder->setTemplateIdentifier($emailTemplate)
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->storeManager->getStore()->getId(),
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
        if ($this->getEmailEnable()) {
            $this->inlineTranslation->suspend();
            $this->generateTemplate($emailTemplateVariables);
            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        }
    }

    /**
     *
     */
    public function sendMailShipmentCreator()
    {
        if ($this->getEmailEnable()) {
            $path  = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
            $logFile = $path.'/log/shipment_creator.log';
            $reader = $this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
            if ($this->fileObject->isExists($logFile)) {
                $contentLog=  $reader->openFile('/log/shipment_creator.log', 'r')->readAll();
            } else {
                $contentLog = '';
            }
            $emailVariable = [
                'generalMessages'=> __("Shipment creator log: "),
                'generalReasons'=> $contentLog
            ];
            $this->inlineTranslation->suspend();
            $this->generateTemplate($emailVariable, true);
            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            if ($this->fileObject->isExists($logFile)) {
                $this->fileObject->deleteFile($logFile);
            }
        }
    }
    /**
     * @param $sftp
     * @return bool
     */
    public function checkSftpConnection($sftp, $copy = false)
    {
        $flag = true;
        $mess = '';
        $host = $this->getSftpHost();
        $port = $this->getSftpPort() ? $this->getSftpPort() : 22;
        $username = $this->getSftpUser();
        $password = $this->getSftpPass();
        $i=1;
        while ($i< self::CONFIG_TIME_CONNECT_SFTP) {
            try {
                $sftp->open(
                    [
                        'host' => $host.':'.$port,
                        'username' => $username,
                        'password' => $password,
                        'timeout' => 300
                     ]
                );
                    $flag = true;
                    break;
            } catch (\Exception $e) {
                $this->logger->info('Try to connect sftp '.$i.' times');
                $this->logger->info($e->getMessage());
                $i++;
            }
        }
        if ($i==self::CONFIG_TIME_CONNECT_SFTP) {
            $this->logger->info('Could not connect to SFTP with ');
            $flag = false;
        }
        if ($flag) { //if connection ok
            //check sfpt location
            if ($copy) {
                $location = $this->getSftpLocationCopy();
            } else {
                $location = $this->getSftpLocation();
            }
            $dirList = explode('/', $location);
            return $this->checkSfptDir($dirList, $sftp);
        }
        return [$flag, $mess] ;
    }

    /**
     * @param $dirList
     * @param $sftp
     * @return array
     */
    public function checkSfptDir($dirList, $sftp)
    {
        $flag = true;
        foreach ($dirList as $dir) {
            if (!$sftp->cd($dir)) {
                try {
                    if (!$sftp->mkdir(DIRECTORY_SEPARATOR . $dir)) {
                        $flag = false;
                        $mess = sprintf(__("Could not create the folder in Sftp : %s"), $sftp->pwd().$dir);
                        return [$flag,$mess];
                    }
                } catch (\Exception $e) {
                    $flag = false;
                    $mess = sprintf(__("Could not create the folder in Sftp : %s"), $sftp->pwd().$dir);
                    return [$flag,$mess];
                }
            }
        }
        return [$flag];
    }
    public function getHolidayOnSaturday($posCode)
    {
        $collection = $this->posFactory->create()->getPlaces();
        $collection->addFieldToFilter('store_code', ['store_code' => $posCode]);
        $collection->setPageSize(1);
        foreach ($collection->getItems() as $item) {
            return $item->getHolydaySettingSaturdayEnable();
        }
    }

    /**
     * @param $posCode
     * @return mixed
     */
    public function getHolidayOnSunday($posCode)
    {
        $collection = $this->posFactory->create()->getPlaces();
        $collection->addFieldToFilter('store_code', ['store_code' => $posCode]);
        $collection->setPageSize(1);
        foreach ($collection->getItems() as $item) {
            return $item->getHolydaySettingSundaysEnable();
        }
    }

    /**
     * @param $posCode
     * @param $day
     * @return bool
     */
    public function isSpecialHoliday($posCode, $day)
    {
        $collection = $this->posFactory->create()->getPlaces();
        $collection->addFieldToFilter('store_code', ['store_code' => $posCode]);
        $collection->setPageSize(1);
        foreach ($collection->getItems() as $item) {
            $data = $item->getSpecificHolidays();
        }
        $specialDay = explode(';', $data);
        if (in_array($day, $specialDay)) {
            return true;
        }
        return false;
    }

    /**
     * @param $storeId
     * @return mixed
     */

    public function getStoreLocale($storeId)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $localeCode  = $this->scopeConfig->getValue('general/locale/code', $storeScope, $storeId);
        return $localeCode;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return bool
     */

    public function checkZSIM(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $flagZsimYes = 0;
        $flagZsimNo = 0;
        $flagShipmentZsim = false;
        $products = $shipment->getAllItems();
        foreach ($products as $product) {
            $productDetail = $this->getDetailProduct($product->getProductId());
            $material = $productDetail->getMaterialType();
            $phcode = $productDetail->getPhCode();
            $flagZsim = $this->compareZSIM($material, $phcode);
            if ($flagZsim) {
                $flagZsimYes++;
            } else {
                $flagZsimNo++;
            }
        }
        //shipment has only zsim
        if ($flagZsimYes && !$flagZsimNo) {
            $flagShipmentZsim = true;
        } else {
            $flagShipmentZsim = false;
        }
        return $flagShipmentZsim;
    }

    /**
     * @param $productId
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function getDetailProduct($productId)
    {
        try {
            $product = $this->productRepository->getById($productId);
        } catch (\Exception $e) {
            $product = $this->productFactory->create();
            $this->logger->info($e->getMessage());
        }
        return $product;
    }

    /**
     * @param $material
     * @param $phcode
     * @return bool
     */
    public function compareZSIM($material, $phcode)
    {
        //get ZSIM configuration
        $configMaterial = $this->getMaterialType();
        $configPhcode = $this->getPhcode();
        if (!is_array($phcode)) {
            $phcode = [$phcode];
        }
        if (!$material && empty($phcode)) {
            return false;
        }
        //compare material
        $flagMaterial = false;
        if (empty($configMaterial) || !$material) {
            $flagMaterial= false;
        } else {
            $flagMaterial = in_array($material, $configMaterial);
        }
        //compare ph_code
        $flagPhcode = false;
        if (empty($configPhcode) || empty($phcode)) {
            $flagPhcode= false;
        } else {
            foreach ($configPhcode as $configcode) {
                foreach ($phcode as $pcode) {
                    if ($configcode == $pcode) {
                        $flagPhcode = true;
                        break;
                    }
                }
            }
        }
        return $flagMaterial || $flagPhcode;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function isOrderFullExported(\Magento\Sales\Model\Order $order)
    {
        $criteria = $this->searchCriteria->addFilter('order_id', $order->getId())
                                        ->addFilter('ship_zsim', 1, 'neq')
                                        ->create();
        $shipmentsCollection = $this->shipmentRepository->getList($criteria);
        $total = $shipmentsCollection->getTotalCount();
        $shipped = 0;
        if ($total) {
            foreach ($shipmentsCollection->getItems() as $shipitem) {
                if ($shipitem->getIsExported()) {
                    $shipped++;
                }
            }
        }
        if ($total && $total == $shipped) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function sendAdminEmailSftpNotConnect()
    {
        $emailVariables = [
            'generalMessages' => __("Could not access to Sftp account"),
            'generalReasons' =>sprintf(
                __("Sftp information, Host: %s, Username: %s, Password: %s, Port: %s"),
                $this->getSftpHost(),
                $this->getSftpUser(),
                $this->getSftpPass(),
                $this->getSftpPort()
            )
        ];
        $this->emailHelper->sendGeneralEmail($emailVariables);
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return bool
     */
    public function canExport(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        if ($shipment->getShipZsim()) {
            return false;
        }
        return true;
    }
    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return bool
     */
    public function checkWareHouse(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        if ($shipment->getWarehouse() == 'TOYO') {
            return true;
        }
        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function processUnableExportShipment(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        return $shipment;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param \Magento\Sales\Model\Order\Shipment\Item $item
     * @return bool
     */
    public function canExportItem(
        \Magento\Sales\Model\Order\Shipment $shipment,
        \Magento\Sales\Model\Order\Shipment\Item $item
    ) {
        if ($shipment && $item->getOrderItem()->getParentItemId()) {
            return false;
        }
        return true;
    }

    /**
     * @param $phone
     * @return mixed
     */
    public function formatPhone($phone)
    {
        return str_replace('-', '', $phone);
    }
    /**
     * @param $lastname
     * @param $firstname
     * @return string
     */
    public function formatCustomerName($lastname, $firstname)
    {
        $lastname = $this->convertEncode($lastname);
        $firstname = $this->convertEncode($firstname);
        $newCustomerName = $lastname.$firstname;
        if (mb_strlen($newCustomerName, self::CONFIG_CUTTING_BYTE) > self::CONFIG_MAX_CUSTOMER_LENGTH) {
        // cut lastname
            $cutNumber = mb_strlen($newCustomerName, self::CONFIG_CUTTING_BYTE) - self::CONFIG_MAX_CUSTOMER_LENGTH ;
            if ($cutNumber < mb_strlen($lastname, self::CONFIG_CUTTING_BYTE)) {
                $newLastname = $this->substringByByte(
                    $lastname,
                    mb_strlen($lastname, self::CONFIG_CUTTING_BYTE) - $cutNumber
                );
                return $newLastname.$firstname;
            } else {
                // cut firstname
                $newFirstName = $this->substringByByte(
                    $firstname,
                    mb_strlen($firstname, self::CONFIG_CUTTING_BYTE) - $cutNumber
                );
                return $lastname.$newFirstName;
            }
        }
        return $newCustomerName;
    }

    /**
     * @param $string
     * @param $length
     * @param string $encoding
     * @param string $padCharacter
     * @return bool|string
     */
    public function substringByByte($string, $length, $encoding = 'SHIFT-JIS', $padCharacter = ' ')
    {
        if ($length < 0) {
            $length = mb_strlen($string, self::CONFIG_CUTTING_BYTE) + $length;
        }

        $result = '';
        $nextCharacter = '';
        $offset = 0;
        while (mb_strlen($result . $nextCharacter, self::CONFIG_CUTTING_BYTE) <= $length) {
                $result .= mb_substr($string, $offset++, 1, $encoding);
                $nextCharacter = mb_substr($string, $offset, 1, $encoding);
        }
        if (mb_strlen($result, self::CONFIG_CUTTING_BYTE) > $length) {
                return false;
        } elseif (mb_strlen($result, self::CONFIG_CUTTING_BYTE) < $length) {
                $padStringLength = $length - mb_strlen($result, self::CONFIG_CUTTING_BYTE);

            for ($i = 0; $i < $padStringLength; $i++) {
                $result .= $padCharacter;
            }
        }
        return $result;
    }

    /**
     * @param $string
     * @return mixed|string
     */
    public function convertEncode($string)
    {
        $encode=mb_detect_encoding($string);
        $encode = $encode ? $encode : 'UTF-8';
        return mb_convert_encoding($string, "sjis-win", $encode);
    }

    /**
     * @param $number
     * @return mixed
     */
    public function getShipmentLimitation($number)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        switch ((int)($number)) {
            case 1:
                $path = self::CONFIG_LIMIT_SHIMENT_EXPORTING_EXP1;
                break;
            case 2:
                $path = self::CONFIG_LIMIT_SHIMENT_EXPORTING_EXP2;
                break;
            case 3:
                $path = self::CONFIG_LIMIT_SHIMENT_EXPORTING_EXP3;
                break;
            default:
                $path = self::CONFIG_LIMIT_SHIMENT_EXPORTING_EXP1;
                break;
        }
        return $this->scopeConfig->getValue($path, $storeScope);
    }
}
