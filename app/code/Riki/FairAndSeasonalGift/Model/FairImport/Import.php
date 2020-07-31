<?php

namespace Riki\FairAndSeasonalGift\Model\FairImport;

class Import
{
    const FILE_TYPE_FAIR_DETAIL = 'fair_detail';
    const FILE_TYPE_FAIR_CONN = 'fair_conn';
    const FILE_TYPE__MNG = 'fair_mng';
    const FILE_TYPE_FAIR_RECOMMEND = 'fair_recommend';

    const COLUMN_FAIR_DETAIL_FAIR_ID = 'FAIR_ID'; // from fair mangaement
    const COLUMN_FAIR_DETAIL_PRODUCT_ID = 'FAIR_COMMODITY';
    const COLUMN_FAIR_DETAIL_SERIAL_NO = 'FAIR_COMMODITY_NUM';
    const COLUMN_FAIR_DETAIL_UPDATE_AT = 'UPDATED_DATETIME';
    const COLUMN_FAIR_DETAIL_IS_RECOMMEND = 'is_recommend';

    const COLUMN_FAIR_CONN_FAIR_ID = 'FAIR_ID';
    const COLUMN_FAIR_CONN_RELATED_ID = 'RELATION_FAIR_ID';
    const COLUMN_FAIR_CONN_RELATED_ORDER = 'RELATION_FAIR_ORDER';
    const COLUMN_FAIR_CONN_UPDATE_AT = 'UPDATED_DATETIME';

    const COLUMN_FAIR_MNG_FAIR_ID = 'FAIR_ID'; // auto
    const COLUMN_FAIR_MNG_FAIR_CODE = 'FAIR_ID';
    const COLUMN_FAIR_MNG_FAIR_YEAR = 'FAIR_YEAR';
    const COLUMN_FAIR_MNG_FAIR_TYPE = 'FAIR_KIND';
    const COLUMN_FAIR_MNG_FAIR_NAME = 'FAIR_NAME';
    const COLUMN_FAIR_MNG_START_DATE = 'FAIR_START_DATETIME';
    const COLUMN_FAIR_MNG_END_DATE = 'FAIR_END_DATETIME';
    const COLUMN_FAIR_MNG_CREATED_AT = 'CREATED_DATETIME';
    const COLUMN_FAIR_MNG_UPDATE_AT = 'UPDATED_DATETIME';
    const COLUMN_FAIR_MNG_MEM_IDS = 'FAIR_ID'; // Not in csv

    const COLUMN_FAIR_RECOMMEND_FAIR_ID = 'FAIR_ID'; // from fair mangaement
    const COLUMN_FAIR_RECOMMEND_RECOMMEND_FAIR_ID = 'FAIR_RECO_FAIR_ID';// from fair mangaement
    const COLUMN_FAIR_RECOMMEND_PRODUCT_ID = 'FAIR_COMMODITY';
    const COLUMN_FAIR_RECOMMEND_RECOMMEND_PRODUCT_ID = 'FAIR_RECO_COMMODITY';
    const COLUMN_FAIR_RECOMMEND_UPDATE_AT = 'UPDATED_DATETIME';
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

    /**
     * @var array
     */
    protected $_dataImport = [];
    /**
     * @var \Riki\FairAndSeasonalGift\Model\ResourceModel\FairConnection\CollectionFactory
     */
    protected $_collectionFairConnection;
    /**
     * @var \Riki\FairAndSeasonalGift\Model\ResourceModel\FairRecommendation\CollectionFactory
     */
    protected $_collectionFairRecommendation;
    /**
     * @var \Riki\FairAndSeasonalGift\Model\ResourceModel\FairDetail\CollectionFactory
     */
    protected $_collectionFairDetail;
    /**
     * @var \Riki\FairAndSeasonalGift\Model\ResourceModel\Fair\CollectionFactory
     */

    protected $_collectionFair;
    /**
     * @var \Riki\FairAndSeasonalGift\Model\ResourceModel\FairConnectionFactory
     */
    protected $_modelFairConnection;
    /**
     * @var \Riki\FairAndSeasonalGift\Model\ResourceModel\FairRecommendationFactory
     */
    /**
     * @var \Riki\FairAndSeasonalGift\Model\ResourceModel\FairDetailFactory
     */
    protected $_modelFairDetail;
    /**
     * @var \Riki\FairAndSeasonalGift\Model\ResourceModel\FairFactory
     */
    protected $_modelFair;
    /**
     *  Config array import for require and validate
     * @var array
     */
    protected $_columns = [
        self::FILE_TYPE_FAIR_DETAIL => [
            'fair_id'  =>  ['column'   =>  self::COLUMN_FAIR_DETAIL_FAIR_ID, 'type' =>  'text', 'update' =>  false],
            'product_id'    =>  ['column'   =>  self::COLUMN_FAIR_DETAIL_PRODUCT_ID, 'type' =>  'int'],
            'serial_no'    =>  ['column'   =>  self::COLUMN_FAIR_DETAIL_SERIAL_NO, 'type' =>  'text'],
            'updated_at'      =>  ['column'   =>  self::COLUMN_FAIR_DETAIL_UPDATE_AT, 'type' =>  'date'],
            'is_recommend'    =>  ['column'   =>  self::COLUMN_FAIR_DETAIL_IS_RECOMMEND, 'type' =>  'int', 'default'   =>  0, 'required'   =>  false]
        ],
        self::FILE_TYPE_FAIR_CONN => [
            'fair_id'  =>  ['column'   =>  self::COLUMN_FAIR_CONN_FAIR_ID, 'type' =>  'text'],
            'fair_related_id'  =>  ['column'   =>  self::COLUMN_FAIR_CONN_RELATED_ID, 'type' =>  'text'],
            'fair_related_order'  =>  ['column'   =>  self::COLUMN_FAIR_CONN_RELATED_ORDER, 'type' =>  'text'],
            'updated_at'      =>  ['column'   =>  self::COLUMN_FAIR_CONN_UPDATE_AT, 'type' =>  'date']
        ],
        self::FILE_TYPE_FAIR_RECOMMEND => [
            'fair_id'  =>  ['column'   =>  self::COLUMN_FAIR_RECOMMEND_FAIR_ID, 'type' =>  'text','default'   =>  0],
            'recommended_fair_id'  =>  ['column'   =>  self::COLUMN_FAIR_RECOMMEND_RECOMMEND_FAIR_ID, 'type' =>  'text'],
            'product_id'  =>  ['column'   =>  self::COLUMN_FAIR_RECOMMEND_PRODUCT_ID, 'type' =>  'text'],
            'recommended_product_id'  =>  ['column'   =>  self::COLUMN_FAIR_RECOMMEND_RECOMMEND_PRODUCT_ID, 'type' =>  'text'],
            'updated_at'    =>  ['column'   =>  self::COLUMN_FAIR_RECOMMEND_UPDATE_AT, 'type' =>  'date']
        ],
        self::FILE_TYPE__MNG => [
            'fair_code'  =>  ['column'   =>  self::COLUMN_FAIR_MNG_FAIR_CODE, 'type' =>  'text'],
            'fair_year'  =>  ['column'   =>  self::COLUMN_FAIR_MNG_FAIR_YEAR, 'type' =>  'int'],
            'fair_type'  =>  ['column'   =>  self::COLUMN_FAIR_MNG_FAIR_TYPE, 'type' =>  'int'],
            'fair_name'  =>  ['column'   =>  self::COLUMN_FAIR_MNG_FAIR_NAME, 'type' =>  'text'],
            'start_date'    =>  ['column'   =>  self::COLUMN_FAIR_MNG_START_DATE, 'type' =>  'date'],
            'end_date'    =>  ['column'   =>  self::COLUMN_FAIR_MNG_END_DATE, 'type' =>  'date'],
            'created_at'    =>  ['column'   =>  self::COLUMN_FAIR_MNG_CREATED_AT, 'type' =>  'date'],
            'updated_at'    =>  ['column'   =>self::COLUMN_FAIR_MNG_UPDATE_AT, 'type' =>  'date' ],
            'mem_ids'    =>  ['column'   =>self::COLUMN_FAIR_MNG_MEM_IDS, 'type' =>  'text' ]

        ]
    ];
    /**
     * Array file import
     * @var array
     */
    protected $_filesUploadName = [
        self::FILE_TYPE_FAIR_DETAIL    =>  'csv_file_detail',
        self::FILE_TYPE_FAIR_CONN    =>  'csv_file_conn',
        self::FILE_TYPE_FAIR_RECOMMEND    =>  'csv_file_recom',
        self::FILE_TYPE__MNG    =>  'csv_file_mng'
    ];

    protected $_headerIndex = [];

    protected $_uploadData = [];

    protected $_listConvertId = [self::COLUMN_FAIR_RECOMMEND_FAIR_ID,
        self::COLUMN_FAIR_CONN_RELATED_ID,
        self::COLUMN_FAIR_RECOMMEND_RECOMMEND_FAIR_ID];

    public function __construct(
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $fileTransfer,
        \Magento\Framework\File\Csv $csvReader,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\FairConnection\CollectionFactory $collectionFairConnection,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\FairRecommendation\CollectionFactory $collectionFairRecommendation,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\FairDetail\CollectionFactory $collectionFairDetail,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\Fair\CollectionFactory $collectionFair,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\FairConnectionFactory $modelFairConnection,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\FairRecommendationFactory $modelFairRecommendation,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\FairDetailFactory $modelFairDetail,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\FairFactory $modelFair,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
    )
    {
        $this->_httpFactory = $fileTransfer;
        $this->_csvReader = $csvReader;
        $this->_logger = $loggerInterface;
        $this->_collectionFairConnection = $collectionFairConnection;
        $this->_collectionFairRecommendation =$collectionFairRecommendation;
        $this->_collectionFair = $collectionFair;
        $this->_collectionFairDetail = $collectionFairDetail;

        $this->_modelFairConnection = $modelFairConnection;
        $this->_modelFairRecommendation =$modelFairRecommendation;
        $this->_modelFair = $modelFair;
        $this->_modelFairDetail = $modelFairDetail;
        $this->_uploaderFactory = $uploaderFactory;
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
        $messagesMng = $this->importMng();
        $messages = $this->_processMessage($messages, $messagesMng);
        $messagesDetail = $this->importDetail();
        $messages = $this->_processMessage($messages, $messagesDetail);
        $messagesRecom = $this->importRecommend();
        $messages = $this->_processMessage($messages, $messagesRecom);
        $messagesConn = $this->importConn();
        $messages = $this->_processMessage($messages, $messagesConn);
        $messages['success'][] =  __('Processed %1 row(s)', $messages['successCount']) ;
       return $messages;
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

    /**
     * Insert Management Fair
     * @return array with message status row import
     */
    public function importMng(){
        $messages = [];
        $insertedFair = [];
        $fileData = $this->_uploadData[self::FILE_TYPE__MNG];
        $row = 2;
        $importedNum = 0;

        $allFairId = $this->_getAllFairId();
        foreach($fileData as $rowData){
            $resultValidate = $this->_validateRowData(self::FILE_TYPE__MNG, $rowData, $row);
            if($resultValidate['is_valid'] === true){
                $fairId = $rowData[$this->_headerIndex[self::FILE_TYPE__MNG][self::COLUMN_FAIR_MNG_FAIR_CODE]];
                if(!in_array($fairId, $allFairId)){
                    $dataMng = $this->_prepareRowData($rowData, self::FILE_TYPE__MNG);
                    try {
                        $importedNum++;
                        $insertedFair[] = $fairId;
                        $resourceModel = $this->_modelFair->create();
                        $table = $resourceModel->getMainTable();
                        $resourceModel->getConnection()->insertMultiple($table, $dataMng);

                    } catch (\Exception $e) {
                        $this->_logger->critical($e);
                    }
                }else{
                // Duplicate message
                    $messages['error'][] = __('[%1][#%2][%3] Duplicate code', self::FILE_TYPE__MNG, $row, self::COLUMN_FAIR_MNG_FAIR_CODE);
                }
                

            }else{
                //Message  validate
                $messages['error'][] = $resultValidate['message'];
            }
        }
        // Message success
        $messages['success'][] =  $importedNum ;
        return  $messages;
    }

    /**
     * Insert detail Fair
     * @return array with message status row import
     */
    public function importDetail(){
        $messages = [];
        $fileData = $this->_uploadData[self::FILE_TYPE_FAIR_DETAIL];
        $row = 2;
        $importedNum = 0;


        foreach($fileData as $rowData){
            $resultValidate = $this->_validateRowData(self::FILE_TYPE_FAIR_DETAIL, $rowData, $row);
            if($resultValidate['is_valid'] === true){
                $dataDetail = $this->_prepareRowData($rowData, self::FILE_TYPE_FAIR_DETAIL);
                try {
                    $importedNum++;
                    $resourceModel = $this->_modelFairDetail->create();
                    $table = $resourceModel->getMainTable();
                    $resourceModel->getConnection()->insertMultiple($table, $dataDetail);

                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }

            }
            else{
                //Message  validate
                $messages['error'][] = $resultValidate['message'];
            }
        }
        // Message success
        $messages['success'][] =  $importedNum ;
        return  $messages;
    }

    /**
     * Import fair connection
     *  @return array with message status row import
     */
    public function importConn(){
        $messages = [];
        $fileData = $this->_uploadData[self::FILE_TYPE_FAIR_CONN];
        $row = 2;
        $importedNum = 0;


        foreach($fileData as $rowData){
            $resultValidate = $this->_validateRowData(self::FILE_TYPE_FAIR_CONN, $rowData, $row);
            if($resultValidate['is_valid'] === true){
                $dataConn= $this->_prepareRowData($rowData, self::FILE_TYPE_FAIR_CONN);
                try {
                    $importedNum++;
                    /** @var \Riki\SerialCode\Model\ResourceModel\SerialCode $resourceModel */
                    $resourceModel = $this->_modelFairConnection->create();
                    $table = $resourceModel->getMainTable();
                    $resourceModel->getConnection()->insertMultiple($table, $dataConn);

                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }

            }else{
                //Message  validate
                $messages['error'][] = $resultValidate['message'];
            }
        }
        $messages['success'][] =  $importedNum ;
        return  $messages;
    }

    /**
     * Import fair recommend
     *  @return array with message status row import
     */
    public function importRecommend(){
        $messages = [];
        $fileData = $this->_uploadData[self::FILE_TYPE_FAIR_RECOMMEND];
        $row = 2;
        $importedNum = 0;


        foreach($fileData as $rowData){
            $resultValidate = $this->_validateRowData(self::FILE_TYPE_FAIR_RECOMMEND, $rowData, $row);
            if($resultValidate['is_valid'] === true){
                $dataRecom = $this->_prepareRowData($rowData, self::FILE_TYPE_FAIR_RECOMMEND);
                try {
                    $importedNum++;
                    $resourceModel = $this->_modelFairRecommendation->create();
                    $table = $resourceModel->getMainTable();
                    $resourceModel->getConnection()->insertMultiple($table, $dataRecom);
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }

            }else{
                //Message  validate
                $messages['error'][] = $resultValidate['message'];
            }
        }
        $messages['success'][] = $importedNum ;
        return  $messages;
    }
    /**
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
                    if(in_array($config['column'], $this->_listConvertId)  && $type != self::FILE_TYPE__MNG ){
                        $columnData = $this->_getFairId($rowData[$this->_headerIndex[$type][$config['column']]]);
                    }else{
                        $columnData = $rowData[$this->_headerIndex[$type][$config['column']]];
                    }
                    
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
     * @param $fairCode
     * @return string
     */
    protected function _getFairId($fairCode){
        $fairInfo = $this->_collectionFair->create()
            ->addFieldToSelect('fair_id')
            ->addFieldToFilter('fair_code',$fairCode)
            ->setPageSize(1)
            ->getFirstItem() ;
        if( $fairInfo->getFairId()){
           return  $fairInfo->getFairId();
        }
        return
            '';
    }

    /**
     * Get all Id fair Id management
     * @return array
     */
    protected function _getAllFairId(){
        $arrayFairCode = [];
        $fairAll = $this->_collectionFair->create()
            ->addFieldToSelect('fair_code')->toArray();
        if( $fairAll){
            foreach ($fairAll['items'] as $item){
                $arrayFairCode[] = $item['fair_code'];
            }
        }
        return $arrayFairCode;
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
            if($type == 'error'){
                $messages['error'] [] = $arrMsg;
            }else{
                if(isset($messages['successCount'])){
                    $messages['successCount'] = $messages['successCount']+ $arrMsg[0];
                }else{
                    $messages['successCount'] = $arrMsg[0];
                }
            }

        }
        return $messages;
    }

}