<?php

namespace Riki\Fraud\Model\Rule;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Yaml\Parser as YamlParser;

class Import
{
    const FIRSTNAME_KANA = 'FIRST_NAME_KANA';
    const LASTNAME_KANA = 'LAST_NAME_KANA';
    const CONDITIONALL = 'all';
    const CONDITIONANY = 'any';
    const CONDITIONTYPE = 'CONDITION';
    /**
     * @var \Magento\Framework\HTTP\Adapter\FileTransferFactory
     */
    protected $_httpFactory;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $_file;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var \Mirasvit\FraudCheck\Model\RuleFactory
     */
    protected $_ruleFactory;
    /**
     * @var \Mirasvit\FraudCheck\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $_ruleCollectionFactory;

    /*defined map column from import file and customer attributes*/
    protected $_dataMapping;

    /*global variable to store current condition of import data*/
    protected $_conditionData;

    /**
     * Import constructor.
     * @param \Magento\Framework\HTTP\Adapter\FileTransferFactory $fileTransfer
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Mirasvit\FraudCheck\Model\RuleFactory $ruleFactory
     * @param \Mirasvit\FraudCheck\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory
     */
    public function __construct(
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $fileTransfer,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Psr\Log\LoggerInterface $logger,
        \Mirasvit\FraudCheck\Model\RuleFactory $ruleFactory,
        \Mirasvit\FraudCheck\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory
    ) {
        $this->_ruleCollectionFactory = $ruleCollectionFactory;
        $this->_httpFactory = $fileTransfer;
        $this->_file = $file;
        $this->_ruleFactory = $ruleFactory;
        $this->_logger = $logger;
    }

    /**
     * @return array
     */
    public function getMappingData()
    {
        if (empty($this->_dataMapping)) {
            $this->_dataMapping = [
                /*customer attribute*/
                'CUSTOMER_CODE' => [
                    'type' => 'Mirasvit\FraudCheck\Model\Rule\Condition\Customer',
                    'attribute' => 'consumer_db_id'
                ],
                'EMAIL' => [
                    'type' => 'Mirasvit\FraudCheck\Model\Rule\Condition\Customer',
                    'attribute' => 'email'
                ],
                'FIRST_NAME' => [
                    'type' => 'Mirasvit\FraudCheck\Model\Rule\Condition\Customer',
                    'attribute' => 'firstname'
                ],
                'LAST_NAME' => [
                    'type' => 'Mirasvit\FraudCheck\Model\Rule\Condition\Customer',
                    'attribute' => 'lastname'
                ],
                'FIRST_NAME_KANA' => [
                    'type' => 'Mirasvit\FraudCheck\Model\Rule\Condition\Customer',
                    'attribute' => 'firstnamekana'
                ],
                'LAST_NAME_KANA' => [
                    'type' => 'Mirasvit\FraudCheck\Model\Rule\Condition\Customer',
                    'attribute' => 'lastnamekana'
                ],
                'PREFECTURE' => [
                    'type' => 'Mirasvit\FraudCheck\Model\Rule\Condition\Billing',
                    'attribute' => 'region'
                ],
                'CITY' => [
                    'type' => 'Mirasvit\FraudCheck\Model\Rule\Condition\Billing',
                    'attribute' => 'city'
                ],
                'STREET' => [
                    'type' => 'Mirasvit\FraudCheck\Model\Rule\Condition\Billing',
                    'attribute' => 'street'
                ],
                'PHONE_NUMBER' => [
                    'type' => 'Mirasvit\FraudCheck\Model\Rule\Condition\Customer',
                    'attribute' => 'phone_number'
                ]
            ];
        }

        return $this->_dataMapping;
    }

    /**
     * insert new condition to db
     *
     * @param $value
     * @return bool
     */
    private function _insertRow($value)
    {
        if (!empty($value)) {
            /*generate condition data*/
            $dataRule = $this->generateRuleData($value);

            $model = $this->_ruleFactory->create();

            $model->addData($dataRule['data']);

            if (isset($dataRule['rule'])) {
                $model->loadPost($dataRule['rule']);
            }

            try {
                $model->save();
                return true;
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                return false;
            }
        }

        return false;
    }

    /**
     * generate rule data
     *
     * @param $value
     * @return array
     */
    public function generateRuleData($value)
    {
        /*general data*/
        $dataRule = [
            'data'=> [
                'name' => 'Blacklist Customer',
                'is_active' => 1,
                'status' => 'review'
            ]
        ];

        /*empty old condition*/
        $this->_conditionData = [];

        /*generate new condition data and parse value to $this->_conditionData*/
        $this->generateConditionData($value, 1);

        /*condition data*/
        $dataRule["rule"] = ["conditions" => $this->_conditionData];

        return $dataRule;
    }

    /**
     * generate condition data
     *
     * @param $value
     * @param $conditionKey
     * @return mixed
     */
    public function generateConditionData($value, $conditionKey)
    {
        $this->_conditionData[$conditionKey] = $this->generateCombineCondition($value, $conditionKey);

        $k = 1;

        foreach ($value as $key => $val) {

            /*use to map nested condition*/
            $cdtKey = $conditionKey.'--'.$k;

            /*condition data { array or boolean } */
            $conditionCode = $this->getConditionData($key, $val, $cdtKey);

            if ($conditionCode) {
                $k++;
                if (is_array($conditionCode)) {
                    $this->_conditionData[$cdtKey] = $conditionCode;
                }
            }
        }

        return true;
    }

    /**
     * @param $value
     * @param $conditionKey
     * @return array
     */
    public function generateCombineCondition($value, $conditionKey)
    {
        /*condition type {any, all}*/
        $conditionType = $this->getConditionType($value);

        return [
            'type' => $conditionKey === 1 ? 'Magento/Rule/Model/Condition/Combine' : 'Mirasvit\FraudCheck\Model\Rule\Condition\Combine',
            'aggregator' => $conditionType,
            'value' => 1,
            'new_child' => ''
        ];
    }


    /**
     * Import rule
     *
     * @param string $fieldName
     * @return array
     * @throws \Exception
     */
    public function doImport($fieldName)
    {
        $result = ['error' => 0, 'success' => 0];

        try {

            /*begin read import file*/
            $adapter = $this->_httpFactory->create();

            $adapter->addValidator('Extension', false, 'yml');

            $fileTransfer = $adapter->getFileInfo();

            /*begin parse data from yaml file to array*/
            $yamlFile = $fileTransfer[$fieldName];

            $fileContent = $this->_file->fileGetContents($yamlFile['tmp_name']);

            $yamlParser = new YamlParser();
            $data = $yamlParser->parse($fileContent);

            /*process import data - validate and insert to db*/
            foreach ($data as $key => $value) {

                /*validate data*/
                if ($this->_validateRow($value)) {

                    /*insert data*/
                    if ($this->_insertRow($value)) {
                        $result['success']++;
                        continue;
                    }
                }

                $result['error']++;
            }

        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }

        return $result;
    }
    /**
     * @param integer $key
     * @param array $row
     * @return bool
     */
    private function _validateRow($row)
    {
        foreach ($row as $column){
            if($column != null and $column != ''){
                return true;
            }
        }
        return false;
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

            /*begin read import file*/
            $adapter = $this->_httpFactory->create();

            $adapter->addValidator('Extension', false, 'yml');

            if (!$adapter->isValid($fieldName)) {
                throw new LocalizedException(__('The file does not matched with predefined format!'));
            }

            /*begin parse import data to array*/
            $fileTransfer = $adapter->getFileInfo();

            $yamlFile = $fileTransfer[$fieldName];

            $fileContent = $this->_file->fileGetContents($yamlFile['tmp_name']);

            $yamlParser = new YamlParser();
            $data = $yamlParser->parse($fileContent);

            /*begin validate import data*/
            $success = 0;

            if (!empty($data)) {

                foreach ($data as $rules) {
                    if($this->_validateRow($rules) === true) {
                        $success++;
                    }
                }

                if ($success) {
                    $messages['success'][] = __('Validate successful %1 rows', $success);
                }


            } else {
                throw new LocalizedException(__('File is empty!'));
            }

        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $messages['error'][] = $e->getMessage();
        }
        return $messages;
    }

    /**
     * get condition data for attribues
     *
     * @param $key - use to get attribute data { type, name }
     * @param $value
     * @return array|bool
     */

    /**
     * get condition data for attributes
     *
     * @param $key
     * @param $value
     * @param $conditionKey
     * @return array|bool
     */
    public function getConditionData($key, $value, $conditionKey)
    {
        $mappingData = $this->getMappingData();

        if (!empty($mappingData[$key])) {

            /*get condition operator */
            $operator = $this->getOperatorByValue($value);

            /*get condition value*/
            $conditionValue = $this->getConditionValue($value);

            return [
                'type' => $mappingData[$key]['type'],
                'attribute' => $mappingData[$key]['attribute'],
                'operator' => $operator,
                'value' => $conditionValue
            ];
        } else if (strpos($key, self::CONDITIONTYPE) !== false) {
            return $this->generateConditionData($value, $conditionKey);
        }

        return false;
    }

    /**
     * get condition type, default is any
     *
     * @param $value
     * @return string
     */
    public function getConditionType($value)
    {
        if (!empty($value['TYPE']) && $value['TYPE'] == self::CONDITIONALL) {
            return self::CONDITIONALL;
        }
        return self::CONDITIONANY;
    }

    /**
     * get condition operator
     *
     * @param $value
     * @return string
     */
    public function getOperatorByValue($value)
    {
        if (is_array($value)) {
            $operator = '()';
        } else {
            $operator = '==';
        }
        return $operator;
    }

    /**
     * get condition value
     *
     * @param $value
     * @return string
     */
    public function getConditionValue($value)
    {
        if (is_array($value)) {
            $rs = implode(',', $value);
        } else {
            $rs = $value;
        }
        return $rs;
    }
}