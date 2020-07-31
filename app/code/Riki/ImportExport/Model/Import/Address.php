<?php
/**
 * Riki Sales calculate cut off date for Shipment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Sales\Model\ResourceModel
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\ImportExport\Model\Import;

use Magento\Customer\Model\ResourceModel\Address\Attribute\Source\CountryWithWebsites as CountryWithWebsitesSource;
use Magento\CustomerImportExport\Model\ResourceModel\Import\Address\Storage as AddressStorage;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

/**
 * Class Address
 *
 * @category  RIKI
 * @package   Riki\Sales\Model\ResourceModel
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Address extends \Magento\CustomerImportExport\Model\Import\Address
{
    const COLUMN_CONSUMER_DB_ID = '_consumer_db_id';

    const ERROR_CONSUMER_DB_ID_IS_EMPTY = \Riki\ImportExport\Model\Import\Customer::ERROR_CONSUMER_DB_ID_IS_EMPTY;

    /**
     * Permanent entity columns
     *
     * @var string[]
     */
    protected $_permanentAttributes = [self::COLUMN_WEBSITE, self::COLUMN_CONSUMER_DB_ID];

    protected $customerDataForValidate = [];

    protected $helper;

    protected $_specialAttributes = [
        self::COLUMN_ACTION,
        self::COLUMN_WEBSITE,
        self::COLUMN_ADDRESS_ID,
        self::COLUMN_DEFAULT_BILLING,
        self::COLUMN_DEFAULT_SHIPPING,
        self::COLUMN_CONSUMER_DB_ID,
    ];

    protected $masterAttributeCode = self::COLUMN_CONSUMER_DB_ID;

    /**
     * @var \Riki\Customer\Model\Address\AddressType $addressType
     */
    protected $addressType;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $eavAttribute;

    protected $addressTypeList;

    const COLUMN_LASTNAME ='lastname';

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
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionColFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory $attributesFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Customer\Model\Address\Validator\Postcode $postcodeValidator,
        \Riki\ImportExport\Helper\Data $helper,
        \Riki\Customer\Model\Address\AddressType $addressType,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        array $data = [],
        ?CountryWithWebsitesSource $countryWithWebsites = null,
        ?AddressStorage $addressStorage = null
    ) {
        parent::__construct($string, $scopeConfig, $importFactory, $resourceHelper, $resource, $errorAggregator,
            $storeManager, $collectionFactory, $eavConfig, $storageFactory, $addressFactory, $regionColFactory,
            $customerFactory, $attributesFactory, $dateTime, $postcodeValidator, $data, $countryWithWebsites,
            $addressStorage);
        $this->helper = $helper;
        $this->eavAttribute = $eavAttribute;
        $this->addressType = $addressType;
        $this->addMessageTemplate(
            self::ERROR_CONSUMER_DB_ID_IS_EMPTY,
            __('Please specify an consumer db ID.')
        );
    }

    protected function _initAddresses()
    {
        $connection = $this->_addressCollection->getConnection();
        $select = $connection->select()->from($this->_addressCollection->getResource()->getEntityTable(), ['entity_id', 'parent_id']);
        $customerAddressEntityVarchar = $connection->getTableName('customer_address_entity_varchar');
        $rikiTypeAddressId = $this->eavAttribute->getIdByCode('customer_address','riki_type_address');
        $select->joinLeft(
            ['customer_address_entity_varchar' => $customerAddressEntityVarchar],
            "customer_address_entity.entity_id =customer_address_entity_varchar.entity_id AND customer_address_entity_varchar.attribute_id =$rikiTypeAddressId",
            [
                "riki_type_address" => "value"
            ],
            null,
            'left');
        $addresses = $connection->fetchAll($select);
        foreach ($addresses as $data) {
            $customerId = $data['parent_id'];
            if (!isset($this->_addresses[$customerId])) {
                $this->_addresses[$customerId] = [];
            }
            if (!isset($this->addressTypeList[$customerId])) {
                $this->addressTypeList[$customerId] = [];
            }
            $addressId = $data['entity_id'];
            if (!in_array($addressId, $this->_addresses[$customerId])) {
                $this->_addresses[$customerId][] = $addressId;
            }
            $rikiTypeAddress = $data['riki_type_address'];
            if (!in_array($addressId, $this->addressTypeList[$customerId])) {
                $this->addressTypeList[$customerId][$addressId] = $rikiTypeAddress;
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function _prepareDataForUpdate(array $rowData):array
    {
        $consumerDbId = strtolower($rowData[self::COLUMN_CONSUMER_DB_ID]);
        $customerId = $this->_getCustomerId($consumerDbId, $rowData[self::COLUMN_WEBSITE]);
        if(isset($this->addressTypeList[$customerId])){
            $typeAddress =  $this->addressType->getIdByLabel($rowData['riki_type_address']);
            foreach ($this->addressTypeList[$customerId] as $addressId => $rikiAddressTypeCode){
                if ($addressId && $typeAddress == $rikiAddressTypeCode  && $typeAddress != \Riki\Customer\Model\Address\AddressType::SHIPPING){
                    $rowData[self::COLUMN_ADDRESS_ID] = $addressId;
                }
                if($rikiAddressTypeCode ==  \Riki\Customer\Model\Address\AddressType::OFFICE && (!isset($rowData[self::COLUMN_LASTNAME])  || $rowData[self::COLUMN_LASTNAME] =='') ){
                    $rowData[self::COLUMN_LASTNAME] = 'ー';
                }
            }
        }
        // entity table data
        $entityRowNew = [];
        $entityRowUpdate = [];
        // attribute values
        $attributes = [];
        // customer default addresses
        $defaults = [];

        $newAddress = true;
        // get address id
        if (isset(
                $this->_addresses[$customerId]
            ) && in_array(
                $rowData[self::COLUMN_ADDRESS_ID],
                $this->_addresses[$customerId]
            )
        ) {
            $newAddress = false;
            $addressId = $rowData[self::COLUMN_ADDRESS_ID];
        } else {
            $addressId = $this->_getNextEntityId();
        }
        $entityRow = [
            'entity_id' => $addressId,
            'parent_id' => $customerId,
            'updated_at' => (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT),
        ];

        foreach ($this->_attributes as $attributeAlias => $attributeParams) {
            if (array_key_exists($attributeAlias, $rowData)) {
                if (!strlen($rowData[$attributeAlias])) {
                    if ($newAddress) {
                        $value = null;
                    } else {
                        continue;
                    }
                } elseif ($newAddress && !strlen($rowData[$attributeAlias])) {

                } elseif ('select' == $attributeParams['type']) {
                    $value = $attributeParams['options'][strtolower($rowData[$attributeAlias])];
                } elseif ('datetime' == $attributeParams['type']) {
                    $value = (new \DateTime())->setTimestamp(strtotime($rowData[$attributeAlias]));
                    $value = $value->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
                } else {
                    $value = $rowData[$attributeAlias];
                }
                if ($attributeParams['is_static']) {
                    $entityRow[$attributeAlias] = $value;
                } else {
                    $attributes[$attributeParams['table']][$addressId][$attributeParams['id']] = $value;
                }
            }
        }

        foreach (self::getDefaultAddressAttributeMapping() as $columnName => $attributeCode) {
            if (!empty($rowData[$columnName])) {
                /** @var $attribute \Magento\Eav\Model\Entity\Attribute\AbstractAttribute */
                $table = $this->_getCustomerEntity()->getResource()->getTable('customer_entity');
                $defaults[$table][$customerId][$attributeCode] = $addressId;
            }
        }

        // let's try to find region ID
        $entityRow['region_id'] = null;
        if (!empty($rowData[self::COLUMN_REGION])) {
            $countryNormalized = strtolower($rowData[self::COLUMN_COUNTRY_ID]);
            $regionNormalized = strtolower($rowData[self::COLUMN_REGION]);

            if (isset($this->_countryRegions[$countryNormalized][$regionNormalized])) {
                $regionId = $this->_countryRegions[$countryNormalized][$regionNormalized];
                $entityRow[self::COLUMN_REGION] = $this->_regions[$regionId];
                $entityRow['region_id'] = $regionId;
            }
        }

        if ($newAddress) {
            $entityRowNew = $entityRow;
            $entityRowNew['created_at'] =
                (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        } else {
            $entityRowUpdate = $entityRow;
        }

        return [
            'entity_row_new' => $entityRowNew,
            'entity_row_update' => $entityRowUpdate,
            'attributes' => $attributes,
            'defaults' => $defaults
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _validateRowForDelete(array $rowData, $rowNumber)
    {
        if ($this->_checkUniqueKey($rowData, $rowNumber)) {
            $consumerDbId = strtolower($rowData[self::COLUMN_CONSUMER_DB_ID]);
            $website = $rowData[self::COLUMN_WEBSITE];
            $addressId = $rowData[self::COLUMN_ADDRESS_ID];

            $customerId = $this->_getCustomerId($consumerDbId, $website);
            if ($customerId === false) {
                $this->addRowError(self::ERROR_CUSTOMER_NOT_FOUND, $rowNumber);
            } else {
                if (!strlen($addressId)) {
                    $this->addRowError(self::ERROR_ADDRESS_ID_IS_EMPTY, $rowNumber);
                } elseif (!in_array($addressId, $this->_addresses[$customerId])) {
                    $this->addRowError(self::ERROR_ADDRESS_NOT_FOUND, $rowNumber);
                }
            }
        }
    }

    /**
     * Validate row for add/update action
     *
     * @param array $rowData array
     * @param int $rowNumber int
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _validateRowForUpdate(array $rowData, $rowNumber)
    {
        if ($this->_checkUniqueKey($rowData, $rowNumber)) {
            $consumerDbId = strtolower($rowData[self::COLUMN_CONSUMER_DB_ID]);
            $website = $rowData[self::COLUMN_WEBSITE];
            $addressId = isset($rowData[self::COLUMN_ADDRESS_ID]) ? $rowData[self::COLUMN_ADDRESS_ID] : "";
            $customerId = $this->_getCustomerId($consumerDbId, $website);

            if ($customerId === false) {
                $this->addRowError(self::ERROR_CUSTOMER_NOT_FOUND, $rowNumber);
            } else {
                if ($this->_checkRowDuplicate($customerId, $addressId)) {
                    $this->addRowError(self::ERROR_DUPLICATE_PK, $rowNumber);
                } else {
                    // check simple attributes
                    foreach ($this->_attributes as $attributeCode => $attributeParams) {
                        if (in_array($attributeCode, $this->_ignoredAttributes)) {
                            continue;
                        }
                        if (isset($rowData[$attributeCode]) && strlen($rowData[$attributeCode])) {
                            $this->isAttributeValid($attributeCode, $attributeParams, $rowData, $rowNumber);
                        } elseif ($attributeParams['is_required']
                            && (!isset($this->_addresses[$customerId]) || !in_array($addressId, $this->_addresses[$customerId]))
                        ) {
                            $this->addRowError(self::ERROR_VALUE_IS_REQUIRED, $rowNumber, $attributeCode);
                        }
                    }

                    if (isset($rowData[self::COLUMN_COUNTRY_ID]) && isset($rowData[self::COLUMN_REGION])) {
                        $countryRegions = isset(
                            $this->_countryRegions[strtolower($rowData[self::COLUMN_COUNTRY_ID])]
                        ) ? $this->_countryRegions[strtolower(
                            $rowData[self::COLUMN_COUNTRY_ID]
                        )] : [];

                        if (!empty($rowData[self::COLUMN_REGION])
                            && !empty($countryRegions)
                            && !isset($countryRegions[strtolower($rowData[self::COLUMN_REGION])])
                        ) {
                            $this->addRowError(self::ERROR_INVALID_REGION, $rowNumber, self::COLUMN_REGION);
                        }
                    }
                }
            }
        }
    }

    /**
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
}
