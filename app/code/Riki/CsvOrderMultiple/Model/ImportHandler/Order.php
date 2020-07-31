<?php

namespace Riki\CsvOrderMultiple\Model\ImportHandler;

use Magento\Framework\App\ResourceConnection;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

class Order extends \Magento\ImportExport\Model\Import\Entity\AbstractEntity
{
    const DEFAULT_COUNTRY = 'JP';

    const UPLOADED_BY = 'uploaded_by';

    const COL_ORIGINAL_UNIQUE_ID = 'original_unique_id';
    const COL_PAYMENT_METHOD = 'payment_method';
    const COL_BUSINESS_CODE = 'business_code';
    const COL_BILL_LASTNAME = 'bill_lastname';
    const COL_BILL_FIRSTNAME = 'bill_firstname';
    const MESSAGE_ERROR_1 = 'Value for \'%s\' attribute contains incorrect value, ';
    const MESSAGE_ERROR_2 = 'see acceptable values on settings specified for Admin.';
    const MESSAGE_DELIVERY_1 = 'Warehouse/delivery type/prefecture "%s" is not existed or inactive';
    protected $username;

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $messageTemplates = [
        RowValidatorInterface::ERROR_SKU_NOT_FOUND => 'Product with specified SKU not found.',
        RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_TYPE => '%s is not %s data type.',
        RowValidatorInterface::ERROR_VALUE_IS_REQUIRED => '%s is required.',
        RowValidatorInterface::ERROR_EXCEEDED_MAX_LENGTH => '%s max length is over %s characters.',
        RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_OPTION => self::MESSAGE_ERROR_1 .self::MESSAGE_ERROR_2 ,
        RowValidatorInterface::ERROR_DUPLICATE_UNIQUE_ATTRIBUTE => 'Duplicated unique attribute.',
        RowValidatorInterface::ERROR_INVALID_DATE_FORMAT => '%s must be in format YYYY/MM/DD.',
        RowValidatorInterface::ERROR_INVALID_EMAIL => '"%s" is not email format.',
        RowValidatorInterface::ERROR_INVALID_PHONE_NUMBER_FORMAT => '"%s" is not phone number format.',
        RowValidatorInterface::ERROR_INVALID_ZIP_CODE_FORMAT => '"%s" is not postcode format.',
        RowValidatorInterface::ERROR_INVALID_WBS_FORMAT => '"%s" is not WBS format.',
        RowValidatorInterface::ERROR_INVALID_GIFT_WRAPPING_CODE => '"%s" is not valid gift code for product %s.',
        RowValidatorInterface::ERROR_INVALID_BUSINESS_CODE => '"%s" is not valid business code.',
        RowValidatorInterface::ERROR_INVALID_PAYMENT_METHOD => '"%s" is not allowed.',
        RowValidatorInterface::ERROR_INVALID_WAREHOUSE_CODE => 'Warehouse value of "%s" is not existed or inactive',
        RowValidatorInterface::ERROR_WAREHOUSE_DELIVERY_TYPE => self::MESSAGE_DELIVERY_1,
        RowValidatorInterface::ERROR_WAREHOUSE_STOCK_STATUS => 'Warehouse "%s" is not enough stock for the order',
        RowValidatorInterface::ERROR_SKU_DISABLE => '"%s" has been disabled.',
    ];

    protected $fieldsProperties = [
        'original_unique_id' => [
            'type' => 'varchar',
            'len' => 24,
            'is_unique' => true
        ],
        'order_channel' => [
            'type' => 'select',
            'options' => ['fax', 'call', 'postcard', 'email'],
            'is_required' => true
        ],
        'campaign_id' => [
            'type' => 'varchar',
            'len' => 255
        ],
        'coupon_code' => [
            'type' => 'varchar',
            'len' => 255
        ],
        'order_type' => [
            'type' => 'select',
            'options' => [1, 2, 3],
            'is_required' => true
        ],
        'original_order_id' => [
            'type' => 'varchar',
            'len' => 12
        ],
        'siebel_enquiry_id' => [
            'type' => 'varchar',
            'len' => 16
        ],
        'replacement_reason' => [
            'type' => 'select'
        ],
        'order_wbs' => [
            'type' => 'wbs'
        ],
        'email' => [
            'type' => 'email',
            'len' => 256,
            'is_required' => true
        ],
        'business_code' => [
            'type' => 'varchar',
            'len' => 10
        ],
        'birthdate' => [
            'type' => 'date'
        ],
        'bill_lastname' => [
            'type' => 'varchar',
            'len' => 255,
            'is_required' => true
        ],
        'bill_firstname' => [
            'type' => 'varchar',
            'len' => 255,
            'is_required' => true
        ],
        'bill_lastname_kana' => [
            'type' => 'varchar',
            'len' => 255,
            'is_required' => true
        ],
        'bill_firstname_kana' => [
            'type' => 'varchar',
            'len' => 255,
            'is_required' => true
        ],
        'bill_postcode' => [
            'type' => 'zipcode',
            'len' => 8,
            'is_required' => true
        ],
        'bill_region' => [
            'type' => 'select',
            'is_required' => true
        ],
        'bill_address' => [
            'type' => 'varchar',
            'len' => 255,
            'is_required' => true
        ],
        'bill_phonenumber' => [
            'type' => 'phone_number',
            'len' => 12,
            'is_required' => true
        ],
        'ship_lastname' => [
            'type' => 'varchar',
            'len' => 255,
            'is_required' => true
        ],
        'ship_firstname' => [
            'type' => 'varchar',
            'len' => 255,
            'is_required' => true
        ],
        'ship_lastname_kana' => [
            'type' => 'varchar',
            'len' => 255,
            'is_required' => true
        ],
        'ship_firstname_kana' => [
            'type' => 'varchar',
            'len' => 255,
            'is_required' => true
        ],
        'ship_zipcode' => [
            'type' => 'zipcode',
            'len' => 8,
            'is_required' => false
        ],
        'ship_region' => [
            'type' => 'select',
            'is_required' => true
        ],
        'ship_address' => [
            'type' => 'varchar',
            'len' => 100,
            'is_required' => true
        ],
        'ship_phonenumber' => [
            'type' => 'phone_number',
            'len' => 12,
            'is_required' => true
        ],
        'payment_method' => [
            'type' => 'select',
            'options' => ['free', 'invoicedbasedpayment', 'cashondelivery', 'cvspayment'],
            'is_required' => true
        ],
        'free_delivery' => [
            'type' => 'select',
            'options' => [0, 1],
            'is_required' => true
        ],
        'free_delivery_wbs' => [
            'type' => 'wbs'
        ],
        'cod_free_free' => [
            'type' => 'select',
            'options' => [0, 1],
            'is_required' => true
        ],
        'free_payment_wbs' => [
            'type' => 'wbs'
        ],
        'delivery_date' => [
            'type' => 'date'
        ],
        'delivery_time' => [
            'type' => 'select',
            'options' => [1, 2, 3, 4, 5, 6]
        ],
        'order_comment' => [
            'type' => 'text',
            'len' => 256
        ],
        'products' => [
            'type' => 'text',
            'is_required' => true
        ],
        'warehouse_code' => [
            'type' => 'varchar',
            'is_required' => false
        ]
    ];

    protected $permanentAttributes = [
        'original_unique_id',
        'order_channel',
        'options',
        'campaign_id',
        'coupon_code',
        'order_type',
        'options',
        'original_order_id',
        'siebel_enquiry_id',
        'replacement_reason',
        'order_wbs',
        'email',
        'business_code',
        'birthdate',
        'bill_lastname',
        'bill_firstname',
        'bill_lastname_kana',
        'bill_firstname_kana',
        'bill_postcode',
        'bill_region',
        'bill_address',
        'bill_phonenumber',
        'ship_lastname',
        'ship_firstname',
        'ship_lastname_kana',
        'ship_firstname_kana',
        'ship_zipcode',
        'ship_region',
        'ship_address',
        'ship_phonenumber',
        'payment_method',
        'options',
        'free_delivery',
        'options',
        'free_delivery_wbs',
        'cod_free_free',
        'options',
        'free_payment_wbs',
        'delivery_date',
        'delivery_time',
        'options',
        'order_comment',
        'products',
        'warehouse_code'
    ];

    /** @var \Riki\CsvOrderMultiple\Model\ResourceModel\ImportFactory */
    protected $resourceFactory;

    /** @var Validator */
    protected $validator;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    /** @var \Magento\Directory\Model\Country */
    protected $country;

    /** @var \Magento\Framework\Locale\ResolverInterface */
    protected $localeResolver;

    /** @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory */
    protected $regionCollectionFactory;

    /**
     * @var \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory
     */
    protected $pointOfSaleCollectionFactory;

    /**
     * @var array
     */
    protected $regions;

    /**
     * @var array
     */
    protected $wareHouses;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var array
     */
    protected $regionCode = [];

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * Order constructor.
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param \Magento\Eav\Model\Config $config
     * @param ResourceConnection $resource
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\ImportExport\Model\Import\Config $importConfig
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Validator $validator
     * @param \Riki\CsvOrderMultiple\Model\ResourceModel\ImportFactory $resourceFactory
     * @param \Magento\Directory\Model\Country $country
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $pointOfSaleCollectionFactory
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Stdlib\StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\ImportExport\Model\Import\Config $importConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\CsvOrderMultiple\Model\ImportHandler\Validator $validator,
        \Riki\CsvOrderMultiple\Model\ResourceModel\ImportFactory $resourceFactory,
        \Magento\Directory\Model\Country $country,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $pointOfSaleCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        $this->_eventManager = $eventManager;
        $this->_importConfig = $importConfig;
        $this->_logger = $logger;
        $this->resource = $resource;
        $this->filesystem = $filesystem;
        $this->indexerRegistry = $indexerRegistry;
        $this->validator = $validator;
        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->string = $string;
        $this->errorAggregator = $errorAggregator;
        $this->scopeConfig = $scopeConfig;
        $this->country = $country;
        $this->request =$request;
        $this->localeResolver = $localeResolver;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->pointOfSaleCollectionFactory = $pointOfSaleCollectionFactory;
        $this->_messageTemplates = $this->messageTemplates;
        $this->_dataSourceModel = $importData;
        $this->resourceFactory = $resourceFactory;
        $this->_connection = $resourceConnection;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\Serialize\Serializer\Json::class
        );
    }

    /**
     * @param $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Check one attribute. Can be overridden in child.
     *
     * @param string $attrCode Attribute code
     * @param array $attrParams Attribute params
     * @param array $rowData Row data
     * @param int $rowNum
     * @return bool
     */
    public function isAttributeValid($attrCode, array $attrParams, array $rowData, $rowNum)
    {
        if (!$this->getvalidator()->isAttributeValid($attrCode, $attrParams, $rowData)) {
            foreach ($this->getvalidator()->getMessages() as $message) {
                $this->addRowError($message, $rowNum, $attrCode);
            }
            return false;
        }
        return true;
    }

    private function getvalidator()
    {
         $this->validator->init($this);
         return $this->validator;
    }

    /**
     * Validate data row.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return bool
     * @throws \Zend_Validate_Exception
     */
    public function validateRow(array $rowData, $rowNum)
    {
        if (isset($this->_validatedRows[$rowNum])) {
            // check that row is already validated
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        $this->_validatedRows[$rowNum] = true;

        if (!$this->getvalidator()->isValid($rowData)) {
            foreach ($this->getvalidator()->getMessages() as $message) {
                $this->addRowError($message, $rowNum);
            }
        }

        // SKU is specified, row is SCOPE_DEFAULT, new product block begins
        $this->_processedEntitiesCount++;

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Validate data rows and save bunches to DB
     *
     * @return $this|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    protected function _saveValidatedBunches()
    {
        $source = $this->_getSource();
        $source->rewind();
        while ($source->valid()) {
            try {
                $rowData = $source->current();
            } catch (\InvalidArgumentException $e) {
                $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                $this->_processedRowsCount++;
                $source->next();
                continue;
            }

            $this->validateRow($rowData, $source->key());
            $source->next();
        }

        return parent::_saveValidatedBunches();
    }

    /**
     * @param $code
     * @return bool|mixed
     */
    public function getAttributeProperties($code)
    {
        $this->fieldsProperties['replacement_reason']['options'] = $this->getReplacementReasons();
        $this->fieldsProperties['bill_region']['options'] = $this->getRegions();
        $this->fieldsProperties['ship_region']['options'] = $this->getRegions();
        $this->fieldsProperties['warehouse_code']['options'] = $this->getWarehouseCode();
        $this->_permanentAttributes = $this->permanentAttributes;

        if (isset($this->fieldsProperties[$code])) {
            return $this->fieldsProperties[$code];
        }

        return false;
    }

    /**
     * Import data rows.
     *
     * @return bool
     * @throws \Zend_Validate_Exception
     */
    protected function _importData()
    {
        while ($bunch =$this->_dataSourceModel->getNextBunch()) {
            $entityRowsIn = [];
            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }

                $entityRowsIn[] = [
                    'original_unique_id' => $rowData[self::COL_ORIGINAL_UNIQUE_ID],
                    'uploaded_by' => $this->username,
                    'status' => \Riki\CsvOrderMultiple\Api\Data\StatusInterface::IMPORT_WAITING,
                    'payment_method' => $rowData[self::COL_PAYMENT_METHOD],
                    'consumer_name' => $rowData[self::COL_BILL_FIRSTNAME] . ' ' . $rowData[self::COL_BILL_LASTNAME],
                    'business_code' => $rowData[self::COL_BUSINESS_CODE],
                    'data_json_order' => \Zend_Json::encode($this->convertDataOrder($rowData))
                ];
            }

            $this->_saveImportEntity($entityRowsIn);
        }
        return true;
    }

    /**
     * @param array $entityRowsIn
     * @return $this
     */
    protected function _saveImportEntity(array $entityRowsIn)
    {
        static $entityTable = null;
        $this->countItemsCreated += count($entityRowsIn);

        if (!$entityTable) {
            $entityTable = $this->resourceFactory->create()->getMainTable();
        }
        if ($entityRowsIn) {
            $this->_connection->getConnection('sales')->insertMultiple($entityTable, $entityRowsIn);
        }
        return $this;
    }

    /**
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getReplacementReasons()
    {
        $result = [];
        $optionsConfig = $this->scopeConfig->getValue('riki_order/replacement_order/reason');

        if ($optionsConfig) {
            $options = $this->serializer->unserialize($optionsConfig);

            if (is_array($options)) {
                foreach ($options as $option) {
                    $result[] = $option['code'];
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getRegions()
    {
        if (!$this->regions) {
            $result = [];

            $this->request->setParams(['locale' => 'ja_JP']);
            $this->localeResolver->setLocale('ja_JP');
            $country = $this->country->loadByCode(self::DEFAULT_COUNTRY);
            $regions = $this->regionCollectionFactory->create(['localeResolver' => $this->localeResolver])
                ->addCountryFilter($country->getId());

            /** @var \Magento\Directory\Model\Region $region */
            foreach ($regions as $region) {
                $result[$region->getId()] = $region->getName();
                $this->regionCode[$region->getCode()] = $region->getName();
            }

            $this->regions = $result;
        }

        return $this->regions;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getWarehouseCode()
    {
        if (!$this->wareHouses) {
            $result = [];

            /** @var \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection $arrWarehouse */
            $arrWarehouse = $this->pointOfSaleCollectionFactory->create();
            if ($arrWarehouse->getSize() > 0) {
                foreach ($arrWarehouse->getItems() as $warehouse) {
                    $result[$warehouse->getStoreCode()] = $warehouse->getId();
                }
            }
            $this->wareHouses = $result;
        }

        return $this->wareHouses;
    }

    /**
     * @param $name
     * @return bool
     */
    protected function getRegionIdByName($name)
    {
        $regions = $this->getRegions();

        return array_search($name, $regions);
    }

    /**
     * EAV entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'sales_order';
    }

    /**
     * @return ProcessingErrorAggregatorInterface
     */
    public function getErrorAggregator()
    {
        foreach ($this->errorMessageTemplates as $errorCode => $message) {
            $this->errorAggregator->addErrorMessageTemplate($errorCode, $message);
        }
        return $this->errorAggregator;
    }

    /**
     * @param $rowData
     * @param $field
     * @return string
     */
    public function getValueOnCol($rowData, $field)
    {
        if (isset($rowData[$field])) {
            return $rowData[$field];
        }
        return '';
    }

    /**
     * @param $products
     * @return null
     */
    public function convertProductItems($products)
    {
        $arrProductItems = [];
        if (!empty($products)) {
            $arrItems = explode(';', $products);
            if (is_array($arrItems) && !empty($arrItems) > 0) {
                foreach ($arrItems as $data) {
                    $item = explode(':', $data);
                    $arrProductItems[] = [
                        'product_sku' => isset($item[0]) ? trim($item[0]) : '',
                        'qty' => isset($item[1]) ? trim($item[1]) : 0,
                        'gift_code' => isset($item[2]) ? trim($item[2]) : '',
                    ];
                }
            }
        }

        return $arrProductItems;
    }

    public function convertWarehouseCodeToId($warehouseCode)
    {
        if ($warehouseCode !=null) {
            $warehouseCode = strtoupper($warehouseCode);
            if (isset($this->wareHouses[$warehouseCode])) {
                return $this->wareHouses[$warehouseCode];
            }
        }
        return '';
    }

    /**
     * @param $rowData
     * @return array
     */
    public function convertDataOrder($rowData)
    {
        $assignedWarehouseId = $this->convertWarehouseCodeToId($this->getValueOnCol($rowData, 'warehouse_code'));
        $arrData = [
            "original_unique_id" => $this->getValueOnCol($rowData, 'original_unique_id'),
            "order_channel" => $this->getValueOnCol($rowData, 'order_channel'),
            "campaign_id" => $this->getValueOnCol($rowData, 'campaign_id'),
            "coupon_code" => $this->getValueOnCol($rowData, 'coupon_code'),
            "order_type" => $this->getValueOnCol($rowData, 'order_type'),
            "original_order_id" => $this->getValueOnCol($rowData, 'original_order_id'),
            "siebel_enquiry_id" => $this->getValueOnCol($rowData, 'siebel_enquiry_id'),
            "replacement_reason" => $this->getValueOnCol($rowData, 'replacement_reason'),
            "order_wbs" => $this->getValueOnCol($rowData, 'order_wbs'),
            "customer" => [
                "email" => $this->getValueOnCol($rowData, 'email'),
                "business_code" => $this->getValueOnCol($rowData, 'business_code'),
                "birthdate" => $this->getValueOnCol($rowData, 'birthdate'),
            ],
            "billingAddress" => [
                "bill_lastname" => $this->getValueOnCol($rowData, 'bill_lastname'),
                "bill_firstname" => $this->getValueOnCol($rowData, 'bill_firstname'),
                "bill_lastname_kana" => $this->getValueOnCol($rowData, 'bill_lastname_kana'),
                "bill_firstname_kana" => $this->getValueOnCol($rowData, 'bill_firstname_kana'),
                "bill_postcode" => $this->getValueOnCol($rowData, 'bill_postcode'),
                "bill_region" => $this->getRegionIdByName($this->getValueOnCol($rowData, 'bill_region')),
                "bill_address" => $this->getValueOnCol($rowData, 'bill_address'),
                "bill_phonenumber" => $this->getValueOnCol($rowData, 'bill_phonenumber'),
            ],
            "shippingAddress" => [
                "ship_lastname" => $this->getValueOnCol($rowData, 'ship_lastname'),
                "ship_firstname" => $this->getValueOnCol($rowData, 'ship_firstname'),
                "ship_lastname_kana" => $this->getValueOnCol($rowData, 'ship_lastname_kana'),
                "ship_firstname_kana" => $this->getValueOnCol($rowData, 'ship_firstname_kana'),
                "ship_zipcode" => $this->getValueOnCol($rowData, 'ship_zipcode'),
                "ship_region" => $this->getRegionIdByName($this->getValueOnCol($rowData, 'ship_region')),
                "ship_address" => $this->getValueOnCol($rowData, 'ship_address'),
                "ship_phonenumber" => $this->getValueOnCol($rowData, 'ship_phonenumber'),
            ],

            "payment_method" => $this->getValueOnCol($rowData, 'payment_method'),
            "free_delivery" => $this->getValueOnCol($rowData, 'free_delivery'),
            "free_delivery_wbs" => $this->getValueOnCol($rowData, 'free_delivery_wbs'),
            "cod_free_free" => $this->getValueOnCol($rowData, 'cod_free_free'),
            "free_payment_wbs" => $this->getValueOnCol($rowData, 'free_payment_wbs'),
            "delivery_date" => $this->getValueOnCol($rowData, 'delivery_date'),
            "delivery_time" => $this->getValueOnCol($rowData, 'delivery_time'),
            "order_comment" => $this->getValueOnCol($rowData, 'order_comment'),
            "assigned_warehouse_id" => $assignedWarehouseId,
            'items' => $this->convertProductItems($this->getValueOnCol($rowData, 'products'))
        ];

        return $arrData;
    }

    /**
     * Get region code by name
     * @param $name
     * @return false|int|string
     */
    public function getRegionCodeByName($name = null)
    {
        $regions = $this->regionCode;
        if ($name !=null) {
            return array_search($name, $regions);
        }
        return null;
    }
}
