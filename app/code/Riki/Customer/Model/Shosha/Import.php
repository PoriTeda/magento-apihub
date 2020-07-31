<?php

namespace Riki\Customer\Model\Shosha;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;

class Import
{
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_writeInterface;
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $_csvReader;
    /**
     * @var \Magento\Framework\HTTP\Adapter\FileTransferFactory
     */
    protected $_fileTransfer;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;
    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_uploaderFactory;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Riki\Customer\Model\ShoshaFactory
     */
    protected $_shoshaFactory;

    /**
     * @var \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory
     */
    protected $_shoshaCollectionFactory;

    /**
     * @var \Riki\Customer\Model\Shosha\ShoshaCode
     */
    protected $_shoshaCode;

    /**
     * @var \Riki\Customer\Model\Shosha\StoreCode
     */
    protected $_storeCode;

    /**
     * @var \Riki\Customer\Model\Shosha\ShoshaValidation
     */
    protected $_shoshaValidation;

    /**
     * Import constructor.
     *
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\File\Csv $csvReader
     * @param \Magento\Framework\HTTP\Adapter\FileTransferFactory $fileTransfer
     * @param DirectoryList $directoryList
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Customer\Model\ShoshaFactory $shoshaFactory
     * @param \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory $shoshaCollectionFactory
     * @param ShoshaCode $shoshaCode
     * @param StoreCode $storeCode
     * @param ShoshaValidation $shoshaValidation
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\File\Csv $csvReader,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $fileTransfer,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Customer\Model\ShoshaFactory $shoshaFactory,
        \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory $shoshaCollectionFactory,
        \Riki\Customer\Model\Shosha\ShoshaCode $shoshaCode,
        \Riki\Customer\Model\Shosha\StoreCode $storeCode,
        \Riki\Customer\Model\Shosha\ShoshaValidation $shoshaValidation
    ) {
        $this->_writeInterface = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->_csvReader = $csvReader;
        $this->_fileTransfer = $fileTransfer;
        $this->_directoryList = $directoryList;
        $this->_uploaderFactory = $uploaderFactory;
        $this->_logger = $logger;
        $this->_shoshaFactory = $shoshaFactory;
        $this->_shoshaCollectionFactory = $shoshaCollectionFactory;
        $this->_shoshaCode = $shoshaCode;
        $this->_storeCode = $storeCode;
        $this->_shoshaValidation = $shoshaValidation;
    }

    /**
     * @return array
     */
    public function shoshaHeader(){
        return [
            'COMPANY_CODE', 'COMPANY_NAME', 'COMPANY_NAME_KANA', 'COMPANY_POST_NAME',
            'COMPANY_POST_NAME', 'COMPANY_CHARGE_NAME', 'COMPANY_ADDRESS1', 'COMPANY_ADDRESS2',
            'COMPANY_ADDRESS_KANA1', 'COMPANY_ADDRESS_KANA2', 'COMPANY_PHONE_NUMBER', 'CORPORATION_CODE',
            'FIRST_CODE', 'SECONDARY_CODE', 'COMMISSION_TYPE', 'COMPANY_POST_NAME_KANA',
            'COMPANY_POSTAL_CODE', 'blocked', 'UPDATED_DATETIME', 'CREATED_DATETIME'
        ];
    }

    /**
     * Validate header index
     *
     * @param array $header
     * @return array
     */
    public function validateHeader($header)
    {
        $errors = [];
        if(empty($header)){
            $errors[] = __('Data is null');
        }else{
            $shoshaHeader = $this->shoshaHeader();

            foreach ($shoshaHeader as $vl){
                if(!in_array($vl, $header)){
                    $errors[] = __('Header: '.$vl.' is not define');
                }
            }
        }
        return $errors;
    }

    /**
     * Insert winner rule to database
     *
     * @param array $value
     * @return bool
     */
    public function insertRow($value)
    {
        $model = $this->getShoshaModel($value);

        $this->setData( $model, $value );

        try {
            $model->save();
            return true;
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            return false;
        }
    }

    /**
     * @param $value
     * @return \Magento\Framework\DataObject|\Riki\Customer\Model\Shosha
     */
    public function getShoshaModel( $value ){
        /*shosha customer id from old system*/
        if( !empty($value[18]) ){
            $shoshaCollection = $this->_shoshaCollectionFactory->create();
            $shoshaCollection->addFieldToFilter('orm_rowid', $value[18]);
            if($shoshaCollection->getSize()){
                return $shoshaCollection->getFirstItem();
            }
        }

        return $this->_shoshaFactory->create();
    }

    public function setData($model, $value){

        try{
            $model->setData('shosha_business_code', $value[0]);
            $model->setData('shosha_cmp', $value[1]);
            $model->setData('shosha_cmp_kana', $value[2]);
            $model->setData('shosha_dept', $value[3]);
            $model->setData('shosha_in_charge', $value[4]);
            $model->setData('shosha_address1', $value[5]);
            $model->setData('shosha_address2', $value[6]);
            $model->setData('shosha_address1_kana', $value[7]);
            $model->setData('shosha_address2_kana', $value[8]);
            $model->setData('shosha_phone', $value[9]);
            $model->setData('shosha_code', $this->_shoshaCode->convertCodeToValue($value[10]));
            $model->setData('shosha_first_code', $this->_storeCode->convertCodeToValue($value[11]));
            $model->setData('shosha_second_code', $this->_storeCode->convertCodeToValue($value[12]));
            $model->setData('shosha_commission', $value[13]);
            $model->setData('orm_rowid', $value[18]);
            $model->setData('shosha_dept_kana', $value[23]);
            $model->setData('shosha_in_charge_kana', $value[24]);
            $model->setData('shosha_postcode', $value[25]);
            $model->setData('block_orders', $value[28]);
            $model->setData('updated_at', $value[22]);
            $model->setData('created_at', $value[20]);
        } catch (\Exception $e){
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * Import CSV winner rule
     *
     * @param string $fieldName
     * @return array
     * @throws \Exception
     */
    public function doImport($fieldName)
    {
        $result = ['error' => 0, 'success' => 0];
        try {
            $adapter = $this->_fileTransfer->create();
            $adapter->addValidator('Extension', false, 'csv');
            $fileTransfer = $adapter->getFileInfo();
            $csvFile = $fileTransfer[$fieldName];
            $csvData = $this->_csvReader->getData($csvFile['tmp_name']);

            //skip header
            array_shift($csvData);

            foreach ($csvData as $key => $value) {
                $value = array_map('trim', $value);
                if ($this->insertRow($value)) {
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

            /*get import file data*/
            $csvData = $this->getImportFileData($fieldName);
            //skip header
            $header = array_shift($csvData);
            $headerError = $this->validateHeader($header);
            if (sizeof($headerError)) {
                return ['error' => $headerError];
            }

            $validateData = [];

            $row = 1;

            foreach ($csvData as $value) {

                $value = array_map('trim', $value);

                $validateRow = $this->_shoshaValidation->validateRowData($value);

                if( !empty($validateRow) ){
                    $validateData[ $row + 1 ] = $validateRow;
                }
                $row++;
            }

            if( !empty($validateData) ){
                return ['error' => $this->setErrorMessage($validateData)];
            } else {
                $messages['success'][] = __('Validate successful.');
            }

        } catch (\Exception $e) {
            $messages['error'][] = $e->getMessage();
        }

        return $messages;
    }

    /* set error message for reponse layout
     * $validate: array(), error message data
     * return array( (str)error_message, (str)error_message, ... )
     */
    public function setErrorMessage( $dataValidated )
    {
        $errorMsg = array();

        foreach ( $dataValidated as $row => $error )
        {
            if( is_array($error) )
            {
                $msg = __('Error at row %1: ',$row) . implode(" , ", $error );
                array_push( $errorMsg, $msg );
            }
        }

        return $errorMsg;
    }

    /**
     * Get upload file data
     *
     * @param $fiedName
     * @return array
     * @throws LocalizedException
     */
    public function getImportFileData($fiedName)
    {
        $adapter = $this->_fileTransfer->create();

        $adapter->addValidator('Extension', false, 'csv');

        if (!$adapter->isValid($fiedName)) {
            throw new LocalizedException(__('The file does not matched with predefined format!'));
        }

        return $this->uploadImportFileToTmpPath($fiedName);
    }

    /**
     * Upload import file to tmp folder
     *
     * @param $fiedName
     * @return mixed
     * @throws LocalizedException
     */
    public function uploadImportFileToTmpPath($fiedName)
    {
        $tmpPath = $this->getDestinationPath();

        $uploader = $this->_uploaderFactory->create(['fileId' => $fiedName])
            ->setAllowCreateFolders(true)
            ->setAllowedExtensions(['csv'])
            ->setAllowRenameFiles(true)
            ->addValidateCallback('validate', $this, 'validateFile');

        if (!$uploader->save($tmpPath)) {
            throw new LocalizedException(
                __('File cannot be saved to path: $1', $tmpPath)
            );
        }

        $uploadFileName = $uploader->getUploadedFileName();

        $this->removeBom($uploadFileName);

        return $this->_csvReader->getData($tmpPath. '/' .$uploadFileName);

    }

    /**
     * Get tmp folder to store import file
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getDestinationPath()
    {
        $varDirectory = $this->_directoryList->getPath(DirectoryList::VAR_DIR);

        $path = $varDirectory.'/'.'import_shosha';

        $fileObject = new File();

        if (!$fileObject->isDirectory($path)) {
            $fileObject->createDirectory($path,0777);
        }
        return $path;
    }

    /**
     * Remove Bom character for import file
     *
     * @param $fileName
     * @return $this
     */
    public function removeBom($fileName)
    {
        $sourceFile = 'import_shosha/'. $fileName;
        $relativePath = $this->_writeInterface->getRelativePath($sourceFile);
        $content = $this->_writeInterface->readFile($relativePath);
        if ($content !== false && substr($content, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
            $content = substr($content, 3);
            $this->_writeInterface->writeFile($relativePath, $content);
        }
        return $this;
    }
}