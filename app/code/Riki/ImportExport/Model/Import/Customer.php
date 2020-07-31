<?php
/**
 * Customer
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ImportExport\Model\Import
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\ImportExport\Model\Import;

use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

/**
 * Class Customer
 *
 * @category  RIKI
 * @package   Riki\ImportExport\Model\Import
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Customer extends \Magento\CustomerImportExport\Model\Import\Customer
{
    protected $helper;

    const COLUMN_CONSUMER_DB_ID = 'consumer_db_id';

    const ERROR_DUPLICATE_CONSUMER_DB_ID = 'duplicateConsumerDbId';

    const ERROR_CONSUMER_DB_ID_IS_EMPTY = 'consumerDbIsEmpty';

    /**
     * Customer constructor.
     *
     * @param \Magento\Framework\Stdlib\StringUtils $string StringUtils
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig ScopeConfigInterface
     * @param \Magento\ImportExport\Model\ImportFactory $importFactory ImportFactory
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper Helper
     * @param \Magento\Framework\App\ResourceConnection $resource ResourceConnection
     * @param ProcessingErrorAggregatorInterface $errorAggregator ProcessingErrorAggregatorInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager StoreManagerInterface
     * @param \Magento\ImportExport\Model\Export\Factory $collectionFactory Factory
     * @param \Magento\Eav\Model\Config $eavConfig Config
     * @param \Magento\CustomerImportExport\Model\ResourceModel\Import\Customer\StorageFactory $storageFactory StorageFactory
     * @param \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attrCollectionFactory CollectionFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory CustomerFactory
     * @param \Riki\ImportExport\Helper\Data $helper CustomerFactory
     * @param array $data Data
     */
    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\ImportExport\Model\ImportFactory $importFactory,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\App\ResourceConnection $resource,
        ProcessingErrorAggregatorInterface $errorAggregator,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\ImportExport\Model\Export\Factory $collectionFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\CustomerImportExport\Model\ResourceModel\Import\Customer\StorageFactory $storageFactory,
        \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attrCollectionFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\ImportExport\Helper\Data $helper,
        array $data = []
    )
    {
        parent::__construct(
            $string,
            $scopeConfig,
            $importFactory,
            $resourceHelper,
            $resource,
            $errorAggregator,
            $storeManager,
            $collectionFactory,
            $eavConfig,
            $storageFactory,
            $attrCollectionFactory,
            $customerFactory,
            $data
        );

        $this->helper = $helper;

        $this->_permanentAttributes[] = self::COLUMN_CONSUMER_DB_ID;

        $this->addMessageTemplate(
            static::ERROR_DUPLICATE_CONSUMER_DB_ID,
            __('This ConsumerDbId is found more than once in the import file.')
        );

        $this->addMessageTemplate(
            self::ERROR_CONSUMER_DB_ID_IS_EMPTY,
            __('Please specify an consumer db ID.')
        );
    }

    public function isAttributeValid($attributeCode,
                                     array $attributeParams,
                                     array $rowData,
                                     $rowNumber,
                                     $multiSeparator = Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR)
    {
        $message = '';
        switch ($attributeParams['type']) {
            case 'varchar':
                $value = $this->string->cleanString($rowData[$attributeCode]);
                $valid = $this->string->strlen($value) < self::DB_MAX_VARCHAR_LENGTH;
                $message = self::ERROR_EXCEEDED_MAX_LENGTH;
                break;
            case 'decimal':
                $value = trim($rowData[$attributeCode]);
                $valid = (double)$value == $value && is_numeric($value);
                $message = self::ERROR_INVALID_ATTRIBUTE_TYPE;
                break;
            case 'select':
                $valid = isset($attributeParams['options'][strtolower($rowData[$attributeCode])]);
                $message = self::ERROR_INVALID_ATTRIBUTE_OPTION;
                break;
            case 'multiselect':
                $values = array_map('strtolower', explode(',', $rowData[$attributeCode]));
                foreach ($values as $value) {
                    $valid = isset($attributeParams['options'][$value]);

                    if (!$valid) {
                        break;
                    }
                }
                $message = self::ERROR_INVALID_ATTRIBUTE_OPTION;
                break;
            case 'int':
                $value = trim($rowData[$attributeCode]);
                $valid = (int)$value == $value && is_numeric($value);
                $message = self::ERROR_INVALID_ATTRIBUTE_TYPE;
                break;
            case 'datetime':
                $value = trim($rowData[$attributeCode]);
                $valid = strtotime($value) !== false;
                $message = self::ERROR_INVALID_ATTRIBUTE_TYPE;
                break;
            case 'text':
                $value = $this->string->cleanString($rowData[$attributeCode]);
                $valid = $this->string->strlen($value) < self::DB_MAX_TEXT_LENGTH;
                $message = self::ERROR_EXCEEDED_MAX_LENGTH;
                break;
            default:
                $valid = true;
                break;
        }

        if (!$valid) {
            if ($message == self::ERROR_INVALID_ATTRIBUTE_TYPE) {
                $message = sprintf(
                    $this->errorMessageTemplates[$message],
                    $attributeCode,
                    $attributeParams['type']
                );
            }
            $this->addRowError($message, $rowNumber, $attributeCode);
        } elseif (!empty($attributeParams['is_unique'])) {
            if (isset($this->_uniqueAttributes[$attributeCode][$rowData[$attributeCode]])) {
                $this->addRowError(self::ERROR_CODE_DUPLICATE_UNIQUE_ATTRIBUTE, $rowNumber, $attributeCode);
                return false;
            }
            $this->_uniqueAttributes[$attributeCode][$rowData[$attributeCode]] = true;
        }
        return (bool)$valid;
    }

    /**
     * SaveValidatedBunches
     *
     * @return $this
     */
    protected function _saveValidatedBunches()
    {
        $source = $this->getSource();
        $bunchRows = [];
        $startNewBunch = false;

        $source->rewind();
        $this->_dataSourceModel->cleanBunches();
        $masterAttributeCode = $this->getMasterAttributeCode();

        while ($source->valid() || count($bunchRows) || isset($entityGroup)) {
            if ($startNewBunch || !$source->valid()) {
                /* If the end approached add last validated entity group to the bunch */
                if (!$source->valid() && isset($entityGroup)) {
                    foreach ($entityGroup as $key => $value) {
                        $bunchRows[$key] = $value;
                    }
                    unset($entityGroup);
                }
                $this->_dataSourceModel->saveBunch($this->getEntityTypeCode(), $this->getBehavior(), $bunchRows);

                $bunchRows = [];
                $startNewBunch = false;
            }
            if ($source->valid()) {
                $valid = true;
                try {
                    $rowData = $source->current();
                    foreach ($rowData as $attrName => $element) {
                        if (!mb_check_encoding($element, 'UTF-8')) {
                            $valid = false;
                            $this->addRowError(
                                AbstractEntity::ERROR_CODE_ILLEGAL_CHARACTERS,
                                $this->_processedRowsCount,
                                $attrName
                            );
                        }
                        if (!$this->helper->validateRequiredAttributes($attrName, $rowData)) {
                            $valid = false;
                            $this->addRowError(
                                AbstractEntity::ERROR_CODE_ATTRIBUTE_NOT_VALID,
                                $this->_processedRowsCount,
                                $attrName
                            );
                        }
                    }
                } catch (\InvalidArgumentException $e) {
                    $valid = false;
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                }
                if (!$valid) {
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }

                if (isset($rowData[$masterAttributeCode])) {
                    /* Add entity group that passed validation to bunch */
                    if (isset($entityGroup)) {
                        foreach ($entityGroup as $key => $value) {
                            $bunchRows[$key] = $value;
                        }
                        $productDataSize = strlen(\Zend\Serializer\Serializer::serialize($bunchRows));

                        /* Check if the new bunch should be started */
                        $isBunchSizeExceeded = ($this->_bunchSize > 0 && count($bunchRows) >= $this->_bunchSize);
                        $startNewBunch = $productDataSize >= $this->_maxDataSize || $isBunchSizeExceeded;
                    }

                    /* And start a new one */
                    $entityGroup = [];
                }

                if (isset($entityGroup) && $this->validateRow($rowData, $source->key())) {
                    /* Add row to entity group */
                    $entityGroup[$source->key()] = $this->_prepareRowForDb($rowData);
                } elseif (isset($entityGroup)) {
                    /* In case validation of one line of the group fails kill the entire group */
                    unset($entityGroup);
                }

                $this->_processedRowsCount++;
                $source->next();
            }
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function _checkUniqueKey(array $rowData, $rowNumber)
    {
        if (empty($rowData[static::COLUMN_WEBSITE])) {
            $this->addRowError(static::ERROR_WEBSITE_IS_EMPTY, $rowNumber, static::COLUMN_WEBSITE);
        } elseif (empty($rowData[static::COLUMN_CONSUMER_DB_ID])) {
            $this->addRowError(static::ERROR_CONSUMER_DB_ID_IS_EMPTY, $rowNumber, static::COLUMN_CONSUMER_DB_ID);
        } else {
            $website = $rowData[static::COLUMN_WEBSITE];
            if (!isset($this->_websiteCodeToId[$website])) {
                $this->addRowError(static::ERROR_INVALID_WEBSITE, $rowNumber, static::COLUMN_WEBSITE);
            }
        }
        return !$this->getErrorAggregator()->isRowInvalid($rowNumber);
    }

    /**
     * @inheritdoc
     */
    protected function _validateRowForDelete(array $rowData, $rowNumber)
    {
        if ($this->_checkUniqueKey($rowData, $rowNumber)) {
            if (!$this->_getCustomerId($rowData[self::COLUMN_CONSUMER_DB_ID], $rowData[self::COLUMN_WEBSITE])) {
                $this->addRowError(self::ERROR_CUSTOMER_NOT_FOUND, $rowNumber);
            }
        }
    }

    /**
     * Prepare customer data for update
     *
     * @param array $rowData
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _prepareDataForUpdate(array $rowData)
    {
        $entitiesToCreate = [];
        $entitiesToUpdate = [];
        $attributesToSave = [];

        // entity table data
        $now = new \DateTime();
        if (empty($rowData['created_at'])) {
            $createdAt = $now;
        } else {
            $createdAt = (new \DateTime())->setTimestamp(strtotime($rowData['created_at']));
        }

        $emailInLowercase = strtolower($rowData[self::COLUMN_EMAIL]);
        $consumerDbId = $rowData[self::COLUMN_CONSUMER_DB_ID];
        $newCustomer = false;
        $entityId = $this->_getCustomerId($consumerDbId, $rowData[self::COLUMN_WEBSITE]);
        if (!$entityId) {
            // create
            $newCustomer = true;
            $entityId = $this->_getNextEntityId();
            $this->_newCustomers[$consumerDbId][$rowData[self::COLUMN_WEBSITE]] = $entityId;
        }

        $entityRow = [
            'group_id' => empty($rowData['group_id']) ? self::DEFAULT_GROUP_ID : $rowData['group_id'],
            'store_id' => empty($rowData[self::COLUMN_STORE]) ? 0 : $this->_storeCodeToId[$rowData[self::COLUMN_STORE]],
            'created_at' => $createdAt->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT),
            'updated_at' => $now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT),
            'entity_id' => $entityId,
        ];

        // password change/set
        if (isset($rowData['password']) && strlen($rowData['password'])) {
            $entityRow['password_hash'] = $this->_customerModel->hashPassword($rowData['password']);
        }

        // attribute values
        foreach (array_intersect_key($rowData, $this->_attributes) as $attributeCode => $value) {
            if ($newCustomer && !strlen($value)) {
                continue;
            }

            $attributeParameters = $this->_attributes[$attributeCode];
            if ('select' == $attributeParameters['type']) {
                $value = isset($attributeParameters['options'][strtolower($value)])
                    ? $attributeParameters['options'][strtolower($value)]
                    : 0;
            } elseif ('datetime' == $attributeParameters['type'] && !empty($value)) {
                $value = (new \DateTime())->setTimestamp(strtotime($value));
                $value = $value->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
            } elseif ('multiselect' == $attributeParameters['type'] && !empty($value)) {
                $values = array_map('strtolower', explode(',', $value));
                $convertedValues = [];
                foreach ($values as $valueToConvert) {
                    if (isset($attributeParameters['options'][$valueToConvert])) {
                        $convertedValues[] = $attributeParameters['options'][$valueToConvert];
                    }
                }
                $value = implode(',', $convertedValues);
            }

            if (!$this->_attributes[$attributeCode]['is_static']) {
                /** @var $attribute \Magento\Customer\Model\Attribute */
                $attribute = $this->_customerModel->getAttribute($attributeCode);
                $backendModel = $attribute->getBackendModel();
                if ($backendModel
                    && $attribute->getFrontendInput() != 'select'
                    && $attribute->getFrontendInput() != 'datetime') {
                    $attribute->getBackend()->beforeSave($this->_customerModel->setData($attributeCode, $value));
                    $value = $this->_customerModel->getData($attributeCode);
                }
                $attributesToSave[$attribute->getBackend()
                    ->getTable()][$entityId][$attributeParameters['id']] = $value;

                // restore 'backend_model' to avoid default setting
                $attribute->setBackendModel($backendModel);
            } else {
                $entityRow[$attributeCode] = $value;
            }
        }

        if ($newCustomer) {
            // create
            $entityRow['website_id'] = $this->_websiteCodeToId[$rowData[self::COLUMN_WEBSITE]];
            $entityRow['email'] = $emailInLowercase;
            $entityRow['consumer_db_id'] = $consumerDbId;
            $entityRow['is_active'] = 1;
            $entitiesToCreate[] = $entityRow;
        } else {
            // edit
            $entitiesToUpdate[] = $entityRow;
        }

        return [
            self::ENTITIES_TO_CREATE_KEY => $entitiesToCreate,
            self::ENTITIES_TO_UPDATE_KEY => $entitiesToUpdate,
            self::ATTRIBUTES_TO_SAVE_KEY => $attributesToSave
        ];
    }

    /**
     * Import data rows
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _importData()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $entitiesToCreate = [];
            $entitiesToUpdate = [];
            $entitiesToDelete = [];
            $attributesToSave = [];

            foreach ($bunch as $rowNumber => $rowData) {
                if (!$this->validateRow($rowData, $rowNumber)) {
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNumber);
                    continue;
                }

                if ($this->getBehavior($rowData) == \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE) {
                    $entitiesToDelete[] = $this->_getCustomerId(
                        $rowData[self::COLUMN_EMAIL],
                        $rowData[self::COLUMN_WEBSITE]
                    );
                } elseif ($this->getBehavior($rowData) == \Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE) {
                    $processedData = $this->_prepareDataForUpdate($rowData);
                    $entitiesToCreate = array_merge($entitiesToCreate, $processedData[self::ENTITIES_TO_CREATE_KEY]);
                    $entitiesToUpdate = array_merge($entitiesToUpdate, $processedData[self::ENTITIES_TO_UPDATE_KEY]);
                    foreach ($processedData[self::ATTRIBUTES_TO_SAVE_KEY] as $tableName => $customerAttributes) {
                        if (!isset($attributesToSave[$tableName])) {
                            $attributesToSave[$tableName] = [];
                        }
                        $attributesToSave[$tableName] = array_diff_key(
                                $attributesToSave[$tableName],
                                $customerAttributes
                            ) + $customerAttributes;
                    }
                }
            }
            $this->updateItemsCounterStats($entitiesToCreate, $entitiesToUpdate, $entitiesToDelete);
            /**
             * Save prepared data
             */
            if ($entitiesToCreate || $entitiesToUpdate) {
                $this->_saveCustomerEntities($entitiesToCreate, $entitiesToUpdate);
            }
            if ($attributesToSave) {
                $this->_saveCustomerAttributes($attributesToSave);
            }
            if ($entitiesToDelete) {
                $this->_deleteCustomerEntities($entitiesToDelete);
            }
        }

        return true;
    }

    /**
     * Update and insert data in entity table
     *
     * @param array $entitiesToCreate Rows for insert
     * @param array $entitiesToUpdate Rows for update
     * @return $this
     */
    protected function _saveCustomerEntities(array $entitiesToCreate, array $entitiesToUpdate)
    {
        $this->customerFields[] = 'email';
        if ($entitiesToCreate) {
            $this->_connection->insertMultiple($this->_entityTable, $entitiesToCreate);
        }

        if ($entitiesToUpdate) {
            $this->_connection->insertOnDuplicate(
                $this->_entityTable,
                $entitiesToUpdate,
                $this->customerFields
            );
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function _validateRowForUpdate(array $rowData, $rowNumber)
    {
        parent::_validateRowForUpdate($rowData, $rowNumber);

        if ($this->_checkUniqueKey($rowData, $rowNumber)) {
            $website = $rowData[self::COLUMN_WEBSITE];

            if (isset($this->_newCustomers[strtolower($rowData[self::COLUMN_CONSUMER_DB_ID])][$website])) {
                $this->addRowError(self::ERROR_DUPLICATE_CONSUMER_DB_ID, $rowNumber);
            }
        }
    }
}
