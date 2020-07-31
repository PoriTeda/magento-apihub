<?php
namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

class InquiryHelper extends \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ExportHelper
{
    const MORE_COLUMN_ENQUIRY = ['inquiry.customer_consumer_db_id'];
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\QuestionnaireCollectionFactory
     */
    protected $_enquiryCollectionFactory;
    /**
     * @var \Riki\Customer\Model\ResourceModel\CategoryEnquiry
     */
    protected $_categoryEnquiry;
    /**
     * @var \Riki\Customer\Model\EnquiryHeader
     */
    protected $_enquiryHeader;
    /*list columns which data type is datetime or timestamp, table riki_customer_enquiry_header*/
    protected $_inquiryDateTimeColumns;

    /**
     * InquiryHelper constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param GlobalHelper\SftpExportHelper $sftpHelper
     * @param GlobalHelper\FileExportHelper $fileHelper
     * @param GlobalHelper\ConfigExportHelper $configHelper
     * @param GlobalHelper\EmailExportHelper $emailHelper
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     * @param \Riki\Customer\Model\ResourceModel\EnquiryHeader\CollectionFactory $enquiryCollection
     * @param \Riki\Customer\Model\CategoryEnquiry $categoryEnquiry
     * @param \Riki\Customer\Model\EnquiryHeader $enquiryHeader
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\SftpExportHelper $sftpHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper $configHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\EmailExportHelper $emailHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper,
        \Riki\Customer\Model\ResourceModel\EnquiryHeader\CollectionFactory $enquiryCollection,
        \Riki\Customer\Model\CategoryEnquiry $categoryEnquiry,
        \Riki\Customer\Model\EnquiryHeader $enquiryHeader
    ) {
        parent::__construct($context, $dateTime, $timezone, $resourceConfig, $sftpHelper, $fileHelper, $configHelper, $emailHelper, $dateTimeColumnsHelper, $connectionHelper);
        $this->_customerFactory = $customerFactory;
        $this->_enquiryCollectionFactory = $enquiryCollection;
        $this->_categoryEnquiry = $categoryEnquiry;
        $this->_enquiryHeader = $enquiryHeader;
    }

    /**
     * export process
     */
    public function exportProcess()
    {
        /*export process*/
        $this->exportData();

        /* set last time to run */
        $this->setLastRunToCron();

        /*move export folder to ftp*/
        $this->_sftpHelper->moveFileToFtp($this->_pathTmp, $this->_path, $this->getSFTPPathExport(), $this->getReportPathExport());

        /*send email notify*/
        $this->sentNotificationEmail();

        /*delete lock file*/
        $this->deleteLockFile();
    }

    /**
     * create new export file for local path
     */
    public function exportData()
    {
        $arrayHeaderData = $arrHeaderInquiryCateData = $arrkeyQuery = [];
        $resource = $this->_enquiryHeader->getResource();
        $mainTableEnquiry = $resource->getMainTable();
        $readConnection = $resource->getConnection();
        //get all column name table
        $arrayHeader = array_keys($readConnection->describeTable($mainTableEnquiry));
        //add prefix column name
        foreach($arrayHeader as $key => $columnName){
            $arrayHeaderData[$key] = 'inquiry.' . $columnName;
        }
        $arrHeaderInquiryCate = array_keys($readConnection->describeTable('enquiry_category'));
        foreach($arrHeaderInquiryCate as $key => $columnName){
            $arrHeaderInquiryCateData[$key] = 'inquiry.enquiry_category_' . $columnName;
        }
        $arrayHeaderAdded = array_merge($arrayHeaderData,$arrHeaderInquiryCateData,self::MORE_COLUMN_ENQUIRY);

        $arrkeyQuery = array_merge($arrayHeader,$arrHeaderInquiryCate);

        /*add header to export data*/
        $arrayExport[] = $arrayHeaderAdded;

        /*get last time that this cron is run*/
        $lastRunToCron = $this->getLastRunToCronDB();

        /** @var \Riki\Customer\Model\ResourceModel\EnquiryHeader\Collection $collection */
        $collection = $this->_enquiryCollectionFactory->create();

        $collection->join(
            ['enquiry_category'],
            'main_table.enquiry_category_id = enquiry_category.entity_id',
            '*'
        );

        if($lastRunToCron){
            $collection->addFieldToFilter('enquiry_updated_datetime',array(
                'gteq' => $lastRunToCron
            ));
        }

        if ($collection->getSize()) {
            foreach ($collection as $enquiry) {
                /*export row data*/
                $data = [];
                foreach($arrkeyQuery as $columnName){
                    /*column value*/
                    $columnValue = $enquiry->getData($columnName);

                    if (!empty($columnValue) && $this->isDateTimeColumns($columnName)) {
                        /*convert to config timezone for datetime column*/
                        $data[] = $this->convertToConfigTimezone($columnValue);
                    } else {
                        $data[] = $columnValue;
                    }
                }

                /*push customer consumer db id to export row data*/
                $data[] = $this->getCustomerConsumerDbId($enquiry->getData('customer_id'));

                /*push export row data to export data*/
                array_push($arrayExport, $data);
            }
        }

        /*get export date via config timezone*/
        $exportDate = $this->_timezone->date()->format('YmdHis');

        /*export file name*/
        $exportFile = 'inquiry-'.$exportDate.'.csv';

        /*create export file to local path*/
        $this->createLocalFile([
            $exportFile => $arrayExport
        ]);
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table riki_customer_enquiry_header
     * @return mixed
     */
    public function getInquiryDateTimeColumns()
    {
        if (empty($this->_inquiryDateTimeColumns)) {
            $this->_inquiryDateTimeColumns = $this->_dateTimeColumnsHelper->getInquiryDateTimeColumns();
        }
        return $this->_inquiryDateTimeColumns;
    }

    /**
     * check columns with data type is datetime or timestamp
     *
     * @param $cl
     * @return bool
     */
    public function isDateTimeColumns($cl)
    {
        $inquiryDateTimeColumns = $this->getInquiryDateTimeColumns();

        if (in_array($cl, $inquiryDateTimeColumns)) {
            return true;
        }

        return false;
    }

    /**
     * Get customer consumer db id
     *
     * @param $customerId
     * @return bool
     */
    public function getCustomerConsumerDbId($customerId)
    {
        try {
            /** @var \Magento\Customer\Model\CustomerFactory $customer */
            $customer = $this->_customerFactory->create()->load($customerId);
            if($customer->getId()){
                return $customer->getData('consumer_db_id');
            }
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }

        return false;
    }

    /**
     * Get lock file
     *      this lock is used to tracking that system have same process is running
     *
     * @return string
     */
    public function getLockFile()
    {
        return $this->_path . DS . '.lock';
    }

    /**
     * Delete lock file
     */
    public function deleteLockFile()
    {
        $this->_fileHelper->deleteFile($this->getLockFile());
    }

    /**
     * Set default config before export
     * @param $defaultLocalPath
     * @param $configLocalPath
     * @param $configSftpPath
     * @param $configReportPath
     * @param $configLastTimeRun
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initExport(
        $defaultLocalPath,
        $configLocalPath,
        $configSftpPath,
        $configReportPath,
        $configLastTimeRun
    )
    {
        $initExport = parent::initExport(
            $defaultLocalPath,
            $configLocalPath,
            $configSftpPath,
            $configReportPath,
            $configLastTimeRun
        );
        if ($initExport) {
            /*tmp file to ensure that system do not run same mulit process at the same time*/
            $lockFile = $this->getLockFile();
            if ($this->_fileHelper->isExists($lockFile)) {
                $this->_logger->info('Please wait, system have a same process is running and haven’t finish yet.');
                throw new \Magento\Framework\Exception\LocalizedException(
                __('Please wait, system have a same process is running and haven’t finish yet.')
                );
            } else {
                $this->_fileHelper->createFile($lockFile);
            }
        }
        return $initExport;
    }
}
