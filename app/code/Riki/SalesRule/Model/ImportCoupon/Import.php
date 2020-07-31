<?php

namespace Riki\SalesRule\Model\ImportCoupon;

use Magento\SalesRule\Model\ResourceModel\Rule;

class Import
{
    const FILE_TYPE_COUPON_PRODUCT = 'coupon_product';
    const FILE_TYPE_COUPON_CONTENT = 'coupon_content';

    const COLUMN_SALESRULE_NAME = 'ECOUPON_NAME_PC';
    const COLUMN_SALESRULE_COUPON = 'ECOUPON_ID';
    const COLUMN_SALESRULE_ACTIVE= 'VALID_FLG';
    const COLUMN_SALESRULE_START_TIME = 'ECOUPON_START_DATETIME';
    const COLUMN_SALESRULE_END_TIME = 'ECOUPON_END_DATETIME';
    const COLUMN_SALESRULE_UPDATED_AT= 'UPDATED_DATETIME';
    const COLUMN_SALESRULE_ORM_ID = 'ORM_ROWID';

    const COLUMN_SALESRULE_PRODUCT_COUPON = 'ECOUPON_ID';
    const COLUMN_SALESRULE_PRODUCT_SKU = 'SKU_CODE';
    const COLUMN_SALESRULE_PRODUCT_RATE = 'ECOUPON_RATE';

    /**
     * @var \Magento\Framework\HTTP\Adapter\FileTransferFactory
     */
    protected $_httpFactory;
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $_csvReader;
    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_uploaderFactory ;
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
    protected $_status = [];

    /**
     * @var array
     */
    protected $_validCode = [];
    protected $_ruleFactory;
    protected $_rulePromo;

    protected $_columns = [
        self::FILE_TYPE_COUPON_CONTENT => [
            'name'  =>  ['column'   =>  self::COLUMN_SALESRULE_NAME, 'type' =>  'text', 'update' =>  false],
            'from_date'    =>  ['column'   =>  self::COLUMN_SALESRULE_START_TIME, 'type' =>  'date'],
            'to_date'    =>  ['column'   =>  self::COLUMN_SALESRULE_END_TIME, 'type' =>  'date'],
            'promo_updated_at'      =>  ['column'   =>  self::COLUMN_SALESRULE_UPDATED_AT, 'type' =>  'date'],
            'is_active'    =>  ['column'   =>  self::COLUMN_SALESRULE_ACTIVE, 'type' =>  'int', 'default'   =>  0, 'required'   =>  false],
            'website_ids'    =>  ['column'   =>  'website_ids', 'type' =>  'array', 'default'   =>  array('1',), 'required'   =>  false],
            'coupon_code'    =>  ['column'   =>  self::COLUMN_SALESRULE_COUPON, 'type' =>  'text','required'   =>  false],
            //'coupon_code'    =>  ['column'   =>  'coupon_code', 'type' =>  'int', 'default'   =>  0, 'required'   =>  false]
        ],
        self::FILE_TYPE_COUPON_PRODUCT => [
            self::COLUMN_SALESRULE_PRODUCT_COUPON  =>  ['column'   =>  self::COLUMN_SALESRULE_PRODUCT_COUPON, 'type' =>  'text'],
            self::COLUMN_SALESRULE_PRODUCT_SKU  =>  ['column'   =>  self::COLUMN_SALESRULE_PRODUCT_SKU, 'type' =>  'text'],

        ]
    ];
    /**
     * Array file import
     * @var array
     */
    protected $_filesUploadName = [
        self::FILE_TYPE_COUPON_CONTENT    =>  'csv_file_coupon_content',
        self::FILE_TYPE_COUPON_PRODUCT    =>  'csv_file_coupon_product',
    ];

    protected $_headerIndex = [];

    protected $_uploadData = [];
    protected $_ruleModel;

    public function __construct(
        \Magento\SalesRule\Model\ResourceModel\RuleFactory $ruleFactory,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $fileTransfer,
        \Magento\Framework\File\Csv $csvReader,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\SalesRule\Model\Rule $ruleModel,
        \Amasty\Promo\Model\Rule $rulePromo
    )
    {
        $this->_httpFactory = $fileTransfer;
        $this->_csvReader = $csvReader;
        $this->_logger = $loggerInterface;
        $this->_uploaderFactory = $uploaderFactory;
        $this->_ruleFactory = $ruleFactory;
        $this->_ruleModel = $ruleModel;
        $this->_rulePromo = $rulePromo;
    }



    /**
     * Validate data before import
     *
     * @param string $fieldName
     * @param boolean $buildImport
     * @return array
     */
    public function validateImprort($buildImport = false){
        $messages = [];
        foreach($this->_filesUploadName as $type    =>  $fileName){
                $file = $this->_uploaderFactory->create(['fileId' => $fileName]);
                $file->setAllowedExtensions(['csv']);
                try{
                    $file = $file->validateFile();
                    $this->_csvReader->setLineLength(1000);
                    $this->_uploadData[$type] = $this->_csvReader->getData($file['tmp_name']);
                    $valiDateHeader  = $this->_checkCsvHeaders(array_shift($this->_uploadData[$type]), $type);
                    if($valiDateHeader !== true){
                        return $valiDateHeader;
                    }
                }catch(\Exception $e){
                    $messages['error'][] = $e->getMessage();
                    return $messages;

                }
        }
        return  $this->importSalesRule();
    }

    /**
     * @param $headers
     * @param $type
     * @return bool
     */
    protected function _checkCsvHeaders($headers, $type){
        $messages = [];
        if (empty($headers) || count(array_unique($headers)) != count($headers)) {
            return false;
        }

        foreach($this->_columns[$type] as $name =>  $config){
            if(
                !in_array($config['column'], $headers)
                && (
                    !isset($config['required'])
                    ||
                    (isset($config['required']) && $config['required'])
                )
            )
                $messages['error'][] = __('Wrong Format File - %1 in column %2.', $type,$config['column']);

        }
        if(sizeof($messages)){
            return $messages;
        }
        $this->_headerIndex[$type] = [];

        foreach($headers as $index  =>  $fieldName){
            $this->_headerIndex[$type][$fieldName]  =  $index;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function _validate(){
        $messages = [];
        foreach($this->_filesUploadName as $type    =>  $fileName){
                $file = $this->_uploaderFactory->create(['fileId' => $fileName]);
                $file->setAllowedExtensions(['csv']);

                try{
                    $file = $file->validateFile();
                    $this->_csvReader->setLineLength(1000);
                    $this->_uploadData[$type] = $this->_csvReader->getData($file['tmp_name']);
                    if(!$this->_checkCsvHeaders(array_shift($this->_uploadData[$type]), $type)){
                        $messages['error'][] =__('Wrong Format File - %1.', $type);
                    }
                }catch(\Exception $e){
                    $messages['error'][] = $e->getMessage();
                    return $messages;

                }
        }
        if (sizeof($messages)){
            return  $messages;
        }
        return true;
    }

   /*
     * @param $rowData
     * @param $type
     * @param bool|false $isUpdate
     * @return array
     */
    protected function _prepareRowData($rowData, $type, $isUpdate = false){
        $result = [];

        $columns = $this->_columns[$type];

        foreach($columns as $name   =>  $config){

            $columnData = '';

            if(isset($this->_headerIndex[$type][$config['column']])){
                if(isset($rowData[$this->_headerIndex[$type][$config['column']]])){
                    $columnData = $rowData[$this->_headerIndex[$type][$config['column']]];

                }else{
                    if(isset($config['default']))
                        $columnData = $config['default'];
                }
            }else{
                if(isset($config['default']))
                    $columnData = $config['default'];
            }

            if(!$isUpdate || !isset($config['update']) || $config['update']){
                $result[$name]  = $columnData;
            }
        }

        return $result;
    }
    /**
     * validate type for value
     *
     * @param $data
     * @param $config
     * @return bool
     */
    protected function _validateTypeData($data, $config)
    {
        switch ($config['type']) {
            case 'select':
                $valid = in_array($data, $config['options']);
                break;
            case 'multiselect':
                $items = explode(';', $data);
                $valid = true;
                foreach($items as $item){
                    if(!in_array(trim($item), $config['options'])){
                        $valid = false;
                    }
                }
                break;
            case 'int':
                $val = trim($data);
                $valid = (string)(int)$val == $val;
                break;
            case 'float':
                $val = trim($data);
                $valid = (string)(float)$val == $val;
                break;
            case 'url':
                $valid = filter_var($data, FILTER_VALIDATE_URL);
                break;
            case 'date':
                $d = \DateTime::createFromFormat('Y/m/d H:i:s', $data);
                $valid = $d && $d->format('Y/m/d H:i:s') == $data;
                break;
            case 'text-required':
                $valid = empty($data) == false;
                break;
            default:
                $valid = true;
                break;
        }

        return $valid;
    }
    /**
     * @param string $fileType
     * @param array $data
     * @param $rowNum
     * @return bool
     */
    protected function _validateRowData($fileType, array $data, $rowNum){
        $validateReturn = [];
        $validateReturn['is_valid'] = true;
        $validateReturn['message'] = '';
        $columns = $this->_columns[$fileType];
        $headerIndex = $this->_headerIndex[$fileType];

        foreach($columns as $name   => $config){

            $value = '';

            if(isset($headerIndex[$config['column']])){
                $index = $headerIndex[$config['column']];
                if(isset($data[$index])){
                    $value = $data[$index];
                }
            }

            if((string)$value == ''){
                if(!isset($config['required']) || $config['required']){
                    $validateReturn['is_valid'] = false;
                    $validateReturn['message'] =  __('[%1][#%2][%3] Missing data', $fileType, $rowNum, $config['column']);
                }
            }else{
                if(!$this->_validateTypeData($value, $config)){
                    $validateReturn['is_valid'] = false;
                    $validateReturn['message'] = __('[%1][#%2][%3] Data "%4" is invalid, a %5 value is required', $fileType, $rowNum, $config['column'], $value, $config['type']);
                }
            }
        }

        return $validateReturn;
    }



    /**
     * Merge all message for detail
     * @param $messages
     * @param $messagesCheck
     * @return mixed
     */
    private function _processMessage($messages,$messagesCheck)
    {
        foreach ($messagesCheck as $type => $arrMsg) {
            $type == 'error' ? $messages['error'] [] = $arrMsg : $messages['success'][] = $arrMsg;

        }
        return $messages;
    }
    public function importSalesRule(){
        $messages = [];
        $row = 2;
        $importedNum = 0;
        $fileDataRule = $this->_uploadData[self::FILE_TYPE_COUPON_CONTENT];
        foreach($fileDataRule as $rowData){
            $isValid = $this->_validateRowData(self::FILE_TYPE_COUPON_CONTENT, $rowData,$row );
            if($isValid){
                $dataInsert = $this->_prepareRowData($rowData, self::FILE_TYPE_COUPON_CONTENT,false);
                try {
                    $importedNum++;
                    $shoppingCartPriceRule = $this->_ruleModel;

                    $shoppingCartPriceRule->setName($dataInsert['name'])
                        ->setDescription('')
                        ->setFromDate($dataInsert['from_date'])
                        ->setToDate($dataInsert['to_date'])
                        ->setUsesPerCustomer('0')
                        ->setCustomerGroupIds(array('0',))
                        ->setIsActive('1')
                        ->setStopRulesProcessing('0')
                        ->setIsAdvanced('1')
                        ->setProductIds(NULL)
                        ->setSortOrder('1')
                        ->setSimpleAction('ampromo_items')
                        ->setDiscountAmount(2)
                        ->setDiscountQty(NULL)
                        ->setDiscountStep('0')
                        ->setSimpleFreeShipping('0')
                        ->setApplyToShipping('0')
                        ->setTimesUsed('0')
                        ->setIsRss('0')
                        ->setWebsiteIds(array('1',))
                        ->setCouponType('1')
                        ->setCouponCode(NULL)
                        ->setUsesPerCoupon(NULL);
                    $dateSave = $shoppingCartPriceRule->save();
                    $latesId = $dateSave->getRuleId();
                    $listSKu = $this->getCoupon($dataInsert['coupon_code']);
                    if ($latesId) {
                        $ampromoRule = $this->_rulePromo;
                        $ampromoData['type'] = 0 ;
                        $ampromoData['sku'] = $listSKu ;
                        $ampromoData['att_visible_cart'] = 1 ;
                        $ampromoData['att_visible_user_account'] = 1 ;
                        $ampromoRule
                            ->load($latesId, 'salesrule_id')
                            ->addData($ampromoData)
                            ->setData('salesrule_id', $latesId)
                            ->save()
                        ;
                    }
                    // Message success
                    $messages['success'][] = __('%1 - Processed %2 row(s)', self::FILE_TYPE_COUPON_CONTENT, $importedNum) ;
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            }
            
        $row++;
        }
        return $messages;
    }

    public function  getCoupon($code){
        $arraySku = [];
        $dataSku = $this->_uploadData[self::FILE_TYPE_COUPON_PRODUCT];
        foreach ($dataSku as $sku){
            $dataProduct = $this->_prepareRowData($sku, self::FILE_TYPE_COUPON_PRODUCT,false);
            if($dataProduct[self::COLUMN_SALESRULE_PRODUCT_COUPON]== $code) {
                $arraySku[] = $dataProduct[self::COLUMN_SALESRULE_PRODUCT_SKU];
            }
        }
        return implode(",",$arraySku);
    }

}
