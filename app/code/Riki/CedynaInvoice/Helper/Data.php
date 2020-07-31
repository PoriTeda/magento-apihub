<?php
namespace Riki\CedynaInvoice\Helper;

use Magento\Framework\Exception\LocalizedException;
use Riki\CedynaInvoice\Model\Source\Config\DataType;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\FileSystemException;

/**
 * Class Data
 * @package Riki\CedynaInvoice\Helper
 */
class Data extends AbstractHelper
{
    const XML_PATH_CEDYNA_INVOICE_SETTING_ENABLE = 'cedyna_invoice/setting/enable';
    const XML_PATH_CEDYNA_INVOICE_IMPORT_ENABLE_LOGGER = 'cedyna_invoice/import/enable_logger';
    const XML_PATH_CEDYNA_INVOICE_IMPORT_REMOTE_PATH = 'cedyna_invoice/import/remote_path';
    const XML_PATH_CEDYNA_INVOICE_IMPORT_FILENAME_PATTERN = 'cedyna_invoice/import/filename_pattern';
    const XML_SFTP_HOST = 'setting_sftp/setup_ftp/ftp_id';
    const XML_SFTP_PORT = 'setting_sftp/setup_ftp/ftp_port';
    const XML_SFTP_USER = 'setting_sftp/setup_ftp/ftp_user';
    const XML_SFTP_PASS = 'setting_sftp/setup_ftp/ftp_pass';
    const SFTP_LOCATION_DONE = 'done';
    const SFTP_LOCATION_ERROR = 'error';
    const LOCAL_LOCATION_IMPORT = 'import';
    const IMPORT_FILE_EXTENSION = '.txt';
    const DATA_ENCODING = 'SHIFT-JIS';
    const DEFAULT_VALUE_EMPTY_FIELD = '-';
    /**
     * @var \Riki\CedynaInvoice\Logger\Logger
     */
    private $customLogger;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;
    /**
     * @var \Magento\Framework\Filesystem\Io\Sftp
     */
    private $sftp;
    /**
     * @var
     */
    private $sftpLocation;
    /**
     * @var
     */
    private $localLocation;
    /**
     * @var
     */
    private $currentDate;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $file;
    /**
     * @var \Magento\Framework\Filesystem
     */
    private $fileSystem;
    /**
     * @var array
     */
    protected $columnKeys = [
        'business_code' => ['start' => 24, 'length'=> 20],
        'shipped_out_date' => ['start' => 46, 'length'=> 8],
        'returned_date' => ['start' => 54, 'length'=> 8],
        'data_type' => ['start' => 62, 'length'=> 2, ],
        'row_total' => ['start' => 64, 'length'=> 9],
        'increment_id' => ['start' => 74, 'length'=> 15],
        'product_line_name' => ['start' => 90, 'length'=> 30, ],
        'unit_price' => ['start' => 120, 'length'=> 9],
        'qty' => ['start' => 131, 'length'=> 6]
    ];
    protected $uncheckFiles = ['.', '..','done','error'];
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $datetime;
    /**
     * @var \Riki\Customer\Helper\ShoshaHelper
     */
    protected $shoshaCustomerHelper;
    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * Data constructor.
     * @param Context $context
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\Filesystem\Io\Sftp $sftp
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Riki\CedynaInvoice\Logger\Logger $logger
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Riki\Customer\Helper\ShoshaHelper $shoshaHelper
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem $filesystem,
        \Riki\CedynaInvoice\Logger\Logger $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Riki\Customer\Helper\ShoshaHelper $shoshaHelper,
        \Magento\Framework\Locale\ResolverInterface $localeResolver
    ) {
        $this->encryptor = $encryptor;
        $this->sftp = $sftp;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->fileSystem = $filesystem;
        $this->customLogger = $logger;
        $this->timezone = $timezone;
        $this->datetime =  $datetime;
        $this->shoshaCustomerHelper = $shoshaHelper;
        $this->localeResolver = $localeResolver;
        parent::__construct($context);
    }
    /**
     * Check module is enabled or disabled
     *
     * @return mixed
     */
    public function isEnable()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_CEDYNA_INVOICE_SETTING_ENABLE, $storeScope);
    }

    /**
     * Enable or disable logger
     * @return mixed
     */
    public function isEnableLogger()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_CEDYNA_INVOICE_IMPORT_ENABLE_LOGGER, $storeScope);
    }

    /**
     * Push a message to logger
     * @param $message
     * @param bool $error
     */
    public function writeToLog($message, $error = true)
    {
        if ($this->isEnableLogger()) {
            if ($error) {
                $this->customLogger->error($message);
            } else {
                $this->customLogger->info($message);
            }
        }
    }
    /**
     * get sftp location from backend configuration
     *
     * @return mixed
     */
    public function getSftpLocation()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CEDYNA_INVOICE_IMPORT_REMOTE_PATH);
    }

    /**
     * get filename pattern to filter from sftp location
     *
     * @return mixed
     */
    public function getFilePattern()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CEDYNA_INVOICE_IMPORT_FILENAME_PATTERN);
    }
    /**
     * @return mixed
     */
    public function getSftpHost()
    {
        return $this->scopeConfig->getValue(
            self::XML_SFTP_HOST,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @return mixed
     */
    public function getSftpPort()
    {
        $port =  $this->scopeConfig->getValue(
            self::XML_SFTP_PORT,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
        return $port ? $port : 22;
    }

    /**
     * @return mixed
     */
    public function getSftpUser()
    {
        return $this->scopeConfig->getValue(
            self::XML_SFTP_USER,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }
    /**
     * @return mixed
     */
    public function getSftpPass()
    {
        $pass =  $this->scopeConfig->getValue(
            self::XML_SFTP_PASS,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
        return $this->encryptor->decrypt($pass);
    }

    /**
     * Get data from sftp
     * @return array|bool
     */
    public function getSftpData()
    {
        $host = $this->getSftpHost();
        $port = $this->getSftpPort();
        $username = $this->getSftpUser();
        $password = $this->getSftpPass();
        $this->sftpLocation = $this->getSftpLocation();
        $this->localLocation = '';
        $this->currentDate = date('YmdHis');
        $patternRoot = $this->getFilePattern();
        $filePattern = "/^$patternRoot/";
        try {
            $this->sftp->open(
                [
                    'host' => $host.':'.$port,
                    'username' => $username,
                    'password' => $password,
                    'timeout' => 300
                ]
            );
            $this->sftpLocation = str_replace('//', '/', $this->sftp->pwd().$this->getSftpLocation());
            if ($this->sftp->cd($this->sftpLocation)) {
                $files = $this->sftp->rawls();
                if ($files) {
                    return $this->fileFilter($files, $filePattern);
                } else {
                    $this->writeToLog(__('TXT files not found'));
                    return false;
                }
            } else {
                $this->writeToLog(__('Location :%1 does not exist', $this->sftpLocation));
                return false;
            }
        } catch (\Exception $e) {
            $this->customLogger->critical($e);
            return false;
        }
    }

    /**
     * Get all content of txt files
     *
     * @param $files
     * @param $pattern
     * @return array
     */
    private function fileFilter($files, $pattern)
    {
        /* Create folder DONE and ERROR if they dont exist */
        $this->createSftpFolders();
        $baseDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $sftpPath = $this->sftpLocation;
        $localPath = $baseDir.DIRECTORY_SEPARATOR.self::LOCAL_LOCATION_IMPORT;
        $allFiles = [];
        foreach ($files as $key => $file) {
            if (!in_array($key, $this->uncheckFiles)) {
                if ($this->validateTxtFile($key, $pattern)) {
                    $this->sftp->read(
                        $sftpPath . DIRECTORY_SEPARATOR . $key,
                        $localPath . DIRECTORY_SEPARATOR . $key
                    );
                    $this->moveToLocation(self::SFTP_LOCATION_DONE, $key, true);
                    //read content of TXT
                    $relativePath = self::LOCAL_LOCATION_IMPORT. DIRECTORY_SEPARATOR . $key;
                    $importDate = $this->extractDate($key);
                    $txtData = $this->parseDataTxt($relativePath, $baseDir, $importDate);
                    if ($txtData) {
                        $allFiles[$key] = $txtData;
                    } else {
                        $this->writeToLog(__('File: %1 has no valid content', $key));
                    }
                    $this->removeLocalFile($localPath . DIRECTORY_SEPARATOR . $key);
                } else {
                    $this->moveToLocation(self::SFTP_LOCATION_ERROR, $key);
                }
            }
        }
        return $allFiles;
    }

    /**
     * Move a file from source to destination path on SFTP
     * @param $destination
     * @param $file
     * @param bool $rename
     */
    private function moveToLocation($destination, $file, $rename = false)
    {
        $sourceFile = $this->sftpLocation.DIRECTORY_SEPARATOR.$file;
        if ($rename) {
            $file = $this->getNewFilename($file);
        }
        $newDestinationFileName = $this->getNewFilename($file);
        $destinationFile = $this->sftpLocation.DIRECTORY_SEPARATOR.
            $destination.
            DIRECTORY_SEPARATOR.
            $newDestinationFileName;
        try {
            $this->sftp->mv($sourceFile, $destinationFile);
            $this->writeToLog(__('Move file from : %1 to :%2', $sourceFile, $destinationFile), false);
        } catch (\Exception $e) {
            $this->writeToLog(__('Can not move file from: %1 to :%2', $sourceFile, $destinationFile));
        }
    }

    /**
     * Get new filename then move to done folder in SFTP location
     * @param $file
     * @return mixed
     */
    private function getNewFilename($file)
    {
        $extension = substr($file, strrpos($file, '.')); // Gets the File Extension
        return str_replace($extension, '_' . $this->currentDate . $extension, $file);
    }

    /**
     * Check and create subfolder : DONE and ERROR in SFTP after login
     */
    private function createSftpFolders()
    {
        $paths = [
            $this->sftpLocation.DIRECTORY_SEPARATOR.self::SFTP_LOCATION_DONE,
            $this->sftpLocation.DIRECTORY_SEPARATOR.self::SFTP_LOCATION_ERROR
        ];
        //back to root of path
        foreach ($paths as $path) {
            $this->sftp->cd('/');
            $dirList = explode('/', $path);
            foreach ($dirList as $dir) {
                if ($dir != '') {
                    if (!$this->sftp->cd($dir)) {
                        if (!$this->sftp->mkdir('/'. $dir)) {
                            $this->writeToLog(__('Permission denied'));
                            throw new LocalizedException(__(
                                'Can not create folder %1 in SFTP',
                                $this->sftp->pwd().DIRECTORY_SEPARATOR.$dir
                            ));
                        }
                    }
                    $this->sftp->cd($dir);
                }
            }
        }
    }
    /**
     * Delete local file after get content
     * @param $path
     */
    private function removeLocalFile($path)
    {
        try {
            if ($this->file->isExists($path)) {
                $this->file->deleteFile($path);
            }
        } catch (FileSystemException $e) {
            $this->writeToLog($e->getTraceAsString());
        }
    }

    /**
     * @param $filename
     * @param $basePath
     * @param $importDate
     * @return array|bool
     */
    private function parseDataTxt($filename, $basePath, $importDate)
    {
        $importTime = implode('-', $importDate);
        $importMonth = $importDate['year'].$importDate['month'];
        $targetMonth = $this->getPreviousMonth($importMonth);
        $reader = $this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        if ($this->file->isExists($basePath.DIRECTORY_SEPARATOR.$filename)) {
            $txtContentRaw = $reader->openFile($filename)->readAll();
            $txtData = explode("\n", $txtContentRaw);
            if (!empty($txtData)) {
                $resultData = [];
                foreach ($txtData as $txtRow) {
                    $resultRow = $this->extractData(trim($txtRow));
                    if ($resultRow) {
                        $resultRow['import_date'] = $importTime;
                        $resultRow['import_month'] = $importMonth;
                        $resultRow['target_month'] = $targetMonth;
                        $resultData[] = $resultRow;
                    }
                }
                return $resultData;
            } else {
                //file empty
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Extract needed data from a string
     *
     * @param $flatRow
     * @return array|bool
     */
    private function extractData($flatRow)
    {
        $detectEncoding = mb_detect_encoding($flatRow, "JIS,SJIS,eucjp-win");
        $convertColumns = ['product_line_name'];
        //136 is the last position of column qty which need to get data on every row
        if (mb_strlen(trim($flatRow)) >136) {
            $row = [];
            $row['beginRow'] = $flatRow[0];
            foreach ($this->columnKeys as $key => $column) {
                $result = trim(mb_substr($flatRow, $column['start']-1, $column['length'], $detectEncoding));
                if (in_array($key, $convertColumns)) {
                    $result = mb_convert_encoding($result, 'UTF-8', $detectEncoding);
                }
                $row[$key] = $result;
            }
            if (!$row['product_line_name']) {
                $row['product_line_name'] = self::DEFAULT_VALUE_EMPTY_FIELD;
            }
            return $row;
        } else {
            return false;
        }
    }

    /**
     * @param $filename
     * @return string
     */
    private function extractDate($filename)
    {
        $explodedDateString = explode('_', $filename);
        $dateString = str_replace(self::IMPORT_FILE_EXTENSION, '', strtolower($explodedDateString[1]));
        if ($dateString && strlen($dateString)>5) {
            $year = mb_substr($dateString, 0, 4);
            $month = mb_substr($dateString, 4, 2);
            $day = mb_substr($dateString, 6, 2);
            if (checkdate($month, $day, $year)) {
                return ['year' => $year, 'month' => $month, 'day' => $day];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    /**
     * @param $importMonth
     * @return false|string
     */
    private function getPreviousMonth($importMonth)
    {
        return date('Ym', strtotime('-1 months', strtotime($importMonth.'01')));
    }

    /**
     * Convert to correct data type
     * @param array $txtRow
     * @return array
     */
    public function convertData(array $txtRow)
    {
        $numericFields = ['row_total','unit_price','qty'];
        $datetimeFields = ['shipped_out_date', 'returned_date'];
        foreach ($txtRow as $key => $value) {
            if (in_array($key, $numericFields)) {
                $txtRow[$key] = (int)$value;
            }
            if (in_array($key, $datetimeFields)) {
                $txtRow[$key] =  $this->datetime->gmtDate('Y-m-d', strtotime($value));
                if ($value <= 0 && $key == 'returned_date') {
                    unset($txtRow[$key]);
                }
            }
        }
        return $txtRow;
    }

    /**
     * Validate file name
     *
     * @param $fileName
     * @param $filenamePattern
     * @return bool
     */
    private function validateTxtFile($fileName, $filenamePattern)
    {
        $pre = preg_match($filenamePattern, $fileName);
        if (!$pre) {
            $this->writeToLog(__(
                'File: %1 does not match the config pattern name (%2).',
                $fileName,
                $filenamePattern
            ));
            return false;
        }
        $extension = substr($fileName, strrpos($fileName, '.')); // Gets the File Extension
        if (!strtolower($extension) == self::IMPORT_FILE_EXTENSION) {
            $this->writeToLog(__('File: %1 is not TXT extension', $fileName));
            return false;
        }
        $importDate = $this->extractDate($fileName);
        if (!$importDate) {
            $this->writeToLog(__('File: %1 does not contain a correct date(yyyymmdd)', $fileName));
            return false;
        }
        return true;
    }

    /**
     * Format date
     * @param $date
     * @return string
     */
    public function formatDate($date)
    {
        return $this->timezone->formatDateTime(
            new \DateTime($date),
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::NONE,
            null
        );
    }
    /**
     * @param $invoices
     * @return array
     */
    public function buildInvoicesInformation($invoices)
    {
        $totalAmount = 0;
        $shippingGroup = [];
        foreach ($invoices as $invoice) {
            if ($invoice['data_type'] == DataType::DATA_TYPE_OPTION_SALES) {
                $tempTotal = $invoice['row_total'];
            } else {
                $tempTotal = (-1) * $invoice['row_total'];
            }
            $totalAmount += $tempTotal;
            if (!isset($shippingGroup[$invoice['riki_nickname']])) {
                $shippingGroup[$invoice['riki_nickname']] = $tempTotal;
            } else {
                $shippingGroup[$invoice['riki_nickname']] += $tempTotal;
            }
        }
        return [
            'total' => $totalAmount,
            'riki_nickname_group' => $shippingGroup
        ];
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return mixed|string
     */
    public function getCustomerBusinessCode(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        if ($customer->getId()) {
            $businessCode = $customer->getCustomAttribute('shosha_business_code');
            if ($businessCode) {
                return $businessCode->getValue();
            }
        }
        return '';
    }
    /**
     * Format invoice date phrase
     * @param $invoice
     * @return \Magento\Framework\Phrase
     */
    public function formatInvoiceDate($invoice)
    {
        $targetYear = $this->datetime->gmtDate('Y', strtotime($invoice['target_month'].'01'));
        $targetMonth = $this->datetime->gmtDate('m', strtotime($invoice['target_month'].'01'));
        $importMonth = $this->datetime->gmtDate('m', strtotime($invoice['import_month'].'01'));
        return __('Invoice details for %1 %2 (invoice: sent in %3)', $targetYear, $targetMonth, $importMonth);
    }

    /**
     * @param $customer
     * @return bool
     */
    public function canCedynaInvoice($customer)
    {
        if ($this->isEnable()) {
            return $this->shoshaCustomerHelper->isCedynaCustomerByData($customer);
        } else {
            return false;
        }
    }

    /**
     * @param $invoices
     * @return string
     */
    public function buildInvoiceSummaryContent($invoices)
    {
        $invoicesRows = [];
        $invoicesContent = '';
        if ($invoices) {
            $shippingGroup = [];
            foreach ($invoices as $invoice) {
                $rikiNickName = $invoice['riki_nickname'];
                if ($invoice['data_type'] == DataType::DATA_TYPE_OPTION_SALES) {
                    $tempTotal = $invoice['row_total'];
                } else {
                    $tempTotal = (-1) * $invoice['row_total'];
                }
                if (!isset($shippingGroup[$rikiNickName])) {
                    $shippingGroup[$rikiNickName] = $tempTotal;
                } else {
                    $shippingGroup[$rikiNickName] += $tempTotal;
                }
            }
            foreach ($shippingGroup as $groupName => $groupTotal) {
                $invoicesRows[] = implode(',', $this->encodeByLocale([$groupName, (int)$groupTotal]));
            }
            $invoicesContent = implode("\n", $invoicesRows);
        }
        return $invoicesContent;
    }

    /**
     * @param $str
     * @return mixed|string
     */
    public function convertToShiftJS($str)
    {
        return mb_convert_encoding($str, self::DATA_ENCODING);
    }

    /**
     * @param array $data
     * @return array
     */
    public function encodeByLocale(array $data)
    {
        $locale = $this->localeResolver->getLocale();
        if (strtoupper($locale) == 'JA_JP') {
            for ($i=0; $i<count($data); $i++) {
                $data[$i] = $this->convertToShiftJS($data[$i]);
            }
        }
        return $data;
    }


    /**
     * @param $invoice
     * @return mixed
     */
    public function getShipmentDate($invoice)
    {
        $shipmentDate = $invoice['shipped_out_date'];
        if ($invoice['data_type'] == DataType::DATA_TYPE_OPTION_RETURN) {
            $shipmentDate = $invoice['returned_date'];
        }
        return $shipmentDate;
    }
}
