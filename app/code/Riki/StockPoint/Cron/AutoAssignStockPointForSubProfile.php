<?php

namespace Riki\StockPoint\Cron;

use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Stdlib\DateTime;
use Bluecom\Paygent\Model\Paygent;
use Riki\SubscriptionFrequency\Model\Frequency;
use Riki\StockPoint\Logger\AutoAssignStockPointLogger;

class AutoAssignStockPointForSubProfile
{
    const FILE_NAME_PATTERN = 'stock_point_auto_assign_list';
    const GOOGLE_STATUS_OK = 'OK';
    const GOOGLE_STATUS_ZERO_RESULTS = 'ZERO_RESULTS';

    /**
     * @var \Magento\Framework\Filesystem\Io\Sftp
     */
    protected $sftp;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Riki\StockPoint\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $fileSystem;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $profileRepository;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $subCourseHelper;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileHelper;

    /**
     * @var \Riki\StockPoint\Model\Api\BuildStockPointPostData
     */
    protected $buildStockPointPostData;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Riki\StockPoint\Model\StockPointRepository
     */
    protected $stockPointRepository;

    /**
     * @var \Riki\Subscription\Helper\StockPoint\Data
     */
    protected $stockPointHelper;

    /**
     * @var \Riki\Customer\Helper\Region
     */
    protected $regionHelper;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var AutoAssignStockPointLogger
     */
    protected $assignStockPointLogger;

    /**
     * @var array
     */
    protected $columnDefine = [
        0 => [
            'field_name' => 'stock_point_id',
            'required' => true,
            'type' => 'number',
            'length' => 10
        ],
        1 => [
            'field_name' => 'stock_point_system_data',
            'required' => true,
            'type' => 'list',
            'length' => null
        ],
        2 => [
            'field_name' => 'delivery_type',
            'required' => true,
            'type' => 'text',
            'length' => 50,
            'default_value' => 'dropoff'
        ],
        3 => [
            'field_name' => 'next_delivery_date',
            'required' => true,
            'type' => 'date',
            'length' => null
        ],
        4 => [
            'field_name' => 'next_order_date',
            'required' => true,
            'type' => 'date',
            'length' => null
        ],
        5 => [
            'field_name' => 'delivery_time',
            'required' => false,
            'type' => 'number',
            'length' => null
        ],
        6 => [
            'field_name' => 'capacity_auto_delivery',
            'required' => true,
            'type' => 'int_number',
            'length' => 3,
        ],
        7 => [
            'field_name' => 'delivery_distance',
            'required' => true,
            'type' => 'number',
            'length' => 6
        ],
        8 => [
            'field_name' => 'target_delivery_date_from',
            'required' => true,
            'type' => 'date',
            'length' => null
        ],
        9 => [
            'field_name' => 'target_delivery_date_to',
            'required' => true,
            'type' => 'date',
            'length' => null
        ],
        10 => [
            'field_name' => 'stockpoint_latitude',
            'required' => true,
            'type' => 'decimal',
            'length' => '8,6'
        ],
        11 => [
            'field_name' => 'stockpoint_longitude',
            'required' => true,
            'type' => 'decimal',
            'length' => '9,6'
        ],
        12 => [
            'field_name' => 'current_discount_rate',
            'required' => true,
            'type' => 'int_number',
            'length' => 6
        ],
        13 => [
            'field_name' => 'comment_for_customer',
            'required' => false,
            'type' => 'text',
            'length' => 255
        ],
        14 => [
            'field_name' => 'stock_point_postcode',
            'required' => true,
            'type' => 'text',
            'length' => 10
        ],
        15 => [
            'field_name' => 'stock_point_prefecture',
            'required' => true,
            'type' => 'text',
            'length' => 255
        ],
        16 => [
            'field_name' => 'stock_point_address',
            'required' => true,
            'type' => 'text',
            'length' => 255
        ],
        17 => [
            'field_name' => 'stock_point_lastname',
            'required' => true,
            'type' => 'text',
            'length' => 255
        ],
        18 => [
            'field_name' => 'stock_point_firstname',
            'required' => true,
            'type' => 'text',
            'length' => 255
        ],
        19 => [
            'field_name' => 'stock_point_lastnamekana',
            'required' => true,
            'type' => 'text',
            'length' => 255
        ],
        20 => [
            'field_name' => 'stock_point_firstnamekana',
            'required' => true,
            'type' => 'text',
            'length' => 255
        ],
        21 => [
            'field_name' => 'stock_point_telephone',
            'required' => true,
            'type' => 'text',
            'length' => 20
        ],
        22 => [
            'field_name' => 'frequency_unit',
            'required' => true,
            'type' => 'text',
            'length' => 5,
            'default_value' => 'month'
        ],
        23 => [
            'field_name' => 'frequency_interval',
            'required' => true,
            'type' => 'list',
            'length' => null
        ],
        24 => [
            'field_name' => 'candidate_postcode',
            'required' => true,
            'type' => 'list',
            'length' => null
        ],
    ];

    /**
     * @var array
     */
    protected $profileValidatedFail = [];

    /**
     * AutoAssignStockPointForSubProfile constructor.
     *
     * @param \Magento\Framework\Filesystem\Io\Sftp $sftp
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Riki\StockPoint\Helper\Data $dataHelper
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $fileSystem
     * @param \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Riki\SubscriptionCourse\Helper\Data $subCourseHelper
     * @param \Riki\Subscription\Helper\Profile\Data $profileHelper
     * @param \Riki\StockPoint\Model\Api\BuildStockPointPostData $buildStockPointPostData,
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Riki\StockPoint\Model\StockPointRepository $stockPointRepository
     * @param \Riki\Subscription\Helper\StockPoint\Data $stockPointHelper
     * @param \Riki\Customer\Helper\Region $regionHelper
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param AutoAssignStockPointLogger $assignStockPointLogger
     */
    public function __construct(
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\StockPoint\Helper\Data $dataHelper,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $fileSystem,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Riki\SubscriptionCourse\Helper\Data $subCourseHelper,
        \Riki\Subscription\Helper\Profile\Data $profileHelper,
        \Riki\StockPoint\Model\Api\BuildStockPointPostData $buildStockPointPostData,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Riki\StockPoint\Model\StockPointRepository $stockPointRepository,
        \Riki\Subscription\Helper\StockPoint\Data $stockPointHelper,
        \Riki\Customer\Helper\Region $regionHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        AutoAssignStockPointLogger $assignStockPointLogger
    ) {
        $this->sftp = $sftp;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->dataHelper = $dataHelper;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->directoryList = $directoryList;
        $this->fileSystem = $fileSystem;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->profileRepository = $profileRepository;
        $this->addressRepository = $addressRepository;
        $this->subCourseHelper = $subCourseHelper;
        $this->profileHelper = $profileHelper;
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->productRepository = $productRepository;
        $this->stockPointRepository = $stockPointRepository;
        $this->stockPointHelper = $stockPointHelper;
        $this->regionHelper = $regionHelper;
        $this->connection = $resourceConnection->getConnection('sales');
        $this->assignStockPointLogger = $assignStockPointLogger;
    }

    /**
     * Execute
     *
     * @return void
     */
    public function execute()
    {
        $date = $this->timezone->date();
        $needDate = $date->format('Y-m-d H:i:s');
        $needDateFile = $date->format('YmdHis');

        $this->assignStockPointLogger->info(sprintf(
            "Auto assign Stock Point run at: %s",
            $needDate
        ));

        // Get import file
        $data = $this->getSftpFiles($needDateFile);

        if (!$data) {
            return;
        }

        if ($data == 2) {
            $this->assignStockPointLogger->info("CSV files not found.");
            return;
        }

        // Step 1: Validate data in CSV file
        $this->assignStockPointLogger->info("Start validate CSV file.");
        $dataValidated = $this->validateCsvData($data);

        if (empty($dataValidated)) {
            // No row pass validate data.
            $this->assignStockPointLogger->info("There are no rows pass the validate data.");
            $this->assignStockPointLogger->info("End validate CSV file.");
            return;
        }
        $this->assignStockPointLogger->info("End validate CSV file.");

        // Loop validated data.
        foreach ($dataValidated as $rowNum => $rowData) {
            // Step 2: Load profiles meet conditions
            $this->assignStockPointLogger->info(sprintf(
                "Row [%u]: Start load profiles meet conditions.",
                $rowNum
            ));

            $profilesListMeetConditions = $this->processLoadProfiles($rowData);
            if (empty($profilesListMeetConditions)) {
                // No profile meet conditions.
                $this->assignStockPointLogger->info(sprintf(
                    "Row [%u]: There are no profiles meet conditions.",
                    $rowNum
                ));
                $this->assignStockPointLogger->info(sprintf(
                    "Row [%u]: End load profiles meet conditions.",
                    $rowNum
                ));

                continue;
            }
            $this->assignStockPointLogger->info(sprintf(
                "Row [%u]: End load profiles meet conditions.",
                $rowNum
            ));

            // Step 3: Calculate distance for each profile within delivery_distance
            $this->assignStockPointLogger->info(sprintf(
                "Row [%u]: Start calculate distance for each profile.",
                $rowNum
            ));

            $profilesListWithDistance = $this->processCalculateDistanceForProfiles(
                $profilesListMeetConditions,
                $rowData
            );
            if (empty($profilesListWithDistance)) {
                // No profile has distance between stock point and customer_address is within delivery_distance
                $this->assignStockPointLogger->info(sprintf(
                    "Row [%u]: There are no profiles has distance between stock point and customer_address is within delivery_distance.",
                    $rowNum
                ));
                $this->assignStockPointLogger->info(sprintf(
                    "Row [%u]: End calculate distance for each profile.",
                    $rowNum
                ));
                continue;
            }
            $this->assignStockPointLogger->info(sprintf(
                "Row [%u]: End calculate distance for each profile.",
                $rowNum
            ));

            // Step 4: Sort subscription profiles by Distance ASC and assign sub profiles to Stock Point
            $this->assignStockPointLogger->info(sprintf(
                "Row [%u]: Start assign each profile to Stock Point.",
                $rowNum
            ));
            $this->processAssignProfilesToStockPoint($profilesListWithDistance, $rowData);
            $this->assignStockPointLogger->info(sprintf(
                "Row [%u]: End assign each profile to Stock Point.",
                $rowNum
            ));
        }
    }

    /**
     * Get Sftp file
     *
     * @param $needDateFile
     * @return array|boolean
     * @throws \Exception
     */
    public function getSftpFiles($needDateFile)
    {
        // Connect to Sftp
        $host = $this->dataHelper->getSftpHost();
        $port = $this->dataHelper->getSftpPort();
        $username = $this->dataHelper->getSftpUser();
        $password = $this->dataHelper->getSftpPass();

        try {
            $this->sftp->open(
                [
                    'host' => $host . ':' . $port,
                    'username' => $username,
                    'password' => $password,
                    'timeout' => 300
                ]
            );
        } catch (\Exception $e) {
            $this->sftp->close();
            $this->assignStockPointLogger->info("Could not connect to SFTP server.");
            return false;
        }
        // Get root path in sftp
        $sfptRoot = $this->sftp->pwd();

        // Check Sftp location
        $sftpLocation = $this->dataHelper->getStockPointLocation();
        $dirList = explode('/', $sftpLocation);

        foreach ($dirList as $dir) {
            if ($dir != '') {
                try {
                    if (!$this->sftp->cd($dir)) {
                        $this->assignStockPointLogger->info(sprintf(
                            "Location [%s] in sFTP does not exist.",
                            $sftpLocation
                        ));
                        return false;
                    }
                } catch (\Exception $e) {
                    $this->assignStockPointLogger->info(sprintf(
                        "Location [%s] in sFTP does not exist.",
                        $sftpLocation
                    ));
                    return false;
                }
            }
        }

        // Create done folder in sftp
        $baseDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $remoteFolder = "remote";
        $doneFolder = "done";

        $pathToDoneFolder = str_replace($remoteFolder, $doneFolder, $sftpLocation);
        $pathToDoneFolder = $sfptRoot . '/' . $pathToDoneFolder;
        if (!$this->sftp->cd($pathToDoneFolder)) {
            $this->sftp->cd($sftpLocation);
            $this->sftp->cd('..');
            $this->sftp->mkdir('/' . $doneFolder, 0777);
        }

        // Create import folder in local
        $remotePath = $sfptRoot . '/' . $sftpLocation;
        $this->sftp->cd($remotePath);
        $files = $this->sftp->rawls();
        $filesAll = [];
        $localPath = $baseDir . '/import/stockpoint';
        $localPathShort = 'import/stockpoint';

        $fileObject = new File();
        if (!$fileObject->isExists($localPath)) {
            $fileObject->createDirectory($localPath, 0777);
        }

        // Check CSV file
        $pattern = "/^" . self::FILE_NAME_PATTERN . "/";
        foreach ($files as $key => $file) {
            if (!in_array($key, ['.', '..'])) {
                $pre = preg_match($pattern, $key);
                if ($pre) {
                    $extension = substr($key, strrpos($key, '.'));
                    if (strtolower($extension) == ".csv") {
                        $filesAll[] = $key;
                        // Download CSV file to local
                        $this->sftp->read($remotePath . '/' . $key, $localPath . '/' . $key);
                        break;
                    } else {
                        $this->assignStockPointLogger->info(sprintf(
                            "File [%s] is not CSV extension.",
                            $key
                        ));
                        return false;
                    }
                } else {
                    $this->assignStockPointLogger->info(sprintf(
                        "File [%s] does not match the file name [%s].",
                        $key,
                        $pattern
                    ));
                    return false;
                }
            }
        }

        // Get CSV data
        $data = [];
        if (sizeof($filesAll) > 0) {
            foreach ($filesAll as $filename) {
                if ($this->fileSystem->isExists($localPath . '/' . $filename)) {
                    $newFileName = str_replace('.csv', '_' . $needDateFile . '.csv', $filename);
                    $done = str_replace($remoteFolder, $doneFolder, $sftpLocation . '/' . $newFileName);
                    $done = $sfptRoot . '/' . $done;

                    // Remove if already exist in done folder
                    $this->sftp->rm($done);
                    $this->sftp->mv($remotePath . '/' . $filename, $done);

                    $contentFile = $this->dataHelper->getCsvData($baseDir . '/' . $localPathShort . '/' . $filename);

                    if ($contentFile == null || $contentFile == '') {
                        $data[$filename] = 1;
                    } else {
                        $data[$filename] = $contentFile;
                    }

                    try {
                        if ($fileObject->isExists($localPath . '/' . $filename)) {
                            $fileObject->deleteFile($localPath . '/' . $filename);
                        }
                    } catch (\Exception $e) {
                        $this->assignStockPointLogger->info($e->getMessage());
                    }
                }
            }
        }

        $this->sftp->close();
        if (sizeof($data) > 0) {
            return $data;
        } else {
            // Import file does not exists
            return 2;
        }
    }

    /**
     * Validate Csv data
     *
     * @param $data
     * @return array
     */
    public function validateCsvData($data)
    {
        $dataValidated = [];
        foreach ($data as $filename => $content) {
            if ($content == 1) {
                $this->assignStockPointLogger->info(sprintf(
                    "Content of file [%s] is null.",
                    $filename
                ));
            } else {
                // Validate data
                foreach ($content as $rowNum => $rowData) {
                    if ($this->validateRow($rowNum, $rowData)) {
                        // Add this row
                        $dataValidated[$rowNum] = $data[$filename][$rowNum];
                    }
                }
            }
        }

        return $dataValidated;
    }

    /**
     * Validate row
     *
     * @param int $rowNum
     * @param array $rowData
     *
     * @return boolean
     */
    public function validateRow($rowNum, array $rowData)
    {
        $isValid = true;

        foreach ($rowData as $key => $value) {
            if (array_key_exists($key, $this->columnDefine) && $this->columnDefine[$key]['required']) {
                if (!empty($value) || $value == 0) {
                    if (!$this->validateDataType($key, $value, $rowNum)) {
                        $isValid = false;
                    }
                } else {
                    $this->assignStockPointLogger->info(sprintf(
                        "Row [%u]: field [%s] is required field.",
                        $rowNum,
                        $this->columnDefine[$key]['field_name']
                    ));
                    $isValid = false;
                }
            }
        }

        return $isValid;
    }

    /**
     * Validate data type
     *
     * @param $key
     * @param $value
     * @param $rowNum
     *
     * @return boolean
     */
    public function validateDataType($key, $value, $rowNum)
    {
        // Check data type is text
        if ($this->columnDefine[$key]['type'] == 'text') {
            // Check default value
            if (isset($this->columnDefine[$key]['default_value'])
                && $value != $this->columnDefine[$key]['default_value']
            ) {
                $this->assignStockPointLogger->info(sprintf(
                    "Row [%u]: field [%s] is different with default value [%s].",
                    $rowNum,
                    $this->columnDefine[$key]['field_name'],
                    $this->columnDefine[$key]['default_value']
                ));
                return false;
            }

            // Check max length
            if (strlen($value) > $this->columnDefine[$key]['length']) {
                $this->assignStockPointLogger->info(sprintf(
                    "Row [%u]: field [%s] is longer than [%u] characters.",
                    $rowNum,
                    $this->columnDefine[$key]['field_name'],
                    $this->columnDefine[$key]['length']
                ));
                return false;
            }
        }

        // Check data type is number
        if ($this->columnDefine[$key]['type'] == 'number') {
            if (!is_numeric($value)) {
                $this->assignStockPointLogger->info(sprintf(
                    "Row [%u]: field [%s] is not a number.",
                    $rowNum,
                    $this->columnDefine[$key]['field_name']
                ));
                return false;
            }
        }

        // Check data type is date
        if ($this->columnDefine[$key]['type'] == 'date') {
            $d = new DateTime();
            try {
                if ($d->formatDate($value, false) != $value) {
                    $this->assignStockPointLogger->info(sprintf(
                        "Row [%u]: field [%s] is not a date time format.",
                        $rowNum,
                        $this->columnDefine[$key]['field_name']
                    ));
                    return false;
                }
            } catch (\Exception $e) {
                $this->assignStockPointLogger->info(sprintf(
                    "Row [%u]: field [%s] is not a date time format.",
                    $rowNum,
                    $this->columnDefine[$key]['field_name']
                ));
                return false;
            }
        }

        // Check data type is int_number
        if ($this->columnDefine[$key]['type'] == 'int_number') {
            if (!is_int($value + 0)) {
                $this->assignStockPointLogger->info(sprintf(
                    "Row [%u]: field [%s] is not an integer number.",
                    $rowNum,
                    $this->columnDefine[$key]['field_name']
                ));
                return false;
            } else {
                // Check greater than 0
                if ($value <= 0) {
                    $this->assignStockPointLogger->info(sprintf(
                        "Row [%u]: field [%s] must be a positive value.",
                        $rowNum,
                        $this->columnDefine[$key]['field_name']
                    ));
                    return false;
                }
            }
        }

        // Check data type is list (json format)
        if ($this->columnDefine[$key]['type'] == 'list') {
            // Replace ';' to ',' for stock_point_system_data
            if ($this->columnDefine[$key]['field_name'] == 'stock_point_system_data') {
                $value = str_replace(";", ",", $value);

                if (!(is_string($value)
                    && is_array(json_decode($value, true))
                    && (json_last_error() == JSON_ERROR_NONE)
                )) {
                    $this->assignStockPointLogger->info(sprintf(
                        "Row [%u]: field [%s] is invalid format.",
                        $rowNum,
                        $this->columnDefine[$key]['field_name']
                    ));
                    return false;
                }
            } else {
                if (preg_match('/^[0-9,]*$/', $value)) {
                    $value = explode(',', $value);
                    if (!is_array($value)) {
                        $this->assignStockPointLogger->info(sprintf(
                            "Row [%u]: field [%s] is invalid format.",
                            $rowNum,
                            $this->columnDefine[$key]['field_name']
                        ));
                        return false;
                    }
                } else {
                    $this->assignStockPointLogger->info(sprintf(
                        "Row [%u]: field [%s] is invalid format.",
                        $rowNum,
                        $this->columnDefine[$key]['field_name']
                    ));
                    return false;
                }
            }
        }

        // Check special case: Find region id is exist or not in Magento from csv.stock_point_prefecture
        if ($this->columnDefine[$key]['field_name'] == 'stock_point_prefecture') {
            $regionId = $this->regionHelper->getRegionIdByName(trim($value));

            if (!$regionId) {
                $this->assignStockPointLogger->info(sprintf(
                    "Row [%u]: field [%s] is not found in Magento",
                    $rowNum,
                    $this->columnDefine[$key]['field_name']
                ));
                return false;
            }
        }

        return true;
    }

    /**
     * Load profile meet conditions
     *
     * @param array $rowData
     *
     * @return mixed
     */
    public function processLoadProfiles($rowData)
    {
        // Limit number of sub profiles can be assigned to a Stock Point
        $limitNumberProfiles = MAX($this->dataHelper->getLimitNumberSubProfiles(), $rowData[6]);

        $results = [];
        $createdAtSort = $this->sortOrderBuilder
            ->setField('created_date')
            ->setDirection(\Magento\Framework\Data\Collection::SORT_ORDER_DESC)
            ->create();
        $searchResult = $this->searchCriteriaBuilder
            ->addFilter('stock_point_profile_bucket_id', new \Zend_Db_Expr('NULL'), 'is')
            ->addFilter('payment_method', Paygent::CODE)
            ->addFilter('frequency_unit', Frequency::UNIT_MONTH)
            ->addFilter('auto_stock_point_assign_status', 0)
            ->addFilter('type', new \Zend_Db_Expr('NULL'), 'is')
            ->addSortOrder($createdAtSort)
            ->setPageSize($limitNumberProfiles)
            ->create();
        $profileRepository = $this->profileRepository->getList($searchResult);
        foreach ($profileRepository->getItems() as $profile) {
            // If profile is validated fail so it doesn't need to validate again
            if (!in_array($profile->getProfileId(), $this->profileValidatedFail)) {
                $profileData = $profile->getData();
                $productCartModel = $this->profileRepository->getListProductCart($profile->getProfileId());
                $productCartItems = $this->convertProductCart($productCartModel);
                $shippingCustomerAddress = $this->getShippingCustomerAddress($productCartModel);

                // Check profiles meet conditions
                if ($this->isProfileMeetConditions(
                    $profileData,
                    $productCartItems,
                    $shippingCustomerAddress,
                    $rowData
                )) {
                    // Add sub profile meet all conditions
                    $profileData['delivery_time_slot'] = $this->getDeliveryTimeSlotId($productCartModel);
                    $results[$profile->getProfileId()] = [
                        'profile' => $profileData,
                        'address_id' => $shippingCustomerAddress->getId()
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Calculate distance for each profile within delivery_distance
     *
     * @param array $profilesListMeetConditions
     * @param array $rowData
     *
     * @return mixed
     */
    public function processCalculateDistanceForProfiles($profilesListMeetConditions, $rowData)
    {
        $results = [];
        foreach ($profilesListMeetConditions as $profileId => $profileData) {
            try {
                $addressObj = $this->addressRepository->getById($profileData['address_id']);
            } catch (\Exception $e) {
                $this->assignStockPointLogger->info(sprintf(
                    "Profile ID [%s] cannot get customer address [%s] due to error from system [%s].",
                    $profileId,
                    $profileData['address_id'],
                    $e->getMessage()
                ));
                continue;
            }

            if ($addressObj) {
                // Get full address = Street + "," + Prefecture (State/Province) + "," + Postal Code
                $address = $addressObj->getStreet()[0] . "," . $addressObj->getRegion()->getRegion() .
                    "," . $addressObj->getPostCode();
                $geometryHash = $addressObj->getCustomAttribute('geometry_hash');

                $lat = "";
                $long = "";
                $resultGoogleMapApi = [];

                // If profile has geometry_hash is NULL
                if (!$geometryHash) {
                    // Call google Map API to get latitude and longitude
                    $resultGoogleMapApi = $this->dataHelper->callAPIGoogleMap($address);
                } elseif ($geometryHash->getValue() != sha1($address)) {
                    // Call google Map API to get latitude and longitude
                    $resultGoogleMapApi = $this->dataHelper->callAPIGoogleMap($address);
                } else {
                    $latObj = $addressObj->getCustomAttribute('latitude');
                    $longObj = $addressObj->getCustomAttribute('longitude');
                    $lat = ($latObj) ? $latObj->getValue() : '';
                    $long = ($longObj) ? $longObj->getValue() : '';
                }

                if ($resultGoogleMapApi) {
                    if ($resultGoogleMapApi['status'] == self::GOOGLE_STATUS_OK) {
                        // Store geometry_hash, latitude and longitude to table customer address
                        $geometryHash = sha1($address);
                        $lat = $resultGoogleMapApi['results'][0]['geometry']['location']['lat'];
                        $long = $resultGoogleMapApi['results'][0]['geometry']['location']['lng'];

                        $addressObj->setCustomAttribute('geometry_hash', $geometryHash);
                        $addressObj->setCustomAttribute('latitude', $lat);
                        $addressObj->setCustomAttribute('longitude', $long);

                        try {
                            $this->addressRepository->save($addressObj);
                        } catch (\Exception $e) {
                            $this->assignStockPointLogger->info(sprintf(
                                "Profile ID [%s] cannot save customer address [%s] due to error from system [%s].",
                                $profileId,
                                $profileData['address_id'],
                                $e->getMessage()
                            ));
                            continue;
                        }
                    } elseif ($resultGoogleMapApi['status'] == self::GOOGLE_STATUS_ZERO_RESULTS) {
                        $this->assignStockPointLogger->info(sprintf(
                            "Profile ID [%s] is not found latitude and longitude with address [%s].",
                            $profileId,
                            $profileData['address_id']
                        ));
                        continue;
                    } else {
                        $this->assignStockPointLogger->info(sprintf(
                            "Profile ID [%s] has issue with getting latitude and longitude with error [%s : %s].",
                            $profileId,
                            $resultGoogleMapApi['status'],
                            $resultGoogleMapApi['error_message']
                        ));
                        continue;
                    }
                }

                // If latitude and longitude are available
                if ($lat && $long) {
                    // Calculate distance between 2 points
                    $resultDistance = $this->dataHelper->distance($lat, $long, $rowData[10], $rowData[11]);

                    // If distance between stock point and customer_address is within delivery_distance
                    // Make a list of sub profile id and distance for sort later.
                    if ($resultDistance <= $rowData[7]) {
                        $results[$profileId] = [
                            'profile' => $profileData['profile'],
                            'distance' => $resultDistance
                        ];
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Assign profiles to stock point
     *
     * @param array $profilesListWithDistance
     * @param array $rowData
     *
     * @return void
     */
    public function processAssignProfilesToStockPoint($profilesListWithDistance, $rowData)
    {
        // Limit number sub profile can be assigned to a Stock Point
        $limitNumberSubProfile = $rowData[6];
        $profileNumber = 0;

        // Sort list profiles by Distance ASC
        array_multisort(array_column($profilesListWithDistance, 'distance'), SORT_ASC, $profilesListWithDistance);

        foreach ($profilesListWithDistance as $profileId => $profileData) {
            if ($profileNumber < $limitNumberSubProfile) {
                $profileId = $profileData['profile']['profile_id'];
                // Prepare request data
                $dataRequest = [
                    "stock_point_id" => $rowData[0],
                    "profile_id" => $profileId,
                    "stock_point_system_data" => $this->prepareStockPointSystemData($profileData['profile'], $rowData),
                    "delivery_type" => $this->buildStockPointPostData->convertDeliveryTypeStockPoint($rowData[2]),
                    "frequency" => $profileData['profile']['frequency_interval'],
                    "next_delivery_date" => $rowData[3],
                    "next_order_date" => $rowData[4],
                    "delivery_time" => $rowData[5]
                ];

                try {
                    // Call registerDelivery() to Stock Point system.
                    $externalBucket = $this->buildStockPointPostData->callAPIRegisterDelivery($dataRequest);

                    if ($externalBucket && $externalBucket['call_api'] == "success") {
                        // Add stock_point_id into table stock_point if it is not existed in Magento
                        $stockPoint = $this->dataHelper->isExistStockPoint($rowData[0]);
                        if (!$stockPoint) {
                            // Convert csv.stock_point_prefecture to region_id
                            $regionId =$this->regionHelper->getRegionIdByName(trim($rowData[15]));

                            $dataStockPoint = [
                                'stock_point_id' => $rowData[0],
                                'stock_point_firstname' => $rowData[18],
                                'stock_point_lastname' => $rowData[17],
                                'stock_point_firstnamekana' => $rowData[20],
                                'stock_point_lastnamekana' => $rowData[19],
                                'stock_point_address' => $rowData[16],
                                'stock_point_prefecture' => $regionId,
                                'stock_point_postcode' => $rowData[14],
                                'stock_point_telephone' => $rowData[21],
                            ];

                            try {
                                $rikiStockPointId = $this->stockPointRepository->saveAndReturnStockPointId($dataStockPoint);
                            } catch (\Exception $e) {
                                throw new \Magento\Framework\Exception\CouldNotSaveException(__(
                                    $e->getMessage()
                                ));
                            }

                            if ($rikiStockPointId) {
                                try {
                                    // Store data to table stock_point_profile_bucket
                                    $profileBucketModel = $this->stockPointHelper->saveBucket(
                                        $rikiStockPointId,
                                        $externalBucket['data']['bucket_id']
                                    );
                                } catch (\Exception $e) {
                                    throw new \Magento\Framework\Exception\CouldNotSaveException(__(
                                        $e->getMessage()
                                    ));
                                }

                                if ($profileBucketModel->getId()) {
                                    // Store data to table subscription_profile
                                    if ($this->processUpdateDataToProfile(
                                        $profileId,
                                        $profileBucketModel->getId(),
                                        $rowData,
                                        $externalBucket
                                    )) {
                                        $profileNumber++;
                                    }
                                }
                            }
                        } else {
                            // Stock point is exist
                            // Get profile_bucket_id from stock_point_id to update this profile
                            $profileBucketId = $this->dataHelper->getBucketIdByStockPointId($stockPoint->getId());

                            if ($profileBucketId) {
                                // Store data to table subscription_profile
                                if ($this->processUpdateDataToProfile(
                                    $profileId,
                                    $profileBucketId,
                                    $rowData,
                                    $externalBucket
                                )) {
                                    $profileNumber++;
                                }
                            } else {
                                $this->assignStockPointLogger->info(sprintf(
                                    "Profile ID [%s] cannot register because cannot find Profile Bucket ID of Riki Stock Point [%s] in DB.",
                                    $profileId,
                                    $stockPoint->getId()
                                ));
                            }
                        }
                    } else {
                        // Get error message when call registerDelivery fail
                        $errorMessage = isset($externalBucket['data']['message']) ? $externalBucket['data']['message']
                            : $externalBucket['data'];

                        $this->assignStockPointLogger->info(sprintf(
                            "Profile ID [%s] cannot register Stock Point due to error from API [%s].",
                            $profileId,
                            $errorMessage
                        ));
                    }
                } catch (\Exception $e) {
                    $this->assignStockPointLogger->info(sprintf(
                        "Profile ID [%s] cannot register Stock Point due to error from System [%s].",
                        $profileId,
                        $e->getMessage()
                    ));
                }
            }
        }

        // Log Profile assigned to Stock Point
        $this->assignStockPointLogger->info(sprintf(
            "There are [%u] profiles assigned to Stock Point [%s].",
            $profileNumber,
            $rowData[0]
        ));
    }

    /**
     * Store data from Api register delivery and CSV to profile.
     *
     * @param int $profileId
     * @param int $profileBucketId
     * @param array $rowData
     * @param array $externalBucket
     *
     * @return boolean
     */
    public function processUpdateDataToProfile($profileId, $profileBucketId, $rowData, $externalBucket)
    {
        $this->connection->beginTransaction();
        try {
            // Store data to table subscription_profile
            $profile = $this->profileRepository->get($profileId);
            if ($profile) {
                // Update this profile
                $profile->setData('stock_point_profile_bucket_id', $profileBucketId);
                $profile->setData(
                    'stock_point_delivery_type',
                    $this->buildStockPointPostData->convertDeliveryTypeStockPoint($rowData[2])
                );
                $profile->setData('stock_point_delivery_information', $rowData[13]);
                $profile->setData('next_delivery_date', $externalBucket['data']['next_delivery_date']);
                $profile->setData('next_order_date', $externalBucket['data']['next_order_date']);
                $profile->setData('auto_stock_point_assign_status', 2);

                $this->profileRepository->save($profile);

                // Update profile product cart
                $this->processUpdateDataToProfileProductCart($profileId, $rowData, $externalBucket);

                $this->connection->commit();

                // Send email notify
                $emailCustomer = $this->dataHelper->getCustomerById($profile->getCustomerId())->getEmail();
                $this->dataHelper->sendMailNotify($emailCustomer);

                // Log Profile assigned to Stock Point
                $this->assignStockPointLogger->info(sprintf(
                    "Profile ID [%s] assigned to Stock Point [%s].",
                    $profileId,
                    $rowData[0]
                ));
            }
        } catch (\Magento\Framework\Exception\NotFoundException $e) {
            $this->assignStockPointLogger->info(sprintf(
                "Profile ID [%s] assigned to Stock Point [%s] but can not send email notify to consumer due to error from System [%s].",
                $profileId,
                $rowData[0],
                $e->getMessage()
            ));
        } catch (\Magento\Framework\Exception\MailException $e) {
            $this->assignStockPointLogger->info(sprintf(
                "Profile ID [%s] assigned to Stock Point [%s] but can not send email notify to consumer due to error from System [%s].",
                $profileId,
                $rowData[0],
                $e->getMessage()
            ));
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $this->assignStockPointLogger->info(sprintf(
                "Profile ID [%s] cannot register to Stock Point [%s] due to error from System [%s].",
                $profileId,
                $rowData[0],
                $e->getMessage()
            ));

            return false;
        }

        return true;
    }

    /**
     * Store data from Api register delivery and CSV to profile product cart.
     *
     * @param int $profileId
     * @param array $rowData
     * @param array $externalBucket
     *
     * @return void
     */
    public function processUpdateDataToProfileProductCart($profileId, $rowData, $externalBucket)
    {
        // Store data to table subscription_profile_product_cart
        $productCartModel = $this->profileRepository->getListProductCart($profileId);
        $deliveryTimeLot = $this->buildStockPointPostData->validateDeliveryTimeSlot($rowData[5]);
        if (!$deliveryTimeLot) {
            // If the value delivery time slot does not match on magento, set delivery_time_lot = -1
            $deliveryTimeLot = -1;
        }

        foreach ($productCartModel->getItems() as $productCartItem) {
            // Update this profile product cart
            $productCartItem->setData('delivery_date', $externalBucket['data']['next_delivery_date']);
            $productCartItem->setData('delivery_time_slot', $deliveryTimeLot);
            $productCartItem->setData('stock_point_discount_rate', $rowData[12]);

            $productCartItem->save();
        }
    }

    /**
     * Is profile meet conditions
     *
     * @param array $profileData
     * @param array $productCartItems
     * @param array $shippingCustomerAddress
     * @param array $rowData
     *
     * @return boolean
     */
    public function isProfileMeetConditions($profileData, $productCartItems, $shippingCustomerAddress, $rowData)
    {
        /* Step 1: Validate condition with data in DB */
        if (!$this->isProfileMeetConditionsWithDataInDB($profileData, $productCartItems)) {
            // Add profile validated fail to $this->profileValidatedFail
            if (!in_array($profileData['profile_id'], $this->profileValidatedFail)) {
                array_push($this->profileValidatedFail, $profileData['profile_id']);
            }
            return false;
        }

        /* Step 2: Validate condition with data in CSV file */
        if (!$this->isProfileMeetConditionsWithDataInCSV($profileData, $shippingCustomerAddress, $rowData)) {
            return false;
        }

        return true;
    }

    /**
     * Is profile meet conditions with data in DB
     *
     * @param array $profileData
     * @param array $productCartItems
     *
     * @return boolean
     */
    public function isProfileMeetConditionsWithDataInDB($profileData, $productCartItems)
    {
        // Check subscription profile doesn't have TEMP
        $tempProfile = $this->profileHelper->getTmpProfile($profileData['profile_id']);
        if ($tempProfile) {
            return false;
        }

        // Check subscription course of profile is not HANPUKAI.
        $subType = $this->subCourseHelper->getSubscriptionCourseType($profileData['course_id']);
        if ($subType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
            return false;
        }

        // All products of sub profile have stock_point_allowed = YES
        // AND Group of Delivery Type = Normal (excluded for all kinds of free gifts)
        if (!$this->validateStockPointProduct->checkAllProductAllowStockPoint($productCartItems)) {
            return false;
        }

        if (!$this->validateStockPointProduct->checkAllProductIsDeliveryTypeNormal($productCartItems)) {
            return false;
        }

        // Check all products has stock in Hitachi.
        if (!$this->validateStockPointProduct->checkAllProductInStockWareHouse($productCartItems)) {
            return false;
        }

        return true;
    }

    /**
     * Is profile meet conditions with data in CSV
     *
     * @param array $profileData
     * @param array $shippingCustomerAddress
     * @param array $rowData
     *
     * @return boolean
     */
    public function isProfileMeetConditionsWithDataInCSV($profileData, $shippingCustomerAddress, $rowData)
    {
        // Check sub profile next_delivery_date will be
        // between csv.target_delivery_date_from AND csv.target_delivery_date_to
        if (!(strtotime($profileData['next_delivery_date']) >= strtotime($rowData[8]) &&
            strtotime($profileData['next_delivery_date']) < strtotime($rowData[9]))) {
            return false;
        }

        // Sub profiles has frequency interval within csv.frequency_interval
        if (!in_array($profileData['frequency_interval'], explode(',', $rowData[23]))) {
            return false;
        }

        // Sub profiles has postal code within csv.candidate_postcode
        $postCode = $shippingCustomerAddress->getPostCode();
        if (!in_array(str_replace('-', '', $postCode), explode(',', $rowData[24]))) {
            return false;
        }

        return true;
    }

    /**
     * Convert Product Cart
     *
     * @param $productCartModel
     *
     * @return mixed
     */
    public function convertProductCart($productCartModel)
    {
        $productCartItems = [];
        foreach ($productCartModel->getItems() as $product) {
            if ($product->getData(\Riki\Subscription\Model\Profile\Profile::PARENT_ITEM_ID) == 0) {
                /** @var */
                $productObj = $this->productRepository->getById($product->getData('product_id'));
                $productCartItems[] = [
                    'product' => $productObj,
                    'qty' => $product->getQty()
                ];
            }
        }

        return $productCartItems;
    }

    /**
     * Get shipping customer address
     *
     * @param $productCartModel
     *
     * @return mixed
     */
    public function getShippingCustomerAddress($productCartModel)
    {
        $address = [];
        foreach ($productCartModel->getItems() as $product) {
            if ($addressId = $product->getData('shipping_address_id')) {
                // Just need find one shipping address id because subscription is same shipping address
                $address = $this->addressRepository->getById($addressId);
                break;
            }
        }

        return $address;
    }

    /**
     * Get shipping customer address
     *
     * @param $productCartModel
     *
     * @return mixed
     */
    public function getDeliveryTimeSlotId($productCartModel)
    {
        $deliveryTimeSlot = null;
        foreach ($productCartModel->getItems() as $product) {
            if ($product->getData('delivery_time_slot')) {
                // Just need find one delivery_time_slot because subscription is same
                $deliveryTimeSlot = $product->getData('delivery_time_slot');
                break;
            }
        }

        return $deliveryTimeSlot;
    }

    /**
     * Prepare data for stock point system data
     *
     * @param $profileData
     * @param $rowData
     *
     * @return mixed
     */
    public function prepareStockPointSystemData($profileData, $rowData)
    {
        $stockPointSystemData = str_replace(';', ',', $rowData[1]);
        $stockPointSystemData = json_decode($stockPointSystemData, true);

        foreach ($stockPointSystemData as $key => $value) {
            if ($key == "request_date") {
                $stockPointSystemData[$key] = date('d', strtotime($profileData['next_delivery_date']));
                break;
            }
        }

        return $stockPointSystemData;
    }
}
