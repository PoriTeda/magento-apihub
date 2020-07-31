<?php
namespace Riki\Customer\Cron;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;


class CedynaCustomerImport {

    const EMAIL_TEMPLATE_ERROR_REPORT = 'cedyna_customer_hold_email_setting_email_template_error';

    const STORAGE_DIR = 'import_blockcustomer_cedyna';
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
     * @var \Riki\Customer\Logger\CustomerHold\Logger
     */
    protected $_logger;

    /**
     * @var \Riki\Customer\Helper\Data
     */
    protected $_dataHelper;

    /**
     * @var \Magento\Framework\Filesystem\Io\Sftp
     */
    protected $_sftp;

    /**
     * @var DateTime
     */
    protected $_datetime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\Email
     */
    protected $_email;

    /**
     * @var string
     */
    protected $_logInfo = '';

    /**
     * @var string
     */
    protected $_logError = '';

    /**
     * @var \Magento\Framework\Phrase\Renderer\Composite
     */
    protected $_phraseComposite;

    /**
     * CedynaCustomerImport constructor.
     *
     * @param \Riki\Customer\Logger\CustomerHold\Logger $logger
     * @param \Riki\Customer\Helper\Data $dataHelper
     * @param DateTime $datetime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Riki\Customer\Model\ShoshaFactory $shoshaFactory
     * @param \Riki\ThirdPartyImportExport\Helper\Sftp $sftp
     * @param \Riki\ThirdPartyImportExport\Helper\Email $email
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Magento\Framework\Phrase\Renderer\Composite $phraseComposite
     */
    public function __construct(
        \Riki\Customer\Logger\CustomerHold\Logger $logger,
        \Riki\Customer\Helper\Data $dataHelper,
        DateTime $datetime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\File\Csv $csv,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Filesystem $filesystem,
        \Riki\Customer\Model\ShoshaFactory $shoshaFactory,
        \Riki\ThirdPartyImportExport\Helper\Sftp $sftp,
        \Riki\ThirdPartyImportExport\Helper\Email $email,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList
    )
    {
        $this->_logger = $logger;
        $this->_dataHelper = $dataHelper;
        $this->_datetime = $datetime;
        $this->_timezone = $timezone;
        $this->_logger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->_csv = $csv;
        $this->_mageCustomerRepository = $customerRepository;
        $this->_customerFactory = $customerFactory;
        $this->_storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_fileSystem = $filesystem;
        $this->_shoshaFactory = $shoshaFactory;
        $this->_sftp = $sftp;
        $this->_email = $email;
        $this->_directoryList = $directoryList;
    }

    public function execute(){

        $needDateFile = $this->_timezone->date()->format('YmdHis');
        $this->backupLog($needDateFile, 'import_customer_hold.log');

        //connect ftp
        $host = $this->_dataHelper->getCedynaCustomerHoldSftpHost();
        $port = $this->_dataHelper->getCedynaCustomerHoldSftpPort();
        $username = $this->_dataHelper->getCedynaCustomerHoldSftpUser();
        $password = $this->_dataHelper->getCedynaCustomerHoldSftpPass();
        $sFileNameSftp = $this->_dataHelper->getCedynaCustomerHoldSFTPFileName();
        $sFilePathSftp = rtrim($this->_dataHelper->getCedynaCustomerHoldSFTPFilePath(), '/') . '/';

        if (empty($host)) {
            return $this;
        }
        $connected = $this->_sftp->connect($host, $port, $username, $password);

        if ($connected !== true) {
            $this->_logger->error(__("Cannot connect to sftp host.")."\r\n");
            $this->handleError();
            return $this;
        }

        $aColumns = ['business_code','block_orders'];

        try {

            $this->_sftp->initBackupImportCedyna($sFilePathSftp);

            $files = $this->_sftp->filter($sFileNameSftp);

            if(!count($files)){
                $this->_logError .= __("File to import cannot be found.")."\r\n";
                $this->_logger->error(sprintf("File to import cannot be found."));
                $this->handleError();
                return $this;
            }

            foreach ($files as $fileName) {

                $file = $this->_sftp->read($fileName);

                if (!$file) {
                    $this->_logError .= sprintf("Abort import. [Unable download file %s to local].\r\n", $fileName);
                    $this->_logger->error(sprintf("Abort import. [Unable download file %s to local].", $fileName));
                    continue;
                }

                $csvDatas = $this->_csv->getData($file);

                // check exist header
                if (array_diff($csvDatas[0], $aColumns)) {
                    $this->_logError .= sprintf("Template of file csv ".$fileName." is invalid format.\r\n");
                    $this->_logger->error(sprintf("Template of file csv ".$fileName." is invalid format."));
                    $this->_sftp->backupPostFixError($sFilePathSftp . $fileName,$this->_timezone->date()->format('YmdHis'));
                    continue;
                }

                $csvDatas = $this->getDataFileImport($csvDatas);

                //update customer
                $customerBusinessCode = array();
                $customerBusinessCodeBlockValue = array();
                foreach($csvDatas as $csvData){
                    if(isset($csvData['business_code'])){
                        $customerBusinessCode[] = $csvData['business_code'];
                        $customerBusinessCodeBlockValue[$csvData['business_code']] = $csvData['block_orders'];
                    }
                }


                //update block order for model shosha
                $aShoshaBusinessCodeExist = [];

                $aShoshaCollection = $this->_shoshaFactory->create()->getCollection()->addFieldToFilter('shosha_business_code', array('in' => $customerBusinessCode));

                $listBlockCustomer = "";

                if ($aShoshaCollection->getSize()) {

                    foreach ($aShoshaCollection as $shosha) {

                        try {

                            $aShoshaBusinessCodeExist[] = $shosha->getData('shosha_business_code');

                            $blockOrders = $customerBusinessCodeBlockValue[$shosha->getData('shosha_business_code')];

                            $shosha->setData('block_orders', $blockOrders);

                            $this->_logger->info(sprintf("Business code ".$shosha->getData('shosha_business_code')." of file ".$fileName." is imported with value ".$blockOrders."."));

                            $shosha->save();

                            if ($blockOrders) {
                                $listBlockCustomer .= '顧客コード：' . $shosha->getData('shosha_business_code') . "\r\n";
                            }

                        } catch (\Exception $e) {
                            $this->_logger->error($e->getMessage());
                        }
                    }
                }

                /* controlled by Email Marketing */
                /* Email: Hold Customer (Business user) */

                if ("" != $listBlockCustomer) {
                    try {
                        $vars = ['list_blocked_customer' => $listBlockCustomer];
                        $this->sendMailBlockedInvoiceCustomer($vars);
                    } catch (\Exception $e) {
                        $this->_logger->error($e->getMessage());
                    }
                }

                $aShoshaBusinessCodeDontExist = array_diff($customerBusinessCode,$aShoshaBusinessCodeExist);

                if(count($aShoshaBusinessCodeDontExist) != count($customerBusinessCode)){
                    $this->_sftp->backupPostFix($sFilePathSftp . $fileName,$this->_timezone->date()->format('YmdHis'));
                }
                else{
                    $this->_sftp->backupPostFixError($sFilePathSftp . $fileName,$this->_timezone->date()->format('YmdHis'));
                }

                foreach($aShoshaBusinessCodeDontExist as $shoshaCode){
                    $this->_logError .= sprintf("Business code ".$shoshaCode." of file ".$fileName." does not exist.\r\n");
                    $this->_logger->error(sprintf("Business code ".$shoshaCode." of file ".$fileName." does not exist."));
                }
            }
        } catch (\Exception $e) {
            $this->_logError .= __("File to import cannot be found.")."\r\n";
            $this->_logger->error( $e->getMessage() );
        }

        if ($this->_logError) {
            $this->handleError();
        }
    }

    /**
     * GetDataFileImport
     *
     * @param $aDataImport
     * @return array
     */
    public function getDataFileImport($aDataImport){

        $aDataImportFinal = [];

        if(count($aDataImport)){
            $headers = [];
            foreach($aDataImport as $key => $csvData){
                if($key ==0){
                    $headers = $csvData;
                }
                else{
                    $item = array();
                    $i = 0;
                    foreach($headers as $header){
                        if(isset($csvData[$i])){
                            $item[$header] = $csvData[$i];
                        }
                        $i++;
                    }
                    if(count($item) == count($headers)){
                        $aDataImportFinal[] = $item;
                    }
                }
            }
        }
        return $aDataImportFinal;
    }
    /**
     *SendMailBlockedInvoiceCustomer
     *
     * @param $emailTemplateVariables
     */
    public function sendMailBlockedInvoiceCustomer($emailTemplateVariables){
        $this->inlineTranslation->suspend();
        if($this->generateTemplate($emailTemplateVariables)){
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
        }
        $this->inlineTranslation->resume();
    }

    /**
     * GenerateTemplate
     *
     * @param $emailTemplateVariables
     * @return $this
     */
    public function generateTemplate($emailTemplateVariables)
    {
        $senderInfo = [
            'name' => $this->_dataHelper->getSenderName() , 'email' => $this->_dataHelper->getSenderEmail()
        ];

        $receivers = array_filter(explode(',', $this->_dataHelper->getCedynaCustomerHoldEmailAlert()), 'trim');
        if (!$receivers) {
            return;
        }

        $this->_transportBuilder->setTemplateIdentifier($this->_dataHelper->getCedynaCustomerHoldEmailTemplate())
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo($receivers);

        return $this;
    }

    /**
     * generateTemplateError
     *
     * @param $emailTemplateVariables
     * @return $this
     */
    public function generateTemplateError($emailTemplateVariables)
    {
        $senderInfo = [
            'name' => $this->_dataHelper->getSenderName() , 'email' => $this->_dataHelper->getSenderEmail()
        ];

        $receivers = array_filter(explode(',', $this->_dataHelper->getCedynaCustomerHoldEmailAlert()), 'trim');
        if (!$receivers) {
            return;
        }

        $this->_transportBuilder->setTemplateIdentifier($this->_dataHelper->getCedynaCustomerHoldEmailTemplateError())
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo($receivers);

        return $this;
    }


    /**
     * Send error report via email
     *
     * @param \Exception|string $log
     */
    public function sendEmailReport()
    {
        $reader = $this->_fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $contentLog = $reader->openFile('/log/import_customer_hold.log', 'r')->readAll();

        $log = ['log' => $contentLog];
        $this->inlineTranslation->suspend();
        if($this->generateTemplateError($log)){
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
        }
        $this->inlineTranslation->resume();

        return $this;
    }
    /**
     * @param $msg
     * @return void
     */
    public function handleError()
    {
        $this->sendEmailReport();
    }

    /**
     * @param $needDate
     * @param $filenameLog
     */
    public function backupLog($needDate, $filenameLog)
    {
        /**
         * Read current log file and import to backup file in the same day of filename.
         */
        $backupFolder = '/log/ImportCustomerHoldBackup/';
        $fileSystem = new File();
        $newFile = 'import_customer_hold_'.$needDate.'.log';
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
        $fileLog = $varDir.'/log/'.$filenameLog;
        if($fileSystem->isExists($fileLog))
        {
            //read current file and write to backup file
            $reader = $this->_fileSystem->getDirectoryRead
            (
                \Magento\Framework\App\Filesystem\DirectoryList::ROOT
            );
            $contentLog = $reader->openFile('/var/log/'.$filenameLog, 'r')->readAll();
            $backupLog->write($contentLog);
            $backupLog->close();
            $fileSystem->deleteFile($fileLog);
        }
    }
}