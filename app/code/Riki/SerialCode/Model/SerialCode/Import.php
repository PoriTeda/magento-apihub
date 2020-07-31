<?php

namespace Riki\SerialCode\Model\SerialCode;

use Magento\Framework\Exception\LocalizedException;

class Import
{
    /**
     * @var \Magento\Framework\HTTP\Adapter\FileTransferFactory
     */
    protected $_httpFactory;

    /**
     * @var \Riki\SerialCode\Model\ResourceModel\SerialCode\CollectionFactory
     */
    protected $_serialCodeCollectionFactory;

    /**
     * @var \Riki\SerialCode\Model\ResourceModel\SerialCodeFactory
     */
    protected $_resourceModelFactory;

    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $_csvReader;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var array
     */
    protected $_header = [];

    /**
     * @var array
     */
    protected $_existed = [];

    /**
     * @var array
     */
    protected $_statistic = [];

    /**
     * @var array
     */
    protected $_status = [];

    /**
     * @var array
     */
    protected $_validCode = [];

    /**
     * @var array
     */
    protected $_dataImport = [];

    /**
     * Import constructor.
     *
     * @param \Magento\Framework\HTTP\Adapter\FileTransferFactory $fileTransfer
     * @param \Riki\SerialCode\Model\ResourceModel\SerialCode\CollectionFactory $collectionFactory
     * @param \Magento\Framework\File\Csv $csvReader
     * @param \Psr\Log\LoggerInterface $loggerInterface
     * @param \Riki\SerialCode\Model\ResourceModel\SerialCodeFactory $serialCodeFactory
     */
    public function __construct(
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $fileTransfer,
        \Riki\SerialCode\Model\ResourceModel\SerialCode\CollectionFactory $collectionFactory,
        \Magento\Framework\File\Csv $csvReader,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Riki\SerialCode\Model\ResourceModel\SerialCodeFactory $serialCodeFactory
    )
    {
        $this->_httpFactory = $fileTransfer;
        $this->_serialCodeCollectionFactory = $collectionFactory;
        $this->_csvReader = $csvReader;
        $this->_logger = $loggerInterface;
        $this->_resourceModelFactory= $serialCodeFactory;
    }

    /**
     * Set header for import data
     *
     * @param array $header
     * @return $this
     */
    private function setHeader($header)
    {
        $this->_header = array_map('strtolower', $header);
        return $this;
    }

    /**
     * Validate header index
     *
     * @return array
     */
    private function validateHeader()
    {
        $errors = [];
        $columnRequired = [
            'serial_code', 'issued_point', 'serial_code_expiration_date',
            'serial_code_shipping_date', 'serial_code_user_customer_code',
            'campaign_id', 'limit_per_customer', 'wbs', 'account_code'
        ];
        foreach ($columnRequired as $column) {
            if (array_search($column, $this->_header) === false) {
                $errors[] = __('Missing column %1', strtoupper($column));
            }
        }
        return $errors;
    }

    /**
     * Combine csv data into key-value
     *
     * @param array $csvData
     * @return array
     */
    private function buildArray($csvData)
    {
        $result = [];
        foreach ($csvData as $key => $value) {
            $result[] = array_combine($this->_header, $value);
        }
        return $result;
    }

    /**
     * Prepare existing serial code to validate
     *
     * @param array $dataImport
     * @return $this
     */
    private function prepareData($dataImport)
    {
        $allSerialCodes = array_map(function($value) {
            return $value['serial_code'];
        }, $dataImport);
        $allSerialCodes = array_filter($allSerialCodes);
        if (!sizeof($allSerialCodes)) {
            return $this;
        }
        /** @var \Riki\SerialCode\Model\ResourceModel\SerialCode\Collection $collection */
        $collection = $this->_serialCodeCollectionFactory->create();
        $collection->addFieldToFilter('serial_code', ['in' => $allSerialCodes]);
        if (!$collection->getSize()) {
            return $this;
        }
        $this->_existed = array_map(function($value) {
            return $value['serial_code'];
        }, $collection->getData());
        return $this;
    }

    /**
     * Validate row import
     *
     * @param int $key
     * @param array $value
     * @return bool
     */
    private function validateRow($key, $value)
    {
        $key++;
        $isError = false;
        $dateValidator = new \Zend_Validate_Date(['format' => 'Y/m/d H:i:s']);
        $requiredField = ['serial_code', 'serial_code_shipping_date', 'wbs', 'account_code', 'issued_point'];
        foreach ($requiredField as $field) {
            if (!\Zend_Validate::is($value[$field], 'NotEmpty')) {
                $isError = true;
                $this->_statistic['EMPTY_'.$field][] = $key;
            }
        }
        if (\Zend_Validate::is($value['serial_code_shipping_date'], 'notEmpty') && !$dateValidator->isValid($value['serial_code_shipping_date'])) {
            $this->_statistic['dateInvalid'][] = $key;
            $isError = true;
        }
        if (\Zend_Validate::is($value['serial_code_regitration_date'], 'notEmpty') && !$dateValidator->isValid($value['serial_code_regitration_date'])) {
            $this->_statistic['dateInvalid'][] = $key;
            $isError = true;
        }
        if (\Zend_Validate::is($value['serial_code_expiration_date'], 'notEmpty') && !$dateValidator->isValid($value['serial_code_expiration_date'])) {
            $this->_statistic['dateInvalid'][] = $key;
            $isError = true;
        }
        if (\Zend_Validate::is($value['issued_point'], 'notEmpty') && !\Zend_Validate::is($value['issued_point'], 'Int')) {
            $this->_statistic['issuedPoint'][] = $key;
        } elseif (\Zend_Validate::is($value['issued_point'], 'notEmpty') &&
            \Zend_Validate::is($value['issued_point'], 'Int') &&
            $value['issued_point'] <= 0
        ) {
            $this->_statistic['issuedPoint'][] = $key;
            $isError = true;
        }

        if (\Zend_Validate::is($value['limit_per_customer'], 'notEmpty') && !\Zend_Validate::is($value['limit_per_customer'], 'Int')) {
            $this->_statistic['limitPer'][] = $key;
        } elseif (\Zend_Validate::is($value['limit_per_customer'], 'notEmpty') &&
            \Zend_Validate::is($value['limit_per_customer'], 'Int') &&
            $value['limit_per_customer'] <= 0
        ) {
            $this->_statistic['limitPer'][] = $key;
            $isError = true;
        }
        if (\Zend_Validate::is($value['serial_code'], 'notEmpty') && in_array($value['serial_code'], $this->_existed)) {
            $this->_statistic['codeExisted'][] = $key;
            $isError = true;
        }
        $wbsValidate = new \Zend_Validate_Regex('/^AC-\d{8}$/');
        if (\Zend_Validate::is($value['wbs'], 'notEmpty') && !$wbsValidate->isValid($value['wbs'])) {
            $this->_statistic['wbsInvalid'][] = $key;
            $isError = true;
        }
        if (\Zend_Validate::is($value['serial_code'], 'notEmpty') && in_array($value['serial_code'], $this->_validCode)) {
            $this->_statistic['codeDuplicate'][] = $key;
            $isError = true;
        }
        if (!$isError) {
            $this->_validCode[] = $value['serial_code'];
        }
        return !$isError;
    }

    /**
     * Prepare data for this row
     *
     * @param array $value
     * @return array
     */
    private function importRow($value)
    {
        try {
            $activationDate = \DateTime::createFromFormat('Y/m/d H:i:s', $value['serial_code_shipping_date']);
            $row = [
                'serial_code' => $value['serial_code'],
                'issued_point' => $value['issued_point'],
                'activation_date' => $activationDate->format('Y-m-d H:i:s'),
                'wbs' => $value['wbs'],
                'account_code' => $value['account_code'],
                'campaign_id' => $value['campaign_id'],
                'campaign_limit' => $value['limit_per_customer'],
                'customer_id' => $value['serial_code_user_customer_code'],
                'expiration_date' => 0,
                'point_expiration_period' => 0
            ];
            if ($value['serial_code_regitration_date']) {
                $usedDate = \DateTime::createFromFormat('Y/m/d H:i:s', $value['serial_code_regitration_date']);
                $row['used_date'] = $usedDate->format('Y-m-d H:i:s');
            }

            if ($value['serial_code_expiration_date']) {
                $expirationDate = \DateTime::createFromFormat('Y/m/d H:i:s', $value['serial_code_expiration_date']);
                $row['expiration_date'] = $expirationDate->format('Y-m-d H:i:s');
                $compare = new \Zend_Date();
                $expirationPeriod = $expirationDate->diff(new \DateTime())->days;
                if ($compare->isEarlier($row['expiration_date'], \Zend_Date::ISO_8601) && $expirationPeriod > 0) {
                    $row['point_expiration_period'] = $expirationPeriod;
                }
            }
            $this->_dataImport[] = $row;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * Import CSV data
     *
     * @param $fieldName
     * @return integer
     */
    public function doImport($fieldName)
    {
        $this->validateSource($fieldName, true);
        $inserted = 0;
        if (sizeof($this->_dataImport)) {
            try {
                /** @var \Riki\SerialCode\Model\ResourceModel\SerialCode $resourceModel */
                $resourceModel = $this->_resourceModelFactory->create();
                $table = $resourceModel->getMainTable();
                $inserted = $resourceModel->getConnection()->insertMultiple($table, $this->_dataImport);
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }
        return $inserted;
    }

    /**
     * Validate data before import
     *
     * @param string $fieldName
     * @param boolean $buildImport
     * @return array
     */
    public function validateSource($fieldName, $buildImport = false)
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
            $headerError = $this->setHeader($header)->validateHeader();
            if (sizeof($headerError)) {
                return ['error' => $headerError];
            }
            $dataImport = $this->buildArray($csvData);
            if (!sizeof($dataImport)) {
                return ['error' => [__('The was no record to import')]];
            }
            $this->prepareData($dataImport);
            $success = 0;
            foreach ($dataImport as $key => $value) {
                $value = array_map('trim', $value);
                if($this->validateRow($key, $value) === true) {
                    $success++;
                    if ($buildImport) {
                        $this->importRow($value);
                    }
                }
            }
            if (sizeof($this->_statistic)) {
                foreach ($this->_statistic as $key => $value) {
                    $value = array_unique($value);
                    if (strpos($key, 'EMPTY_') !== false) {
                        $detect = explode('EMPTY_', $key);
                        $msg = __('%1 is empty in rows: %2', strtoupper($detect[1]), implode(', ', $value));
                        $messages['error'][] = $msg;
                        continue;
                    }
                    switch ($key) {
                        case 'dateInvalid':
                            $msg = __('Date is not valid in rows: %1', implode(', ', $value));
                            break;
                        case 'issuedPoint':
                            $msg = __('Issued point is not valid in rows: %1', implode(', ', $value));
                            break;
                        case 'limitPer':
                            $msg = __('Limit Per Customer is not valid in rows: %1', implode(', ', $value));
                            break;
                        case 'codeExisted':
                            $msg = __('Serial code is already existed in rows: %1', implode(', ', $value));
                            break;
                        case 'wbsInvalid':
                            $msg = __('WBS format is not valid in rows: %1', implode(', ', $value));
                            break;
                        case 'codeDuplicate':
                            $msg = __('Serial code is duplicated in rows: %1', implode(', ', $value));
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