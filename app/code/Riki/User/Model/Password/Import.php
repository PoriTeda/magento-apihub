<?php

namespace Riki\User\Model\Password;


use Magento\Framework\Exception\LocalizedException;

class Import
{
    /**
     * @var \Magento\Framework\HTTP\Adapter\FileTransferFactory
     */
    protected $_httpFactory;


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
     * @var \Riki\User\Model\PasswordFactory
     */
    protected $_passwordModel;
    /**
     * @var array
     */
    protected $_dataImport = [];
    /**
     * @var \Riki\User\Model\ResourceModel\Password\CollectionFactory
     */
    protected $_passwordCollection;
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
     * Import constructor.
     *
     * @param \Magento\Framework\HTTP\Adapter\FileTransferFactory $fileTransfer
     * @param \Magento\Framework\File\Csv $csvReader
     * @param \Psr\Log\LoggerInterface $loggerInterface
     */
    public function __construct(
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $fileTransfer,
        \Riki\User\Model\PasswordFactory  $passwordModel,
        \Riki\User\Model\ResourceModel\PasswordFactory $resourceModelFactory,
        \Riki\User\Model\ResourceModel\Password\CollectionFactory $passwordCollection,
        \Magento\Framework\File\Csv $csvReader,
        \Psr\Log\LoggerInterface $loggerInterface
    )
    {
        $this->_httpFactory = $fileTransfer;
        $this->_csvReader = $csvReader;
        $this->_logger = $loggerInterface;
        $this->_passwordModel = $passwordModel;
        $this->_passwordCollection =  $passwordCollection;
        $this->_resourceModelFactory = $resourceModelFactory;

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
    public function validateSource($fieldName,$buildImport = false)
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
                            $msg = __('CREATED DATETIME not valid in rows: %1', implode(', ', $value));
                            break;
                        case 'codeExisted':
                            $msg = __('NG WORD is already existed in rows: %1', implode(', ', $value));
                            break;
                       ;
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
            'ng_word', 'created_datetime'
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
        $allNgWord = array_map(function($value) {
            return $value['ng_word'];
        }, $dataImport);
        $allNgWordCodes = array_filter($allNgWord);
        if (!sizeof($allNgWordCodes)) {
            return $this;
        }
        /** @var \Riki\SerialCode\Model\ResourceModel\SerialCode\Collection $collection */
        $collection = $this->_passwordCollection->create();
        $collection->addFieldToFilter('ng_word', ['in' => $allNgWordCodes]);
        if (!$collection->getSize()) {
            return $this;
        }
        $this->_existed = array_map(function($value) {
            return $value['ng_word'];
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
        $dateValidator = new \Zend_Validate_Date(['format' => 'Y-m-d H:i:s']);
        $requiredField = ['ng_word', 'created_datetime'];
        foreach ($requiredField as $field) {
            if (!\Zend_Validate::is($value[$field], 'NotEmpty')) {
                $isError = true;
                $this->_statistic['EMPTY_'.$field][] = $key;
            }
        }
        if (\Zend_Validate::is($value['created_datetime'], 'notEmpty') && !$dateValidator->isValid($value['created_datetime'])) {
            $this->_statistic['dateInvalid'][] = $key;
            $isError = true;
        }

        if (\Zend_Validate::is($value['ng_word'], 'notEmpty') && in_array($value['ng_word'], $this->_existed)) {
            $this->_statistic['codeExisted'][] = $key;
            $isError = true;
        }

        if (!$isError) {
            $this->_validCode[] = $value['ng_word'];
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
            $activationDate = \DateTime::createFromFormat('Y-m-d H:i:s', $value['created_datetime']);
            $row = [
                'ng_word' => $value['ng_word'],
                'created_datetime' => $value['created_datetime']
            ];
            $this->_dataImport[] = $row;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }
}