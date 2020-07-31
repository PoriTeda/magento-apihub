<?php

namespace Riki\Prize\Model\Prize;

use Magento\Framework\Exception\LocalizedException;
use Riki\Prize\Model\Prize;

class Import
{
    const ERR_WBS           = 'ERR_WBS';
    const ERR_SKU           = 'ERR_SKU';
    const ERR_CUSTOMER_CODE = 'ERR_CMC';
    const ERR_EXISTED       = 'ERR_EXD';
    const ERR_DUPLICATE     = 'ERR_DLC';
    const ERR_EMPTY         = 'ERR_EMP';
    const ERR_INVALID_DATE  = 'ERR_DATE';
    const ERR_INVALID_QTY   = 'ERR_QTY';
    const ERR_INVALID_STATUS = 'ERR_STATUS';

    private $_fieldCustomerCode;
    private $_fieldSku;
    private $_fieldWbs;
    private $_fieldQty;
    private $_fieldCampaignId;
    private $_fieldStatus;
    private $_fieldOrderNo;
    private $_fieldMailSendDate;
    private $_fieldWinningDate;

    /**
     * @var array
     */
    protected $_header;

    /**
     * @var array
     */
    protected $_statistic = [];

    /**
     * @var array
     */
    protected $_productSKUs = [];

    /**
     * @var array
     */
    protected $_customerCodes = [];

    /**
     * @var array
     */
    protected $_existingPrize = [];

    /**
     * @var array
     */
    protected $_approved = [];

    /**
     * @var \Riki\Prize\Model\PrizeFactory
     */
    protected $_prizeFactory;

    /**
     * @var \Riki\Prize\Model\ResourceModel\Prize\CollectionFactory
     */
    protected $_prizeCollectionFactory;

    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $_csvReader;

    /**
     * @var \Magento\Framework\HTTP\Adapter\FileTransferFactory
     */
    protected $_httpFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
     */
    protected $_productCollection;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
     */
    protected $_customerCollection;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Import constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollection
     * @param \Magento\Framework\File\Csv $csvReader
     * @param \Magento\Framework\HTTP\Adapter\FileTransferFactory $fileTransfer
     * @param \Riki\Prize\Model\PrizeFactory $prizeFactory
     * @param \Riki\Prize\Model\ResourceModel\Prize\CollectionFactory $prizeCollectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollection,
        \Magento\Framework\File\Csv $csvReader,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $fileTransfer,
        \Riki\Prize\Model\PrizeFactory $prizeFactory,
        \Riki\Prize\Model\ResourceModel\Prize\CollectionFactory $prizeCollectionFactory,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_productCollection = $productCollection;
        $this->_customerCollection = $customerCollection;
        $this->_prizeCollectionFactory = $prizeCollectionFactory;
        $this->_csvReader = $csvReader;
        $this->_httpFactory = $fileTransfer;
        $this->_prizeFactory = $prizeFactory;
        $this->_logger = $logger;
    }

    /**
     * Prepare data to CSV import
     *
     * @param array $csvData
     * @return $this
     */
    private function _prepareData($csvData)
    {
        $productSKUs = [];
        $customerCodes = [];
        foreach ($csvData as $value) {
            $productSKUs[] = $value[$this->_fieldSku];
            $customerCodes[] = $value[$this->_fieldCustomerCode];
        }
        if (sizeof($productSKUs)) {
            $productCol = $this->_productCollection->create();
            $productCol->addAttributeToSelect('sku');
            $productCol->addAttributeToFilter('sku', ['in' => $productSKUs]);
            if ($productCol->getSize()) {
                $this->_productSKUs = array_map(function($value) { return $value['sku'];}, $productCol->getData());
            }
        }
        if (sizeof($customerCodes)) {
            $customerCol = $this->_customerCollection->create();
            $customerCol->addAttributeToSelect('consumer_db_id');
            $customerCol->addAttributeToFilter('consumer_db_id', ['in' => $customerCodes]);
            if ($customerCol->getSize()) {
                $this->_customerCodes = array_map(function($value) { return $value['consumer_db_id'];}, $customerCol->getData());
            }
        }
        $prizeCollection = $this->_prizeCollectionFactory->create();
        $prizeCollection->addFieldToSelect('*');
        $prizeCollection->addFieldToFilter('consumer_db_id', ['in' => $customerCodes]);
        $prizeCollection->addFieldToFilter('sku', ['in' => $productSKUs]);
        if ($prizeCollection->getSize()) {
            foreach ($prizeCollection->getData() as $item) {
                $this->_existingPrize[$item['consumer_db_id']][$item['sku']] = $item['campaign_code'];
            }
        }
        return $this;
    }

    /**
     * Find index for import row
     *
     * @param array $header
     * @return void
     */
    private function _initHeader($header)
    {
        $this->_header = $header;
        $this->_fieldCustomerCode = array_search('CUSTOMER_CODE', $header);
        $this->_fieldSku = array_search('COMMODITY_CODE', $header);
        $this->_fieldWbs = array_search('WBS', $header);
        $this->_fieldQty = array_search('AMOUNT', $header);
        $this->_fieldCampaignId = array_search('CAMPAIGN_ID', $header);
        $this->_fieldStatus = array_search('STATUS', $header);
        $this->_fieldOrderNo = array_search('ORDER_NO', $header);
        $this->_fieldMailSendDate = array_search('MAIL_SEND_DATE', $header);
        $this->_fieldWinningDate = array_search('WINNING_DATE', $header);
    }

    /**
     * Validate header index
     *
     * @param array $header
     * @return array
     */
    private function _validateHeader($header)
    {
        $errors = [];
        $columnRequired = ['CUSTOMER_CODE', 'COMMODITY_CODE', 'WBS', 'AMOUNT', 'CAMPAIGN_ID', 'STATUS', 'ORDER_NO', 'MAIL_SEND_DATE', 'WINNING_DATE'];
        foreach ($columnRequired as $column) {
            if (array_search($column, $header) === false) {
                $errors[] = __('Missing column %1', $column);
            }
        }
        return $errors;
    }
    /**
     * @param integer $key
     * @param array $row
     * @return bool
     */
    private function _validateRow($key, $row)
    {
        $isError = false;
        $dateValidator = new \Zend_Validate_Date(['format' => 'Y/m/d']);
        $key++; //show error start with the first row (instead of 0)
        $requiredField = ['CUSTOMER_CODE', 'COMMODITY_CODE', 'WBS_CODE', 'AMOUNT', 'CAMPAIGN_ID', 'STATUS', 'WINNING_DATE'];
        foreach ($requiredField as $field) {
            $index = array_search($field, $this->_header);
            if (!\Zend_Validate::is($row[$index], 'NotEmpty')) {
                $isError = true;
                $this->_statistic['EMPTY_'.$field][] = $key;
            }
        }
        $wbsValidate = new \Zend_Validate_Regex('/^AC-\d{8}$/');
        if (!$wbsValidate->isValid($row[$this->_fieldWbs])) {
            $isError = true;
            $this->_statistic[self::ERR_WBS][] = $key;
        }
        $statuses = [
            Prize::STATUS_DONE, Prize::STATUS_DONE_BY_MANUAL, Prize::STATUS_STOCK_SHORTAGE_ERROR, Prize::STATUS_WAITING
        ];
        if (!in_array($row[$this->_fieldStatus], $statuses)) {
            $isError = true;
            $this->_statistic[self::ERR_INVALID_STATUS][] = $key;
        }
        if ((int) $row[$this->_fieldQty] <= 0) {
            $isError = true;
            $this->_statistic[self::ERR_INVALID_QTY][] = $key;
        }
        if (\Zend_Validate::is($row[$this->_fieldMailSendDate], 'NotEmpty')) {
            $createDate = new \DateTime($row[$this->_fieldMailSendDate]);
            $validateDate = $createDate->format('Y/m/d');
            if (!$dateValidator->isValid($validateDate)) {
                $isError = true;
                $this->_statistic[self::ERR_INVALID_DATE][] = $key;
            }
        }
        if (\Zend_Validate::is($row[$this->_fieldWinningDate], 'NotEmpty')) {
            $createDate = new \DateTime($row[$this->_fieldWinningDate]);
            $validateDate = $createDate->format('Y/m/d');
            if (!$dateValidator->isValid($validateDate)) {
                $isError = true;
                $this->_statistic[self::ERR_INVALID_DATE][] = $key;
            }
        }
        if (!in_array($row[$this->_fieldCustomerCode], $this->_customerCodes)) {
            $isError = true;
            $this->_statistic[self::ERR_CUSTOMER_CODE][] = $key;
        }
        if (!in_array($row[$this->_fieldSku], $this->_productSKUs)) {
            $isError = true;
            $this->_statistic[self::ERR_SKU][] = $key;
        }
        if (isset($this->_existingPrize[$row[$this->_fieldCustomerCode]][$row[$this->_fieldSku]]) &&
            $this->_existingPrize[$row[$this->_fieldCustomerCode]][$row[$this->_fieldSku]] == trim($row[$this->_fieldCampaignId])) {
            $isError = true;
            $this->_statistic[self::ERR_EXISTED][] = $key;
        }
        if (isset($this->_approved[$row[$this->_fieldCustomerCode]][$row[$this->_fieldSku]]) &&
            $this->_approved[$row[$this->_fieldCustomerCode]][$row[$this->_fieldSku]] == trim($row[$this->_fieldCampaignId])) {
            $isError = true;
            $this->_statistic[self::ERR_DUPLICATE][] = $key;
        }
        if (!$isError) {
            $this->_approved[$row[$this->_fieldCustomerCode]][$row[$this->_fieldSku]] = trim($row[$this->_fieldCampaignId]);
        }
        return !$isError;
    }

    /**
     * Insert winner prize to database
     *
     * @param array $value
     * @return bool
     */
    private function _insertRow($value)
    {
        $model = $this->_prizeFactory->create();
        $dataPrize = [
            'consumer_db_id' => $value[$this->_fieldCustomerCode],
            'sku' => $value[$this->_fieldSku],
            'wbs' => $value[$this->_fieldWbs],
            'qty' => $value[$this->_fieldQty],
            'status' => $value[$this->_fieldStatus],
            'campaign_code' => $value[$this->_fieldCampaignId],
            'order_no' => !empty($value[$this->_fieldOrderNo]) ? $value[$this->_fieldOrderNo] : null,
            'mail_send_date' => !empty($value[$this->_fieldMailSendDate]) ? $value[$this->_fieldMailSendDate] : null,
            'winning_date' => $value[$this->_fieldWinningDate]
        ];
        $model->setData($dataPrize);
        try {
            $model->save();
            $this->_existingPrize[$model->getData('consumer_db_id')][$model->getData('sku')] =
                $model->getData('campaign_code');
            return true;
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            return false;
        }
    }

    /**
     * Import CSV winner prize
     *
     * @param string $fieldName
     * @return array
     * @throws \Exception
     */
    public function doImport($fieldName)
    {
        $result = ['error' => 0, 'success' => 0];
        try {
            $adapter = $this->_httpFactory->create();
            $adapter->addValidator('Extension', false, 'csv');
            $fileTransfer = $adapter->getFileInfo();
            $csvFile = $fileTransfer[$fieldName];
            $csvData = $this->_csvReader->getData($csvFile['tmp_name']);
            //skip header
            $header = array_shift($csvData);
            $this->_initHeader($header);
            $this->_prepareData($csvData);
            foreach ($csvData as $key => $value) {
                $value = array_map('trim', $value);
                if ($this->_validateRow($key, $value) !== true) {
                    $result['error']++;
                    continue;
                }
                if ($this->_insertRow($value)) {
                    $result['success']++;
                } else {
                    $result['error']++;
                }
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $result;
    }

    /**
     * Validate data before import
     *
     * @param string $fieldName
     * @return array
     */
    public function validateSource($fieldName)
    {
        $messages = [];
        try {
            $adapter = $this->_httpFactory->create();
            $adapter->addValidator('Extension', false, 'csv');
            if (!$adapter->isValid($fieldName)) {
                throw new LocalizedException(__('The file does not matched with predefined format!'));
            }
            $fileTransfer = $adapter->getFileInfo();
            $csvFile = $fileTransfer[$fieldName];
            $csvData = $this->_csvReader->getData($csvFile['tmp_name']);
            //skip header
            $header = array_shift($csvData);
            $headerError = $this->_validateHeader($header);
            if (sizeof($headerError)) {
                return ['error' => $headerError];
            }
            $this->_initHeader($header);
            $this->_prepareData($csvData);
            $success = 0;
            foreach ($csvData as $key => $value) {
                $value = array_map('trim', $value);
                if($this->_validateRow($key, $value) === true) {
                    $success++;
                }
            }
            if (sizeof($this->_statistic)) {
                foreach ($this->_statistic as $key => $value) {
                    $value = array_unique($value);
                    if (strpos($key, 'EMPTY_') !== false) {
                        $detect = explode('EMPTY_', $key);
                        $msg = __('%1 is empty in rows: %2', $detect[1], implode(',', $value));
                        $messages['error'][] = $msg;
                        continue;
                    }
                    switch ($key) {
                        case self::ERR_CUSTOMER_CODE:
                            $msg = __('CUSTOMER_CODE does not exist rows: %1', implode(',', $value));
                            break;
                        case self::ERR_SKU:
                            $msg = __('COMMODITY_CODE does not exist rows: %1', implode(',', $value));
                            break;
                        case self::ERR_EXISTED:
                            $msg = __('Prize is existed (COMMODITY_CODE - CUSTOMER_CODE - CAMPAIGN_ID) in rows: %1', implode(',', $value));
                            break;
                        case self::ERR_DUPLICATE:
                            $msg = __('Duplicate data in rows: %1', implode(', ', $value));
                            break;
                        case self::ERR_WBS:
                            $msg = __('Wrong format for WBS in rows: %1', implode(', ', $value));
                            break;
                        case self::ERR_INVALID_DATE:
                            $msg = __('Date is invalid in rows: %1', implode(', ', $value));
                            break;
                        case self::ERR_INVALID_QTY:
                            $msg = __('AMOUNT is invalid in rows: %1', implode(', ', $value));
                            break;
                        case self::ERR_INVALID_STATUS:
                            $msg = __('STATUS is invalid in rows: %1', implode(', ', $value));
                            break;
                        default:
                            $msg = __('Data error in rows: %1', implode(', ', $value));
                            break;
                    }
                    $messages['error'][] = $msg;
                }
            }
            if ($success) {
                $messages['success'][] = __('Validate successful %1 rows', $success);
            }
        } catch (\Exception $e) {
            $messages['error'][] = $e->getMessage();
        }
        return $messages;
    }
}