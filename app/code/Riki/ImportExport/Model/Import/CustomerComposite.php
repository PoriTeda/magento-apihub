<?php
/**
 * Import CustomerComposite
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

use Magento\ImportExport\Model\Import\AbstractEntity;
/**
 * Class CustomerComposite
 *
 * @category  RIKI
 * @package   Riki\ImportExport\Model\Import
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class CustomerComposite extends \Magento\CustomerImportExport\Model\Import\CustomerComposite
{
    const ERROR_DUPLICATE_EMAIL_SITE = 'duplicateEmailSite';
    protected $currentConsumerDbId = null;

    /**
     * {@inheritdoc}
     */
    protected $masterAttributeCode = Customer::COLUMN_CONSUMER_DB_ID;

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

    public function validateRow(array $rowData, $rowNumber)
    {
        $rowScope = $this->_getRowScope($rowData);
        if ($rowScope == self::SCOPE_DEFAULT) {
            if ($this->_customerEntity->validateRow($rowData, $rowNumber)) {
                $this->_currentWebsiteCode =
                    $rowData[Customer::COLUMN_WEBSITE];
                $this->_currentEmail = strtolower(
                    $rowData[Customer::COLUMN_EMAIL]
                );
                $this->currentConsumerDbId = $rowData[Customer::COLUMN_CONSUMER_DB_ID];

                // Add new customer data into customer storage for address entity instance
                $websiteId = $this->_customerEntity->getWebsiteId($this->_currentWebsiteCode);
                if (!$this->_addressEntity->getCustomerStorage()->getCustomerId($this->currentConsumerDbId, $websiteId)) {
                    $customerData = new \Magento\Framework\DataObject(
                        [
                            'id' => $this->_nextCustomerId,
                            'email' => $this->_currentEmail,
                            'consumer_db_id' => $this->currentConsumerDbId,
                            'website_id' => $websiteId,
                        ]
                    );
                    $this->_addressEntity->getCustomerStorage()->addCustomer($customerData);
                    $this->_nextCustomerId++;
                    $loadedData = $this->_addressEntity->getCustomerStorage()->_customerData;
                    if(isset($loadedData[$rowData[Customer::COLUMN_EMAIL]])){
                        $this->addRowError(self::ERROR_DUPLICATE_EMAIL_SITE, $rowNumber);
                    }
                }

                return $this->_validateAddressRow($rowData, $rowNumber);
            } else {
                $this->_currentWebsiteCode = null;
                $this->_currentEmail = null;
                $this->currentConsumerDbId = null;
            }
        } else {
            if (!empty($this->_currentWebsiteCode) && !empty($this->_currentEmail)) {
                return $this->_validateAddressRow($rowData, $rowNumber);
            } else {
                $this->addRowError(self::ERROR_ROW_IS_ORPHAN, $rowNumber);
            }
        }

        return false;
    }

    protected function _validateAddressRow(array $rowData, $rowNumber)
    {
        if ($this->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE) {
            return true;
        }

        $rowData = $this->_prepareAddressRowData($rowData);
        if (empty($rowData)) {
            return true;
        } else {
            $rowData[Address::COLUMN_WEBSITE] =
                $this->_currentWebsiteCode;
            $rowData[Address::COLUMN_EMAIL] =
                $this->_currentEmail;
            $rowData[Address::COLUMN_CONSUMER_DB_ID] = $this->currentConsumerDbId;
            $rowData[Address::COLUMN_ADDRESS_ID] = null;

            return $this->_addressEntity->validateRow($rowData, $rowNumber);
        }
    }

    protected function _prepareRowForDb(array $rowData)
    {
        $rowData[Address::COLUMN_CONSUMER_DB_ID] = $this->currentConsumerDbId;
        return parent::_prepareRowForDb($rowData);
    }
}
