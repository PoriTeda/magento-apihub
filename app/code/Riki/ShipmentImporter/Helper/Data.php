<?php
/**
 * Riki Shipment Importer
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShipmentImporter\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\ShipmentImporter\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\Sftp;
use Magento\Framework\Filesystem\Driver\File;
use Riki\NpAtobarai\Model\Config\Source\TransactionPaymentStatus;
use Riki\Sales\Model\Order\Payment;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatus;
use Magento\Sales\Model\Order\InvoiceDocumentFactory;
use Magento\Sales\Model\Order\InvoiceRepository;
use Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus as OrderPaymentStatus;

/**
 * Class Data
 *
 * @category  RIKI
 * @package   Riki\ShipmentImporter\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Data extends AbstractHelper
{
    const SI_COMMON_ENABLE = 'shipmentimporter/common/enable';
    const SI_COMMON_ENABLE_LOGGER = 'shipmentimporter/common/enable_logger';

    const SI_SFTP_HOST = 'setting_sftp/setup_ftp/ftp_id';
    const SI_SFTP_PORT = 'setting_sftp/setup_ftp/ftp_port';
    const SI_SFTP_USER = 'setting_sftp/setup_ftp/ftp_user';
    const SI_SFTP_PASS = 'setting_sftp/setup_ftp/ftp_pass';

    const SI_LOCATION_IMPORT_1501 = 'shipmentimporter/location/import1501';
    const SI_LOCATION_IMPORT_1601 = 'shipmentimporter/location/import1601';
    const SI_LOCATION_IMPORT_1701 = 'shipmentimporter/location/import1701';
    const SI_LOCATION_IMPORT_1801 = 'shipmentimporter/location/import1801';
    const SI_LOCATION_IMPORT_1901 = 'shipmentimporter/location/import1901';

    const SI_LOCATION_IMPORT_1502 = 'shipmentimporter/location/import1502';
    const SI_LOCATION_IMPORT_1602 = 'shipmentimporter/location/import1602';
    const SI_LOCATION_IMPORT_1702 = 'shipmentimporter/location/import1702';
    const SI_LOCATION_IMPORT_1802 = 'shipmentimporter/location/import1802';
    const SI_LOCATION_IMPORT_1902 = 'shipmentimporter/location/import1902';

    const SI_LOCATION_IMPORT_1504 = 'shipmentimporter/location/import1504';
    const SI_LOCATION_IMPORT_1604 = 'shipmentimporter/location/import1604';
    const SI_LOCATION_IMPORT_1704 = 'shipmentimporter/location/import1704';
    const SI_LOCATION_IMPORT_1804 = 'shipmentimporter/location/import1804';
    const SI_LOCATION_IMPORT_1904 = 'shipmentimporter/location/import1904';

    const SI_LOCATION_IMPORT_1507 = 'shipmentimporter/location/import1507';

    const SI_EXP_1501_01 = 'shipmentimporter/expression/expression1501_01';
    const SI_EXP_1501_02 = 'shipmentimporter/expression/expression1501_02';
    const SI_EXP_1501_03 = 'shipmentimporter/expression/expression1501_03';
    const SI_EXP_1502_01 = 'shipmentimporter/expression/expression1502_01';
    const SI_EXP_1503_01 = 'shipmentimporter/expression/expression1503_01';
    const SI_EXP_1503_02 = 'shipmentimporter/expression/expression1503_02';
    const SI_EXP_1503_03 = 'shipmentimporter/expression/expression1503_03';
    const SI_EXP_1504_01 = 'shipmentimporter/expression/expression1504_01';
    const SI_EXP_1505_01 = 'shipmentimporter/expression/expression1505_01';
    const SI_EXP_1505_02 = 'shipmentimporter/expression/expression1505_02';
    const SI_EXP_1507_01 = 'shipmentimporter/expression/expression1507_01';

    const SI_EMAIL_ENABLE = 'shipmentimporter/email/enable';
    const SI_EMAIL_SHIPPED_OUT_XDAYS = 'shipmentimporter/email/shippedout_xday';
    const SI_EMAIL_RECEIVER = 'shipmentimporter/email/receiver';
    const SI_EMAIL_SUBJECT = 'shipmentimporter/email/subject';
    const SI_EMAIL_TEMPLATE = 'shipmentimporter/email/template';
    const SI_EMAIL_TEMPLATE_TRACKING = 'shipmentimporter/email/shipment_tracking';
    const SI_EMAIL_TEMPLATE_TRACKING_SPOT = 'shipmentimporter/email/template_tracking_spot';
    const SI_EMAIL_TEMPLATE_TRACKING_HANPUKAI = 'shipmentimporter/email/template_tracking_hanpukai';
    const SI_EMAIL_TEMPLATE_TRACKING_SUBSCRIPTION = 'shipmentimporter/email/template_tracking_subscription';
    const SI_EMAIL_TEMPLATE_ERROR_COD = 'shipmentimporter/email/template_error_cod';
    const SI_EMAIL_TEMPLATE_ERROR_CVS = 'shipmentimporter/email/template_error_cvs';

    const SI_PATTERN_1501 = 'shipmentimporter/pattern/pattern1501';
    const SI_PATTERN_1601 = 'shipmentimporter/pattern/pattern1601';
    const SI_PATTERN_1701 = 'shipmentimporter/pattern/pattern1701';
    const SI_PATTERN_1801 = 'shipmentimporter/pattern/pattern1801';
    const SI_PATTERN_1901 = 'shipmentimporter/pattern/pattern1901';

    const SI_PATTERN_1502 = 'shipmentimporter/pattern/pattern1502';
    const SI_PATTERN_1602 = 'shipmentimporter/pattern/pattern1602';
    const SI_PATTERN_1702 = 'shipmentimporter/pattern/pattern1702';
    const SI_PATTERN_1802 = 'shipmentimporter/pattern/pattern1802';
    const SI_PATTERN_1902 = 'shipmentimporter/pattern/pattern1902';

    const SI_PATTERN_1504 = 'shipmentimporter/pattern/pattern1504';
    const SI_PATTERN_1604 = 'shipmentimporter/pattern/pattern1604';
    const SI_PATTERN_1704 = 'shipmentimporter/pattern/pattern1704';
    const SI_PATTERN_1804 = 'shipmentimporter/pattern/pattern1804';
    const SI_PATTERN_1904 = 'shipmentimporter/pattern/pattern1904';

    const SI_PATTERN_1505 = 'shipmentimporter/pattern/pattern1505';
    const SI_PATTERN_1507 = 'shipmentimporter/pattern/pattern1507';
    const CONFIG_TIME_CONNECT_SFTP = 5;

    const FREE_PAYMENT = 'free';
    /**
     * @var
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
     * @var
     */
    protected $tempId;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_varDirectory;
    /**
     * @var
     */
    protected $_directoryList;
    /**
     * @var \Riki\ShipmentExporter\Helper\Email
     */
    protected $_emailHelper;
    /**
     * @var
     */
    protected $_readerCSV;
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;
    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transactionFactory;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory
     */
    protected $_orderHistory;
    /**
     * @var \Riki\Sales\Helper\OrderStatus
     */
    protected $_orderStatusHelper;
    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $_shipmentRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriticalBuilder;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_fileSystem;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $_shipmentCollectionFactory;
    /**
     * @var \Riki\Shipment\Setup\UpgradeSchema
     */
    protected $_systemUpgrade;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;
    /**
     * @var \Riki\ShippingCarrier\Helper\CarrierHelper
     */
    protected $_carrierHelper;

    /**
     * @var InvoiceDocumentFactory
     */
    protected $invoiceDocumentFactory;
    /**
     * @var InvoiceRepository
     */
    protected $invoiceRepository;
    /**
     * @var \Bluecom\Paygent\Helper\HistoryHelper
     */
    protected $paygentHistoryHelper;

    /**
     * @var \Riki\NpAtobarai\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Riki\ShipmentExporter\Helper\Email $emailHelper
     * @param \Magento\Framework\Filesystem $filesystem
     * @param DirectoryList $directoryList
     * @param \Magento\Framework\File\Csv $csvReader
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $orderHistory
     * @param \Riki\Sales\Helper\OrderStatus $orderStatus
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory
     * @param \Riki\Shipment\Setup\UpgradeSchema $upgradeSchema
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Riki\ShippingCarrier\Helper\CarrierHelper $carrierHelper
     * @param InvoiceDocumentFactory $invoiceDocumentFactory
     * @param InvoiceRepository $invoiceRepository
     * @param \Bluecom\Paygent\Helper\HistoryHelper $paygentHistoryHelper
     * @param \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Riki\ShipmentExporter\Helper\Email $emailHelper,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\File\Csv $csvReader,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $orderHistory,
        \Riki\Sales\Helper\OrderStatus $orderStatus,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        \Riki\Shipment\Setup\UpgradeSchema $upgradeSchema,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Riki\ShippingCarrier\Helper\CarrierHelper $carrierHelper,
        InvoiceDocumentFactory $invoiceDocumentFactory,
        InvoiceRepository $invoiceRepository,
        \Bluecom\Paygent\Helper\HistoryHelper $paygentHistoryHelper,
        \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository
    ) {
        $this->_storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_emailHelper = $emailHelper;
        $this->_varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->_directoryList = $directoryList;
        $this->_readerCSV  = $csvReader;
        $this->_invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->_orderHistory = $orderHistory;
        $this->_orderStatusHelper = $orderStatus;
        $this->_shipmentRepository = $shipmentRepository;
        $this->_searchCriticalBuilder = $searchCriteriaBuilder;
        $this->_fileSystem = $filesystem;
        $this->_shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->_systemUpgrade = $upgradeSchema;
        $this->_encryptor = $encryptor;
        $this->_carrierHelper = $carrierHelper;
        $this->invoiceDocumentFactory = $invoiceDocumentFactory;
        $this->invoiceRepository = $invoiceRepository;
        $this->paygentHistoryHelper = $paygentHistoryHelper;
        $this->transactionRepository = $transactionRepository;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function isEnable()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $isEnabled = $this->scopeConfig->getValue(self::SI_COMMON_ENABLE, $storeScope);
        return $isEnabled;
    }

    /**
     * @return mixed
     */
    public function isEnableLogger()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::SI_COMMON_ENABLE_LOGGER, $storeScope);

    }
    /**
     * @return mixed
     */
    public function getPattern1501()
    {
        return $this->scopeConfig->getValue(self::SI_PATTERN_1501,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getPattern1601()
    {
        return $this->scopeConfig->getValue(self::SI_PATTERN_1601,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
    /**
     * @return mixed
     */
    public function getPattern1502()
    {
        return $this->scopeConfig->getValue(self::SI_PATTERN_1502,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
    /**
     * @return mixed
     */
    public function getPattern1602()
    {
        return $this->scopeConfig->getValue(self::SI_PATTERN_1602,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
    /**
     * @return mixed
     */
    public function getPattern1503()
    {
        return $this->scopeConfig->getValue(self::SI_PATTERN_1503,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @param null $b
     * @return mixed
     */
    public function getPattern1504($b=null)
    {
        if($b) {
            return $this->scopeConfig->getValue(self::SI_PATTERN_1504b,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        }else
        {
            return $this->scopeConfig->getValue(self::SI_PATTERN_1504,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        }
    }
    /**
     * @return mixed
     */

    public function getPattern1505()
    {
        return $this->scopeConfig->getValue(self::SI_PATTERN_1505,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getPattern1507()
    {
        return $this->scopeConfig->getValue(self::SI_PATTERN_1507,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
    /**
     * @return mixed
     */
    public function getPatternRegex($patternNumber)
    {
        $pattern = 'shipmentimporter/pattern/pattern'.$patternNumber;
        return $this->scopeConfig->getValue($pattern,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getSftpHost()
    {
        return $this->scopeConfig->getValue(self::SI_SFTP_HOST,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getSftpPort()
    {
        return $this->scopeConfig->getValue(self::SI_SFTP_PORT,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getSftpUser()
    {
        return $this->scopeConfig->getValue(self::SI_SFTP_USER,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
    /**
     * @return mixed
     */
    public function getSftpPass()
    {
        $pass =  $this->scopeConfig->getValue(self::SI_SFTP_PASS,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        return $this->_encryptor->decrypt($pass);
    }
    /**
     * @param $location
     * @return string
     */
    public function validLocationDS($location)
    {
        if($location[0] == DIRECTORY_SEPARATOR)
        {
            return $location;
        }
        else
        {
            return DIRECTORY_SEPARATOR.$location;
        }
    }

    /**
     * @return mixed
     */
    public function getLocation1501()
    {
        $location =  $this->scopeConfig->getValue(self::SI_LOCATION_IMPORT_1501,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

        $location = $this->validLocationDS($location);
        return $this->validSftpLocation($location);
    }

    /**
     * @return string
     */
    public function getLocation1601()
    {
        $location =  $this->scopeConfig->getValue(self::SI_LOCATION_IMPORT_1601,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

        $location = $this->validLocationDS($location);
        return $this->validSftpLocation($location);
    }
    /**
     * @return mixed
     */
    public function getLocation1502()
    {

        $location =   $this->scopeConfig->getValue(self::SI_LOCATION_IMPORT_1502,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $location = $this->validLocationDS($location);
        return $this->validSftpLocation($location);
    }
    /**
     * @return mixed
     */
    public function getLocation1602()
    {

        $location =   $this->scopeConfig->getValue(self::SI_LOCATION_IMPORT_1602,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $location = $this->validLocationDS($location);
        return $this->validSftpLocation($location);
    }
    /**
     * @return mixed
     */
    public function getLocation1503()
    {
        $location =   $this->scopeConfig->getValue(self::SI_LOCATION_IMPORT_1503,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $location = $this->validLocationDS($location);
        return $this->validSftpLocation($location);
    }

    /**
     * @param null $b
     * @return string
     */
    public function getLocation1504($b=null)
    {
        if($b)
        {
            $location = $this->scopeConfig->getValue(self::SI_LOCATION_IMPORT_1504b,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        }
        else
        {
            $location = $this->scopeConfig->getValue(self::SI_LOCATION_IMPORT_1504,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        }
        $location = $this->validLocationDS($location);
        return $this->validSftpLocation($location);
    }

    /**
     * @return mixed
     */
    public function getLocationSftp($targetNumber)
    {
        $target = 'shipmentimporter/location/import'.$targetNumber;
        $location =  $this->scopeConfig->getValue($target,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $location = $this->validLocationDS($location);
        return $location;
    }
    /**
     * @return mixed
     */
    public function getLocation1507()
    {
        $location = $this->scopeConfig->getValue(self::SI_LOCATION_IMPORT_1507,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $location = $this->validLocationDS($location);
        return $location;
    }
    /**
     * @return mixed
     */
    public function getEmailEnable()
    {
        return $this->scopeConfig->getValue(self::SI_EMAIL_ENABLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
    /**
     * @return mixed
     */
    public function getShippedOutEmailXdays()
    {
        return $this->scopeConfig->getValue(self::SI_EMAIL_SHIPPED_OUT_XDAYS,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
    /**
     * @return mixed
     */
    public function getEmailReceiver()
    {
        return $this->scopeConfig->getValue(self::SI_EMAIL_RECEIVER,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getEmailSubject()
    {
        return $this->scopeConfig->getValue(self::SI_EMAIL_SUBJECT,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getEmailTemplate()
    {
        return $this->scopeConfig->getValue(self::SI_EMAIL_TEMPLATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getEmailTemplateTracking()
    {
        return $this->scopeConfig->getValue(self::SI_EMAIL_TEMPLATE_TRACKING,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getEmailTemplateTrackingSpot()
    {
        return $this->scopeConfig->getValue(self::SI_EMAIL_TEMPLATE_TRACKING_SPOT,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getEmailTemplateTrackingHan()
    {
        return $this->scopeConfig->getValue(self::SI_EMAIL_TEMPLATE_TRACKING_HANPUKAI,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getEmailTemplateTrackingSub()
    {
        return $this->scopeConfig->getValue(self::SI_EMAIL_TEMPLATE_TRACKING_SUBSCRIPTION,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
    /**
     * @return mixed
     */
    public function getEmailTemplateErrorCOD()
    {
        return $this->scopeConfig->getValue(self::SI_EMAIL_TEMPLATE_ERROR_COD,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getEmailTemplateErrorCVS()
    {
        return $this->scopeConfig->getValue(self::SI_EMAIL_TEMPLATE_ERROR_CVS,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
    /**
     * @param $sftp
     * @return bool
     */

    public function checkSftpConnection($sftp)
    {
        $host = $this->getSftpHost();
        $port = $this->getSftpPort() ? $this->getSftpPort() : 22;
        $username = $this->getSftpUser();
        $password = $this->getSftpPass();
        $i=1;
        while($i< self::CONFIG_TIME_CONNECT_SFTP)
        {
            try {
                $sftp->open(
                    array (
                        'host' => $host.':'.$port,
                        'username' => $username,
                        'password' => $password,
                        'timeout' => 300
                    )
                );
                return true;
            } catch (\Exception $e) {
                $this->_logger->critical($e->getMessage());
                $i++;
            }
        }
        if($i==self::CONFIG_TIME_CONNECT_SFTP)
        {
            return false;
        }
        return true;
    }


    /**
     * @param $sftp
     * @param $location
     * @return bool|void
     */
    public function checkSftpLocation($sftp,$location,$complete = false)
    {
        $host = $this->getSftpHost();
        $port = $this->getSftpPort() ? $this->getSftpPort() : 22;
        $username = $this->getSftpUser();
        $password = $this->getSftpPass();

        try {
            $sftp->open(
                array (
                    'host' => $host.':'.$port,
                    'username' => $username,
                    'password' => $password,
                    'timeout' => 300
                )
            );
        } catch (\Exception $e) {
            //send mail
            $this->sendAdminEmailSftpNotConnect();
            return false;
        }
        $dirList = explode('/', $location);

        $i = 0;
        foreach ($dirList as $dir) {
            if($dir != '') {
                try {
                    if(!$sftp->cd($dir))
                        return false;
                    else
                    {
                        if($i==count($dirList)-2)
                        {
                            $sftp->mkdir(DIRECTORY_SEPARATOR . 'complete');
                            $sftp->mkdir(DIRECTORY_SEPARATOR . 'error');
                            $sftp->mkdir(DIRECTORY_SEPARATOR . 'done');
                        }
                    }
                } catch(\Exception $e) {
                    return false;

                }

            }
            $i++;
        }
        return true ;
    }

    /**
     * @param $emailTemplateVariables
     * @param null $templateId
     * @return $this
     */
    public function generateTemplate($emailTemplateVariables, $templateId = null)
    {
        if(!$templateId)
        {
            $templateId = $this->getEmailTemplate();
        }
        $senderInfo = [
            'name' => $this->getSenderName() , 'email' => $this->getSenderEmail()
        ];
        $this->_transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(
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
    public function sendMailResult($emailTemplateVariables)
    {
        if($this->getEmailEnable()) {
            try{
                $this->inlineTranslation->suspend();
                $this->generateTemplate($emailTemplateVariables);
                $transport = $this->_transportBuilder->getTransport();
                $transport->sendMessage();
                $this->inlineTranslation->resume();

            }catch(\Exception $e)
            {
                $this->_logger->critical($e->getMessage());
            }
        }
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
     * @return mixed
     */
    public function getEmailAlert()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $emailAlert = $this->scopeConfig->getValue(self::SI_EMAIL_RECEIVER,$storeScope);
        return explode(';',$emailAlert);
    }

    /**
     * @param $emailTemplateVariables
     * @return $this
     */
    public function generateTrackingCodeEmail($emailTemplateVariables){
        $receiver = $emailTemplateVariables['receiver'];
        $senderInfo = [
            'name' => $this->getSenderName() , 'email' => $this->getSenderEmail()
        ];
        switch($emailTemplateVariables['order_type']) {
            case 'SUBSCRIPTION':
                $emailId = $this->getEmailTemplateTrackingSub();
                break;
            case 'HANPUKAI':
                $emailId = $this->getEmailTemplateTrackingHan();
                break;
            default:
                $emailId = $this->getEmailTemplateTrackingSpot();
                break;
        }
        $this->_transportBuilder->setTemplateIdentifier($emailId)
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo($receiver);

        return $this;
    }

    /**
     * @param $emailTemplateVariables
     */
    public function sendTrackingCodeEmail($emailTemplateVariables)
    {
        try {
            $this->inlineTranslation->suspend();
            $this->generateTrackingCodeEmail($emailTemplateVariables);
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        }catch(\Exception $e)
        {
            $this->_logger->critical($e->getMessage());
        }

    }

    /**
     * @param $emailTemplateVariables
     * @param $emailType
     */
    public function sendErrorImportingEmail($emailTemplateVariables, $emailType)
    {
        if($emailType=="COD")
        {
            $templateId = $this->getEmailTemplateErrorCOD();
        }
        else
        {
            $templateId = $this->getEmailTemplateErrorCVS();
        }
        try {
            $this->inlineTranslation->suspend();
            $this->generateTemplate($emailTemplateVariables,$templateId);
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        }catch(\Exception $e)
        {
            $this->_logger->critical($e->getMessage());
        }

    }

    /**
     * @param $filename
     * @return array
     */

    /**
     * @param $filename
     * @param bool $isShippedOut
     * @param bool $combineTracking
     * @return array
     */
    public function getCsvData($filename, $isShippedOut = false, $combineTracking = false)
    {
        $dateIndexNumber = 6;
        $this->removeBom($filename);
        $datas = $this->_readerCSV->getData($filename);
        $data = array();
        $rowKey = 2; // for 1501, 1601 only
        $trackingIndex = 5;
        $compareKeys = [];
        if($datas)
        {
            foreach($datas as $_data)
            {
                $tempData = array_map('trim',$_data);
                for($i=0;$i<30;$i++) {
                    if(!key_exists($i,$tempData)) {
                        $tempData[$i] = '';
                    }
                }
                if($isShippedOut) // only for 1501 and 1601
                {
                    $shippedOutDate = $tempData[$dateIndexNumber];
                    if($this->validateDate($shippedOutDate))
                    {
                        $tempData[29] = 1;
                    }
                    else
                    {
                        $tempData[29] = 0;
                    }
                }
                if(count($tempData)> 1){
                    if($combineTracking){
                        if(in_array($tempData[$rowKey], $compareKeys)){
                            $arrayIndex = $this->findIndexArray($data,$tempData[$rowKey],$rowKey);
                            if($tempData[$trackingIndex]){
                                $data[$arrayIndex][$trackingIndex] =  $data[$arrayIndex][$trackingIndex].';'.$tempData[$trackingIndex];
                            }
                        }else{
                            $compareKeys[] = $tempData[$rowKey];
                            $data[] = $tempData;
                        }

                    }else{
                        $data[] = $tempData;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * @param $needArray
     * @param $value
     * @param $rowKey
     * @return int
     */
    public function findIndexArray($needArray,$value,$rowKey)
    {
        for($i=0;$i<count($needArray);$i++){
            if($needArray[$i][$rowKey]==$value){
                return $i;
            }
        }
        return 0;
    }
    /**
     * @param $filename
     */
    public function convertEncodeFile($filename)
    {
        $localPathShort = 'import';
        $filePath = $localPathShort.DS.$filename;
        $reader = $this->_fileSystem->getDirectoryRead
        (
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
        $convertFileContent = $reader->openFile($filePath,'r')->readAll();
        $content = mb_convert_encoding($convertFileContent, "UTF-8", "SJIS-win");
        $writer = $this->_fileSystem->getDirectoryWrite
        (
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
        $newFile = $writer->openFile($filePath, 'w+');
        $newFile->write($content);
    }
    /**
     * @param $date
     * @param string $format
     * @return bool
     */
    public function validateDate($date, $format = 'Ymd')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
    /**
     * check for last shipment of an order
     */
    public function isLastShipments($shipments)
    {
        $flag = true;
        foreach($shipments as $ship)
        {
            if(!$ship['imported'])
                $flag = false;
        }
        return $flag;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function checkFullShipment(\Magento\Sales\Model\Order $order, $status)
    {
        //begin to compare
        $searchCriteria = $this->_searchCriticalBuilder
            ->addFilter('order_id',$order->getId())
            ->addFilter('ship_zsim',1,'neq')
            ->create();

        $shipmentsCollection = $this->_shipmentRepository->getList($searchCriteria);


        $shipcounter = 0;
        $shipnumber = $shipmentsCollection->getTotalCount();
        if($shipnumber)
        {
            foreach($shipmentsCollection->getItems() as $_ship)
            {
                $currentStatus = $_ship->getShipmentStatus();

                if($currentStatus == $status || $currentStatus == ShipmentStatus::SHIPMENT_STATUS_REJECTED )
                {
                    $shipcounter++;
                }
            }
            if($shipcounter == $shipnumber && $shipnumber > 0)
            { //shipment comming enoughly ..
                return true;
            }
            else {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function checkOrderShipmentPaymentStatus(\Magento\Sales\Model\Order $order )
    {
        //begin to compare
        $searchCriteria = $this->_searchCriticalBuilder
            ->addFilter('order_id', $order->getId())
            ->create();
        $shipmentsCollection =  $this->_shipmentRepository->getList($searchCriteria);
        $rs = true;
        $shipnumber = $shipmentsCollection->getTotalCount();

        if($shipnumber)
        {
            foreach($shipmentsCollection->getItems() as $_ship)
            {
                $paymentStatus = $_ship->getData('payment_status');
                if( $paymentStatus != \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_COLLECTED
                    &&  $paymentStatus != \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_NOT_APPLICABLE )
                {
                    $rs = false;
                }
            }
        }
        else
        {
            $rs = false;
        }

        return $rs;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function checkOrderHasZsim(\Magento\Sales\Model\Order $order)
    {
        $searchCriteria  = $this->_searchCriticalBuilder
            ->addFilter('order_id',$order->getId())
            ->addFilter('ship_zsim',1)
            ->create();
        $shipmentsCollection = $this->_shipmentRepository->getList($searchCriteria);
        if($shipmentsCollection->getTotalCount()) {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * @param $carrier
     * @return string
     */
    public function getCarrierCode($carrier)
    {
        return $this->_carrierHelper->getCarrierCodeByCompanyCode($carrier);
    }

    /**
     * @param $carrier
     * @return mixed
     */
    public function getCarrierTitle($carrier)
    {
        $code = $this->getCarrierCode($carrier);
        return $this->_carrierHelper->getTitleByCarrierCode($code);
    }

    /**
     * get Inquiry Name
     * @param $carrier
     * @return mixed
     */
    public function getInquiryTitle($carrier)
    {
        return $this->_carrierHelper->getInquiryNameByCarrierCode($carrier);
    }
    /**
     * @param $carrier
     * @return mixed
     */
    public function getCarrierMethod($carrier)
    {
        $code = $this->getCarrierCode($carrier);
        return $this->scopeConfig->getValue('carriers/'.$code.'/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    public function getCarrierUrl($carrier, $tracking = null)
    {
        $trackingUrl =  $this->_carrierHelper->getTrackingUrlByCarrierCode($carrier);
        if($tracking){
            return $trackingUrl. '?id='.$tracking;
        }
        return $trackingUrl;

    }

    /**
     * @param $needDate
     * @param $filenameLog
     * @param null $taskName
     */
    public function backupLog( $needDate, $filenameLog, $taskName= null)
    {
        /**
         * Read current log file and import to backup file in the same day of filename.
         */
        $backupFolder = '/log/ShipmentImporterBackup/';
        $fileSystem = new File();
        $newFile = 'ShipmentImporter_'.$taskName.'_'.$needDate.'.log';
        $writer = $this->_fileSystem->getDirectoryWrite
        (
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
        $backupLog = $writer->openFile($backupFolder.$newFile, 'a+');
        $backupLog->lock();
        $varDir = $this->_directoryList->getPath
        (
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
        $fileSystem = new File();
        $fileLog = $varDir.'/log/importshipment/'.$filenameLog;
        if($fileSystem->isExists($fileLog))
        {
            //read current file and write to backup file
            $reader = $this->_fileSystem->getDirectoryRead
            (
                \Magento\Framework\App\Filesystem\DirectoryList::ROOT
            );
            $contentLog = $reader->openFile('/var/log/importshipment/'.$filenameLog, 'r')->readAll();
            $backupLog->write($contentLog);
            $backupLog->close();
            $fileSystem->deleteFile($fileLog);
        }
    }

    public function removeFile($filename)
    {
        $fileSystem = new File();
        if($fileSystem->isExists($filename) && $fileSystem->isWritable($filename))
        {
            $fileSystem->deleteFile($filename);
        }
    }

    /**
     *
     */
    public function sendAdminEmailSftpNotConnect()
    {
        $emailVariables = [
            'generalMessages' => __("Could not access to Sftp account"),
            'generalReasons' =>sprintf(__("Sftp information, Host: %s, Username: %s, Password: %s, Port: %s"),
                $this->getSftpHost(),
                $this->getSftpUser(),
                $this->getSftpPass(),
                $this->getSftpPort()
            )
        ];
        $this->_emailHelper->sendGeneralEmail($emailVariables);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param null $paymentDate
     * @param bool $statusCompleted
     */
    public function createInvoiceOrderOnly(
        \Magento\Sales\Model\Order $order,
        $paymentDate = null,
        $statusCompleted = false
    )
    {
        $invoice = $this->invoiceDocumentFactory->create($order);
        $invoice->setState(\Magento\Sales\Model\Order\Invoice::STATE_PAID);
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
        $invoice->register();
        try{
            $this->invoiceRepository->save($invoice);
            //save invoice
            $saveTransaction = $this->transactionFactory->create();
            $saveTransaction->addObject($invoice);
            $saveTransaction->addObject($invoice->getOrder());
            $saveTransaction->save();
            if($statusCompleted)
            {
                $order->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);
                $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                $order->setStatus(OrderStatus::STATUS_ORDER_COMPLETE);
                //add history
                $order->addStatusToHistory(
                    OrderStatus::STATUS_ORDER_COMPLETE,
                    __('Complete by Order fixer'),
                    false
                );
            }
            $order->save();
        }catch(\Exception $e)
        {
            $this->_logger->critical($e);
        }


    }
    /**
     * @param \Magento\Sales\Model\Order $order
     * @param null $date
     * @param null $logger
     * @return bool
     * @throws \Exception
     */
    public function createInvoiceOrder(& $order, $date = null, $logger = null )
    {
        $paymentMethod = $order->getPayment()->getMethod();
        $captureType = $paymentMethod=="paygent" ?
            \Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE :
            \Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE;
        $captureFlag = true;

        $statusData = array(
            'order_id' =>$order->getId(),
            'order_increment_id' => $order->getIncrementId(),
            'status_date'=>$date,
        );

        if ($order->canInvoice() && !$order->hasInvoices()) {
            try{
                $time = microtime(true);
                $this->writetoLog($logger,'Create invoice for order: '.$order->getIncrementId());
                $invoice = $this->_invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase($captureType);
                $invoice->register();

                //save invoice
                $saveTransaction = $this->transactionFactory->create();
                $saveTransaction->addObject($invoice);
                $saveTransaction->addObject($invoice->getOrder());
                $saveTransaction->save();
                $timeSpend = "Time spend ".(microtime(true) - $time);
                $this->writetoLog($logger,'TIME SPEND SAVE INVOICE : ' . $timeSpend);

            } catch(\Exception $e) {
                $this->writetoLog($logger,'Capture order: '.$order->getIncrementId().' failed');
                $this->writetoLog($logger,$e->getMessage());
                $this->paymentCaptureFailed($order);
                return false;
            }
            $this->writetoLog($logger,'Capture order: '.$order->getIncrementId().' successfully');
        }
        //capture success
        switch($paymentMethod) {
            case 'paygent':
                if ($order->getStatus() != \Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus::PAYMENT_CAPTURE_FAILED) {
                    //$this->resetCapturedStatus($order);
                    $status = OrderStatus::STATUS_ORDER_COMPLETE;
                    $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                    /*Change order payment status to "Payment collected" */
                    $order->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);
                    $order->addStatusToHistory($status, 'Imported from 3PL')
                        ->setIsCustomerNotified(false);

                    /*update payment agent after capture failed*/
                    if (empty($order->getData('payment_agent'))) {
                        $paymentAgent = $this->getPaymentAgentByOrderIncrementId($order->getIncrementId());
                        if (!empty($paymentAgent)) {
                            $order->setData('payment_agent', $paymentAgent);
                        }
                    }
                }
                break;
            case 'cashondelivery':
                $status = OrderStatus::STATUS_ORDER_COMPLETE;
                $order->addStatusToHistory($status,__('Order complete'))
                    ->setIsCustomerNotified(false);
                //update payment status
                $paymentStatus = $this->getFinalPaymentStatus($order);
                $order->setPaymentStatus($paymentStatus);
                $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);

                $statusData['status_payment'] = $paymentStatus;
                $this->addOrderStatusHistory($statusData);
                break;
            case \Riki\NpAtobarai\Model\Payment\NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE:
                $transactionList = $this->transactionRepository->getListByOrderId($order->getId());
                $countTransaction = 0;
                foreach ($transactionList->getItems() as $transaction) {
                    if ($transaction->getNpCustomerPaymentStatus() == TransactionPaymentStatus::PAID_STATUS_VALUE) {
                        $countTransaction++;
                    }
                }
                if ($countTransaction == $transactionList->getTotalCount()) {
                    $order->setPaymentStatus(OrderPaymentStatus::PAYMENT_COLLECTED);
                }
                $status = OrderStatus::STATUS_ORDER_COMPLETE;
                $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                $order->addStatusToHistory($status, 'Completed Order | Completion of delivery')
                    ->setIsCustomerNotified(false);
                $paymentStatus = $this->getFinalPaymentStatus($order);
                $statusData['status_shipment'] = $paymentStatus;
                $this->addOrderStatusHistory($statusData);
                break;
            default://invoicebasepayment, cvspayment,free, null
                $status = OrderStatus::STATUS_ORDER_COMPLETE;
                $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                $order->addStatusToHistory($status, 'Completed Order | Completion of delivery')
                    ->setIsCustomerNotified(false);
                //update shipment status for Order
                $order->setShipmentStatus(
                    ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED
                );
                //update payment status
                $order->setPaymentStatus(
                    PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED
                );
                $statusData['status_shipment'] = ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED;
                $this->addOrderStatusHistory($statusData);
                break;
        }
        try {
            $order->save();
        } catch(\Exception $e) {
            $this->writetoLog($logger,$e->getMessage());
        }
        return $captureFlag;
    }

    /**
     * NED-1851 Check shipment is rejected, set status to not_applicable
     * Get final payment status for order base on shipment status
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function getFinalPaymentStatus($order)
    {
        $shipmentCollection = $order->getShipmentsCollection();
        $paymentStatus = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;

        if ($shipmentCollection->getTotalCount()) {
            /**
             * @var \Magento\Sales\Model\Order\Shipment $item
             */
            foreach ($shipmentCollection->getItems() as $item) {
                if ($item->getShipmentStatus()==ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED
                    && $item->getPaymentStatus() == PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED
                ) {
                    $paymentStatus = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
                    break;
                } elseif ($item->getShipmentStatus() == ShipmentStatus::SHIPMENT_STATUS_REJECTED
                ) {
                    $paymentStatus = PaymentStatus::SHIPPING_PAYMENT_STATUS_NOT_APPLICABLE;
                }
            }
        } else {
            $paymentStatus = PaymentStatus::SHIPPING_PAYMENT_STATUS_NOT_APPLICABLE;
        }
        return $paymentStatus;
    }

    /**
     * Check this order can create invoice or not
     *
     * @param $orderId
     * @return bool
     */
    public function canCreateInvoiceForCodOrder($orderId)
    {
        $result = true;
        $criteria = $this->_searchCriticalBuilder->addFilter(
            'order_id', $orderId
        )->addFilter(
            'ship_zsim', 1,'neq'
        )->create();

        $shipmentCollection = $this->_shipmentRepository->getList($criteria);

        if ($shipmentCollection->getSize()) {
            foreach ($shipmentCollection->getData() as $shipment) {

                if ($shipment['shipment_status'] != ShipmentStatus::SHIPMENT_STATUS_REJECTED &&
                    $shipment['payment_status'] != \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_COLLECTED
                ) {
                    $result = false;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * @param $orderId
     * @return bool
     */
    public function isOrderCollected($orderId)
    {
        $criteria = $this->_searchCriticalBuilder->addFilter(
            'order_id', $orderId
        )->addFilter(
            'ship_zsim', 1,'neq'
        )->addFilter(
            'is_chirashi', 1,'neq'
        )->create();
        $shipmentCollection = $this->_shipmentRepository->getList($criteria);
        $rs = false;
        if ($shipmentCollection->getSize())
        {
            foreach ($shipmentCollection->getItems() as $item) {

                if ($item->getShipmentStatus()==ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED
                    && $item->getPaymentStatus() == PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED
                ) {
                    $rs = true;
                } else if (
                    $item->getShipmentStatus() == ShipmentStatus::SHIPMENT_STATUS_REJECTED
                ) {
                    $rs = true;
                } else if($item->getShipmentStatus()==ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED
                    && (!$item->getData('grand_total') || $item->getData('grand_total')== $item->getData('base_shopping_point_amount'))
                ){
                    $rs = true;
                } else {
                    /*cannot create invoice if exist any shipment which did not rejected or collected*/
                    return false;
                }
            }
        }
        return $rs;
    }
    /**
     * @param $order
     * @throws \Exception
     */
    public function resetCapturedStatus($order)
    {
        $orderId = $order->getId();
        $orderStatusHistory = $this->_orderHistory->create();
        $orderStatusHistory->addFieldToFilter('parent_id',$orderId);
        $orderStatusHistory->addFieldToFilter('status','preparing_for_shipping');
        $orderStatusHistory->addFieldToFilter('entity_name','invoice');
        $orderStatusHistory->setOrder('created_at','DESC');
        $orderStatusHistory->setPageSize(1)->setCurPage(1)->load();
        $orderStatus = $orderStatusHistory->getFirstItem();
        $comment = $orderStatus->getComment();
        $orderStatus->delete();
        try {
            $status = 'shipped_out';
            $order->addStatusToHistory($status, $comment)->setIsCustomerNotified(false);
            $order->save();
        }
        catch(\Exception $e)
        {
            throw $e;
        }
    }
    /**
     * @param $data
     * @throws \Exception
     */
    public function addOrderStatusHistory($data)
    {
        $this->_orderStatusHelper->addOrderPayShipStatus($data);
    }

    /**
     * @param $location
     * @return string
     */
    public function validSftpLocation($location)
    {
        if($location[0] =="/")
        {
            return $location;
        }
        else
        {
            return "/".$location;
        }
    }

    /**
     * Remove BOM from a file
     *
     * @param string $sourceFile
     * @return $this
     */
    public function removeBom($sourceFile)
    {
        $string = $this->_varDirectory->readFile($this->_varDirectory->getRelativePath($sourceFile));
        if ($string !== false && substr($string, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
            $string = substr($string, 3);
            $this->_varDirectory->writeFile($this->_varDirectory->getRelativePath($sourceFile), $string);
        }
        return $this;
    }

    /**
     * @param $paymentMethod
     * @return string
     */
    public function getPaymentStatus1501($paymentMethod)
    {
        $paymentStatus = '';
        switch($paymentMethod)
        {
            case \Bluecom\Paygent\Model\Paygent::CODE:
                $paymentStatus = '';
                break;
            case \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE:
                $paymentStatus = '';
                break;
            case \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE:
                $paymentStatus = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
                break;
            case \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE;
                $paymentStatus ='';
                break;
            case 'free': // FreeOfCharge or checkout by all points
                $paymentStatus = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
                break;
            default:
                $paymentStatus = '';
                break;
        }
        return $paymentStatus;
    }

    /**
     * @param $logger
     * @param $message
     */
    public function writetoLog($logger,$message)
    {
        if($logger)
        {
            $logger->info($message);
        }
        return;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    public function paymentCaptureFailed(\Magento\Sales\Model\Order $order)
    {
        $shipmentsCollection = $order->getShipmentsCollection();
        if($shipmentsCollection->getTotalCount())
        {
            try{
                foreach($shipmentsCollection as $shipment)
                {
                  $shipment->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_NULL);
                  $shipment->save();
                }
            }catch(\Exception $e)
            {
                $this->_logger->info($e->getMessage());
            }
        }
    }

    /**
     * Get shipment payment status after shipped out
     *
     * @param $paymentMethod
     * @param $order
     * @return bool|string
     */
    public function getShipmentPaymentStatusAfterShippedOut($paymentMethod, $order)
    {
        $paymentStatus = false;

        switch ($paymentMethod) {
            case \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE:
                $paymentStatus = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
                break;
            case self::FREE_PAYMENT:
                if ($order && $order->getChargeType() == \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_NORMAL) {
                    $paymentStatus = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
                }
                break;
        }
        return $paymentStatus;
    }

    /**
     * Get payment agent by order increment id
     *
     * @param $orderIncrementId
     * @return bool|mixed
     */
    public function getPaymentAgentByOrderIncrementId($orderIncrementId)
    {
        return $this->paygentHistoryHelper->getPaymentAgentByOrderIncrementId($orderIncrementId);
    }
    /**
     * Check if mysql has deadlock error
     *
     * @param \Exception $e
     * @return bool
     */
    public function hasMysqlDeadLock(\Exception $e)
    {
        if (preg_match('#SQLSTATE\[40001\]: [^:]+: 1213[^\d]#', $e->getMessage())) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Check if mysql has lock wait timeout exceeded
     *
     * @param \Exception $e
     * @return bool
     */
    public function hasMysqlLockWaitTimeOut(\Exception $e)
    {
        if (preg_match('#SQLSTATE\[40001\]: [^:]+: 1205[^\d]#', $e->getMessage())) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check Xday before send Shipped out email
     *
     * @param $shippedOutDate
     * @param $currentDate
     * @return bool
     */
    public function checkXdaysBeforeSendmail($shippedOutDate, $currentDate)
    {
        $xday = $this->getShippedOutEmailXdays();
        $compareTimes = strtotime($shippedOutDate) + 86400*$xday;
        if ($compareTimes > strtotime($currentDate)) {
            return true;
        }
        return false;
    }
}
