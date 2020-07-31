<?php
namespace Riki\AdvancedInventory\Model\ReAssignation\ImportHandler;

use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

class ReAssignation extends \Magento\ImportExport\Model\Import\Entity\AbstractEntity
{
    const ORDER_NUMBER = 'order_increment_id';

    const WAREHOUSE = 'to_warehouse';

    const UPLOADED_BY = 'uploaded_by';

    protected $username;

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        RowValidatorInterface::ERROR_VALUE_IS_REQUIRED => '%s is required.',
        RowValidatorInterface::ERROR_EXCEEDED_MAX_LENGTH => '%s max length is over %s characters.',
        RowValidatorInterface::ERROR_DUPLICATE_UNIQUE_ATTRIBUTE => 'Duplicated unique attribute.',
        RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_OPTION =>
            'Value for \'%s\' attribute contains incorrect value, see acceptable values on settings specified for Admin.',
    ];

    protected $_fieldsProperties = [
        self::ORDER_NUMBER    =>  [
            'type'  =>  'varchar',
            'len'   =>  32,
            'is_required'   =>  true,
            'is_unique' =>  true
        ],
        self::WAREHOUSE    =>  [
            'type'  =>  'varchar',
            'len'   =>  255
        ]
    ];

    /**
     * @var array
     */
    protected $_permanentAttributes = [
        self::ORDER_NUMBER,
        self::WAREHOUSE
    ];

    /**
     * DB data source model.
     *
     * @var \Magento\ImportExport\Model\ResourceModel\Import\Data
     */
    protected $_dataSourceModel;

    /** @var \Riki\AdvancedInventory\Model\ResourceModel\ReAssignationFactory  */
    protected $resourceFactory;

    /** @var Validator  */
    protected $validator;

    /**
     * @var
     */
    protected $warehouseOption;

    /**
     * ReAssignation constructor.
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\ImportExport\Model\Import\Config $importConfig
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param Validator $validator
     * @param \Riki\AdvancedInventory\Model\ResourceModel\ReAssignationFactory $resourceFactory
     * @param array $data
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
        \Riki\AdvancedInventory\Model\ReAssignation\ImportHandler\Validator $validator,
        \Riki\AdvancedInventory\Model\ResourceModel\ReAssignationFactory $resourceFactory,
        array $data = []
    ) {
    
        $this->validator = $validator;

        $this->validator->init($this);

        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->string = $string;
        $this->errorAggregator = $errorAggregator;

        foreach ($this->errorMessageTemplates as $errorCode => $message) {
            $this->getErrorAggregator()->addErrorMessageTemplate($errorCode, $message);
        }

        $this->_connection = $resourceFactory->create()->getConnection();

        $this->_dataSourceModel = $importData;
        $this->resourceFactory = $resourceFactory;
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
        if (!$this->validator->isAttributeValid($attrCode, $attrParams, $rowData)) {
            foreach ($this->validator->getMessages() as $message) {
                $this->addRowError($message, $rowNum, $attrCode);
            }
            return false;
        }
        return true;
    }

    /**
     * Validate data row.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return boolean
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function validateRow(array $rowData, $rowNum)
    {
        if (isset($this->_validatedRows[$rowNum])) {
            // check that row is already validated
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        $this->_validatedRows[$rowNum] = true;

        if (!$this->validator->isValid($rowData)) {
            foreach ($this->validator->getMessages() as $message) {
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
     * @return $this
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
     * Import data rows.
     *
     * @return boolean
     */
    protected function _importData()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
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
                    'order_increment_id'    =>  $rowData[self::ORDER_NUMBER],
                    'warehouse_code'    =>  strtoupper(trim($rowData[self::WAREHOUSE])),
                    'uploaded_by'    =>  $this->username,
                    'status'    =>  \Riki\AdvancedInventory\Model\Config\Source\ReAssignation\Status::STATUS_WAITING
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
            $this->_connection->insertMultiple($entityTable, $entityRowsIn);
        }
        return $this;
    }

    /**
     * EAV entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'riki_reassignation';
    }

    /**
     * @param $code
     * @return bool|mixed
     */
    public function getAttributeProperties($code)
    {
        if (isset($this->_fieldsProperties[$code])) {
            return $this->_fieldsProperties[$code];
        }

        return false;
    }

    /**
     * Add errors to error aggregator
     *
     * @param string $code
     * @param array|mixed $errors
     * @return void
     */
    protected function addErrors($code, $errors)
    {
        if ($errors) {
            $this->getErrorAggregator()->addError(
                $code,
                ProcessingError::ERROR_LEVEL_CRITICAL,
                null,
                '"' . implode('", "', $errors) . '"'
            );
        }
    }
}
