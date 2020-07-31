<?php
namespace Riki\Subscription\Command;

use Riki\Subscription\Model\Profile\Profile;
use Riki\Subscription\Model\Profile\ResourceModel\ProfileLink;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionCourseType;

class SubscriptionBeforeImport extends Command
{
    const FILE_NAME = 'file_name';

    const TYPE_PROFILE = 'type_profile';

    const MAIN_PROFILE = 'main_profile';

    const VERSION_PROFILE = 'version_profile';

    const IS_BEFORE_IMPORT = 'before_import';

    const IS_AFTER_IMPORT = 'after_import';
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_modelSubscriptionProfile;

    /**
     * @var \Magento\Framework\File\Csv $_readerCSV
     */
    protected $_readerCSV;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $_time
     */
    protected $_time;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee
     */
    protected $modelPaymentFee;
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $subscriptionCourse;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileLink
     */
    protected $profileLink;
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_varDirectory;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_fileSystem;

    protected $dateValidator;

    /**
     * @var array
     */
    protected $orderTypeMapping = [
        0 => 'call',
        1 => 'postcard',
        2 => 'call',
        3 => 'fax',
        4 => 'email',
        5 => 'online',
        6 => 'online'
    ];

    public $currentTypeProfile;

    public $isModeImport =false;

    public $isUpdateProfile;

    public $arrIdProfileUpdate = [];
    public $arrVersionDelete = [];

    protected $dataOldProfilesWithKey = [];
    protected $aDataSubscriptionCourseId = [];
    protected $aDataCustomerIds = [];
    protected $aDataPaymentMethodCode = [];

    /**
     * SubscriptionBeforeImport constructor.
     * @param \Riki\Subscription\Model\Profile\ProfileImport $modelSubscriptionProfile
     * @param \Magento\Framework\File\Csv $reader
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Riki\SubscriptionCourse\Model\Course $subscriptionCourse
     * @param \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee $modelPaymentFee
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     * @param \Riki\Subscription\Model\Profile\ProfileLink $profileLink
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \Riki\Subscription\Model\Profile\ProfileImport $modelSubscriptionProfile,
        \Magento\Framework\File\Csv $reader,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\SubscriptionCourse\Model\Course $subscriptionCourse,
        \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee $modelPaymentFee,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Riki\Subscription\Model\Profile\ProfileLink $profileLink,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Filesystem $filesystem,
        \Zend_Validate_Date $dateValidator
    )
    {
        $this->_modelSubscriptionProfile = $modelSubscriptionProfile;
        $this->_readerCSV = $reader;
        $this->_time = $timezoneInterface;
        $this->_customerFactory = $customerFactory;
        $this->modelPaymentFee = $modelPaymentFee;
        $this->subscriptionCourse = $subscriptionCourse;
        $this->resourceConnection = $resourceConnection;
        $this->profileLink = $profileLink;
        $this->_fileSystem = $filesystem;
        $this->_varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->dateValidator = $dateValidator;
        $this->dateValidator->setFormat('Y/m/d');
        parent::__construct();
    }

    /**
     * Set param name for CLI
     */
    protected function configure()
    {
        $options = [
            new InputArgument(
                self::FILE_NAME,
                InputArgument::REQUIRED,
                'Name of file to import'
            ),
            new InputArgument(
                self::TYPE_PROFILE,
                InputArgument::REQUIRED,
                'Check type profile'
            )
        ];
        $this->setName('riki:subscription:before-import')
            ->setDescription('A cli Import Subscription Profile')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * check colum exit on file csv
     *
     * @param $fieldName
     * @param $data
     * @param null $type
     * @return int|null
     */
    public function checkColumExit($fieldName, $data, $type = null)
    {
        if (isset($data[$fieldName]) && $data[$fieldName] != '') {
            $value = trim($data[$fieldName]);
            if ($type != null) {
                if ($type == 'datetime') {
                    //date yyyy/mm/dd h:i:s
                    $value = str_replace('/', '-', $value);
                    $re1 = '((?:2|1)\\d{3}(?:-|\\/)(?:(?:0[1-9])|(?:1[0-2]))(?:-|\\/)(?:(?:0[1-9])|(?:[1-2][0-9])|(?:3[0-1]))(?:T|\\s)(?:(?:[0-1][0-9])|(?:2[0-3])):(?:[0-5][0-9]):(?:[0-5][0-9]))';    # Time Stamp 1
                    if ($c = preg_match_all("/" . $re1 . "/is", $value, $matches)) {
                        return $this->_time->date($value)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                    } else {
                        return null;
                    }
                } else if ($type == 'date') {
                    //date yyyy/mm/dd
                    $value = str_replace('/', '-', $value);
                    if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $value)) {
                        return $this->_time->date($value)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                    } else {
                        return null;
                    }
                } else if ($type == 'int') {
                    if ($value >= 0) {
                        return $value;
                    } else {
                        return null;
                    }
                }
            } else {
                return $value;
            }
        }
        return null;
    }

    /**
     * check subscription code
     *
     * @param $code
     * @return mixed|null
     */
    public function getDataImportSubscriptionCourse($aCodes = [])
    {
        if (!empty($aCodes)) {

            $subscriptionCourse = $this->subscriptionCourse->getCollection()
                ->addFieldToSelect(['course_id', 'course_code', 'course_name','subscription_type'])
                ->addFieldToFilter('course_code', ['in' => $aCodes]);

            if ($subscriptionCourse) {
                return $subscriptionCourse->getData();
            }
        }
        return [];
    }


    /**
     * check subscription code
     *
     * @param $code
     * @return mixed|null
     */
    public function getDataImportCustomerId($aConsumerIds = [])
    {
        if (!empty($aConsumerIds)) {
            $customer = $this->_customerFactory->create()->getCollection()
                ->addAttributeToFilter('consumer_db_id', ['in' => $aConsumerIds]);

            if ($customer) {
                return $customer->getData();
            }
        }
        return [];
    }

    /**
     * check payment method
     *
     * @param $code
     * @return mixed|null
     */
    public function getDataImportPaymentMethod($aListPaymentCode = [])
    {
        if (!empty($aListPaymentCode)) {

            $connection = $this->resourceConnection->getConnection('sales');
            $select = $connection->select()->from(
                $connection->getTableName('payment_fee')
            )->where('payment_code IN (?)', $aListPaymentCode);

            $data = $connection->fetchAll($select);

            return $data;
        }
        return [];
    }

    /**
     * UpdateDeliveryDateMainProfile
     *
     * @param $dataVersionProfile
     */
    public function updateDeliveryDateMainProfile($dataVersionProfile,$dataOrderTimeUpdate=null){

        if(!isset($dataVersionProfile['main_profile']['profile_id'])){
            return;
        }

        $dataVersionProfileItem =  $dataVersionProfile['main_profile'];
        $nextDeliveryDate = isset($dataVersionProfileItem['next_delivery_date'])?$dataVersionProfileItem['next_delivery_date']:"";
        $nextOrderDate = isset($dataVersionProfileItem['next_order_date'])?$dataVersionProfileItem['next_order_date']:"";

        $frequencyUnit = isset($dataVersionProfileItem['frequency_unit'])?$dataVersionProfileItem['frequency_unit']:"";
        $frequencyInterval = isset($dataVersionProfileItem['frequency_interval'])?$dataVersionProfileItem['frequency_interval']:"";

        $orderTimes = $dataVersionProfileItem['order_times'];

        if($frequencyUnit == \Riki\SubscriptionFrequency\Model\Frequency::UNIT_MONTH){
            $frequencyUnit = 'months';
        }
        elseif($frequencyUnit == \Riki\SubscriptionFrequency\Model\Frequency::UNIT_WEEK){
            $frequencyUnit = 'weeks';
        }
        else{
            $frequencyUnit = $frequencyUnit.'s';
        }

        if($nextDeliveryDate && $nextOrderDate && $frequencyInterval && $frequencyUnit){
            $nextDeliveryDateUpdate = date('Y-m-d', strtotime($nextDeliveryDate . "-".$frequencyInterval." ".$frequencyUnit));
            $nextOrderDateUpdate = date('Y-m-d', strtotime($nextOrderDate . "-".$frequencyInterval." ".$frequencyUnit));

            //not set order time version = order time profile main
            if ($dataOrderTimeUpdate !=null){
                $orderTimesUpdate = $dataOrderTimeUpdate;
            }else{
                //for insert new
                $orderTimesUpdate = $orderTimes - 1;
            }

            $dataUpdate = [
                'next_delivery_date' => $nextDeliveryDateUpdate,
                'next_order_date' => $nextOrderDateUpdate,
                'order_times' => $orderTimesUpdate
            ];

            $this->updateRecord('subscription_profile',$dataVersionProfile['main_profile']['profile_id'],$dataUpdate);

            //update product main if main has product cart
            $this->updateDeliveryProductCartMain($dataVersionProfileItem);
        }
    }
    /**
     * InsertMultipleRecord
     *
     * @param $tableName
     * @param $dataMultipleImport
     */
    public function insertMultipleRecord($tableName, $dataMultipleImport)
    {
        $connection = $this->resourceConnection->getConnection('sales');

        $iInserted = $connection->insertMultiple($connection->getTableName($tableName), $dataMultipleImport);

        return $iInserted;
    }

    /**
     * UpdateRecord
     *
     * @param $tableName
     * @param $iProfileId
     * @param $dataUpdate
     */
    public function updateRecord($tableName,$iProfileId,$dataUpdate){
        $connection = $this->resourceConnection->getConnection('sales');
        return $connection->update($connection->getTableName($tableName),$dataUpdate,'profile_id = '.(int)$iProfileId);
    }
    
    /**
     * Update delivery product cart
     *
     * @param $dataProfileMain
     */
    public function updateDeliveryProductCartMain($dataProfileMain){

        $connection  = $this->resourceConnection->getConnection('sales');
        $tableUpdate = $connection->getTableName('subscription_profile_product_cart');

        if (isset($dataProfileMain['profile_id'])){

            $iProfileId = (int) $dataProfileMain['profile_id'];

            $frequencyUnit     = $dataProfileMain['frequency_unit'];
            $frequencyInterval = $dataProfileMain['frequency_interval'];

            if($frequencyUnit == \Riki\SubscriptionFrequency\Model\Frequency::UNIT_MONTH){
                $frequencyUnit = 'months';
            }
            elseif($frequencyUnit == \Riki\SubscriptionFrequency\Model\Frequency::UNIT_WEEK){
                $frequencyUnit = 'weeks';
            }
            else{
                $frequencyUnit = $frequencyUnit.'s';
            }

            if($frequencyInterval && $frequencyUnit){
                if ($frequencyUnit=='months'){
                    $sql = "UPDATE $tableUpdate SET delivery_date = DATE_SUB(delivery_date, INTERVAL $frequencyInterval MONTH) WHERE  profile_id = $iProfileId";
                }else {
                    $sql = "UPDATE $tableUpdate SET delivery_date = DATE_SUB(delivery_date, INTERVAL $frequencyInterval WEEK) WHERE  profile_id = $iProfileId";
                }

                $connection->query($sql);
            }
        }
    }



    /**
     * default value (month,week)
     *
     * @param $frequencyUnit
     * @return null
     */
    public function checkFrequencyUnit($frequencyUnit)
    {
        if ($frequencyUnit != null) {
            $arrUnit = array('month', 'week');
            $unit = strtolower($frequencyUnit);
            if (in_array($unit, $arrUnit)) {
                return $unit;
            }
        }
        return null;
    }

    /**
     * check status
     *
     * @param $status
     * @return null|int
     */
    public function checkStatus($status)
    {
        $arrStatus = array(
            Profile::STATUS_ENABLED,
            Profile::STATUS_DISABLED
        );
        if (in_array($status, $arrStatus)) {
            return $status;
        }
        return null;
    }

    /**
     * Clean main profile
     *
     * @param $connection
     * @param array $oldProfileIds
     */
    public function cleanMainProfile($connection,$oldProfileIds = [])
    {
        $profile    = $connection->getTableName('subscription_profile');
        if (is_array($oldProfileIds)&&count($oldProfileIds)>0){
            $profileIds = implode(' , ',$oldProfileIds);
            $sqlDeleteProfile = "DELETE $profile FROM $profile WHERE  $profile.old_profile_id IN ($profileIds)";
            $connection->query($sqlDeleteProfile);
        }
    }

    /**
     * Clean version profile
     *
     * @param $connection
     * @param array $oldProfileIds
     */
    public function cleanProfileRelatedVersion($connection,$oldProfileIds = [])
    {
        $version = $connection->getTableName('subscription_profile_version');
        $profile = $connection->getTableName('subscription_profile');
        if (is_array($oldProfileIds)&&count($oldProfileIds)>0){
            $profileIds = implode(' , ',$oldProfileIds);
            $sqlDeleteVersion = "DELETE $version FROM $version 
                                 INNER JOIN $profile ON $version.rollback_id = $profile.profile_id 
                                 WHERE  $profile.old_profile_id IN ($profileIds) ";
            $connection->query($sqlDeleteVersion);
        }
    }


    public function cleanProfileVersion($connection,$oldProfileIds = [])
    {
        $version = $connection->getTableName('subscription_profile_version');
        $profile = $connection->getTableName('subscription_profile');
        if (is_array($oldProfileIds)&&count($oldProfileIds)>0){
            $profileIds = implode(' , ',$oldProfileIds);
/*            $sql ="DELETE FROM $profile WHERE profile_id IN (
                        SELECT * FROM (
                            SELECT moved_to FROM $version ,$profile WHERE old_profile_id IN ($profileIds) AND $version.moved_to = $profile.profile_id GROUP BY moved_to 
                        ) AS p
                    )";*/

            $sql ="DELETE FROM $profile WHERE old_profile_id IN ($profileIds) AND `type` = 'version' ";
            $connection->query($sql);
        }
    }

    public function cleanDataUpdateVersionProfile($oldProfileIds = []){
        $connection = $this->resourceConnection->getConnection('sales');
        $this->cleanProfileVersion($connection,$oldProfileIds);
        //$this->cleanProfileRelatedVersion($connection,$oldProfileIds);
    }


    /**
     * Get data old profile
     *
     * @param array $oldProfileIds
     * @return array|bool
     */
    public function getOldProfileId($oldProfileIds = [])
    {
        if (!empty($oldProfileIds)) {
            $connection = $this->resourceConnection->getConnection('sales');

            $importMain    = \Riki\Subscription\Command\SubscriptionBeforeImport::MAIN_PROFILE;
            $importVersion = \Riki\Subscription\Command\SubscriptionBeforeImport::VERSION_PROFILE;

            //clean profile main or version
            if ($this->currentTypeProfile==$importMain && $this->isModeImport)
            {
                $this->cleanProfileVersion($connection,$oldProfileIds);
                $this->cleanProfileRelatedVersion($connection,$oldProfileIds);
                $this->cleanMainProfile($connection,$oldProfileIds);
            }

            $select = $connection->select()
                ->from([$connection->getTableName('subscription_profile')])
                ->where("old_profile_id IN (?)", $oldProfileIds);
            $data = $connection->fetchAll($select);
            return $data;
        }
        return false;
    }

    /**
     * Remove BOM from a file
     *
     * @param string $sourceFile
     * @return $this
     */
    public function removeBom($sourceFile)
    {
        $sourceFile = str_replace('var/', '', $sourceFile);
        $string = $this->_varDirectory->readFile($this->_varDirectory->getRelativePath($sourceFile));
        if ($string !== false && substr($string, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
            $string = substr($string, 3);
            $this->_varDirectory->writeFile($this->_varDirectory->getRelativePath($sourceFile), $string);
        }
        return $this;
    }

    /**
     * validate basic field
     *
     * @param $data
     * @param $dataImport
     * @return mixed
     */
    public function validateBasicField($data, $dataImport, $row)
    {

        //skip_next_delivery
        if ($dataImport['skip_next_delivery'] != '' && $dataImport['frequency_interval'] < 0) {
            $data['error'][] = "\tSkip next delivery is invalid at row " . $row;
        }

        //penalty_amount
        if ($dataImport['penalty_amount'] != '' && is_numeric($dataImport['penalty_amount']) &&  $dataImport['penalty_amount'] >= 0) {
            $dataImport['penalty_amount'] = $dataImport['penalty_amount'];
        } else {
            //SET penalty_amount = 0 IF IT IS NULL;
            if ($dataImport['penalty_amount'] ==''){
                $dataImport['penalty_amount'] = 0;
            }else{
                $data['error'][] = "\tPenalty amount is invalid at row" . $row;
            }
        }

        //check next_delivery_date
        if ($dataImport['next_delivery_date'] == null) {
            $data['error'][] = "\tNext delivery date is empty";
        } else if (!$this->dateValidator->isValid($dataImport['next_delivery_date'])) {
            $data['error'][] = "\tNext delivery date is invalid at row " . $row;
        }

        //check next_order_date
        if ($dataImport['next_order_date'] == null) {
            $data['error'][] = "\tNext order date is empty";
        } else if (!$this->dateValidator->isValid($dataImport['next_order_date'])) {
            $data['error'][] = "\tNext order date is invalid at row " . $row;
        }

        //check order time
        if ($dataImport['order_times'] == null) {
            $data['error'][] = "\tOrder times is empty at row " . $row;
        }

        //check sales count
        if ($dataImport['sales_count'] == null) {
            $dataImport['sales_count'] = 0;
        }

        //check sales value count
        if ($dataImport['sales_value_count'] == null) {
            $dataImport['sales_value_count'] = 0;
        }

        //check created_date
        if ($dataImport['created_date'] != '') {
            if (!$this->dateValidator->isValid($dataImport['created_date'])) {
                $data['error'][] = "\tPlan start date is invalid at row " . $row;
            }
        }

        //check updated_date
        if ($dataImport['updated_date'] == null) {
            $data['error'][] = "\tUpdated date is empty at row " . $row;
        } else if (!$this->dateValidator->isValid($dataImport['updated_date'])) {
            $data['error'][] = "\tUpdated date is invalid at row " . $row;
        }

        //check order time
        if ($dataImport['order_times'] == null || $dataImport['order_times'] < 0) {
            $data['error'][] = "\tOrder times is empty at row " . $row;
        }

        //check status
        if ($dataImport['status'] ===  null || $dataImport['status'] === '') {
            $data['error'][] = "\tStatus is empty";
        } else if (!in_array((int)$dataImport['status'], [0, 1, 9])) {
            $data['error'][] = "\tStatus is invalid at row " . $row;
        }

        //default shipping method
        $dataImport['shipping_condition'] = 'riki_shipping_riki_shipping';

        //check order type
        if ($dataImport['order_channel'] == 'invalid') {
            $data['error'][] = "\tOrder type is invalid at row " . $row;
            $dataImport['order_channel'] = '';
        }

        $data['dataImport'] = $dataImport;
        return $data;
    }

    /**
     * validate file import
     *
     * @param $dataImport
     * @param bool $allowValidate
     * @return array
     */
    public function validateData($dataImport, $typeProfile = \Riki\Subscription\Command\SubscriptionBeforeImport::MAIN_PROFILE,$row,$sMode = \Riki\Subscription\Command\SubscriptionBeforeImport::IS_BEFORE_IMPORT)
    {
        $data = array(
            'error' => null,
            'dataImport' => $dataImport
        );

/*        if ($typeProfile == \Riki\Subscription\Command\SubscriptionBeforeImport::MAIN_PROFILE && $sMode  == \Riki\Subscription\Command\SubscriptionBeforeImport::IS_BEFORE_IMPORT) {
            //check profile exit
            if (isset($this->dataOldProfilesWithKey[$dataImport['old_profile_id']])) {
                $data['error'][] = "\tSorry! Profile id already exit in database at row " . $row;
                return $data;
            }
        }*/

        if ($typeProfile == \Riki\Subscription\Command\SubscriptionBeforeImport::VERSION_PROFILE) {
            //check profile has now row
            if (!isset($this->dataOldProfilesWithKey[$dataImport['old_profile_id']])) {
                $data['error'][] = "\tSorry! Parent of version profile doest not exit in database at row " . $row;
                return $data;
            }

            if($sMode  == \Riki\Subscription\Command\SubscriptionBeforeImport::IS_BEFORE_IMPORT){
                //check profile has more than 1 row
                if (isset($this->dataOldProfilesWithKey[$dataImport['old_profile_id']]) && count($this->dataOldProfilesWithKey[$dataImport['old_profile_id']]) > 1) {
                    //validate profile exit and update profile

                       if (isset($this->dataOldProfilesWithKey[$dataImport['old_profile_id']])){
                            $mainProfile = array_shift($this->dataOldProfilesWithKey[$dataImport['old_profile_id']]);
                            if (is_array($mainProfile)&& count($mainProfile)>0){
                                $mainOrderTime    = $mainProfile['order_times'] ;
                                $versionOrderTime = $dataImport['order_times'];
                                $this->isUpdateProfile = true;
                                if ($mainOrderTime != $versionOrderTime){
                                    $data['error'][] = "\tOrder times of version profile must be less than main profile at row " . $row;
                                    return $data;
                                }else{
                                    //order time of version profile update
                                    $this->arrIdProfileUpdate[$mainProfile['profile_id']] = $versionOrderTime;
                                }
                            }

                            foreach ($this->dataOldProfilesWithKey[$dataImport['old_profile_id']] as $versionDelete){
                                $this->arrVersionDelete[$versionDelete['profile_id']] = $versionDelete['old_profile_id'];
                            }

                       }

/*                    $data['error'][] = "\tSorry! 2 profile id already exit in database at row " . $row;
                    return $data;*/
                }else{
                    //validate when profile not exit
                    if (isset($this->dataOldProfilesWithKey[$dataImport['old_profile_id']])){
                        $mainProfile = array_shift($this->dataOldProfilesWithKey[$dataImport['old_profile_id']]);
                        if (is_array($mainProfile)&& count($mainProfile)>0){
                            $mainOrderTime    = $mainProfile['order_times'] ;
                            $versionOrderTime = $dataImport['order_times']+1;
                            if ($mainOrderTime != $versionOrderTime){
                                $data['error'][] = "\tOrder times of version profile must be less than main profile at row " . $row;
                                return $data;
                            }
                        }
                    }
                }
            }

        }

        //validate record profile id
        if ($dataImport['old_profile_id'] == null) {
            $data['error'][] = "\tProfile id is not null at row " . $row;
        }

        //check course id
        if ($dataImport['course_id'] != '') {
            if (isset($this->aDataSubscriptionCourseId[$dataImport['course_id']])) {
                $courseCode = $dataImport['course_id'];
                $dataImport['course_id'] = $this->aDataSubscriptionCourseId[$courseCode]['course_id'];
                $dataImport['course_name'] = $this->aDataSubscriptionCourseId[$courseCode]['course_name'];

               // $dataCourse = $aDataSubscriptionCourseId[$dataImport['course_id']];
                $subscriptionType = $this->aDataSubscriptionCourseId[$courseCode]['subscription_type'];
                if ($subscriptionType==SubscriptionCourseType::TYPE_HANPUKAI){
                    $dataImport['hanpukai_qty'] = 1;
                }

            } else {
                $data['error'][] = "\tCourse id is invalid at row " . $row;
            }
        } else {
            $data['error'][] = "\tCourse id is invalid at row " . $row;
        }

        //customer consumer db
        if ($dataImport['customer_id'] != '') {
            $iConsumerDbId = $dataImport['customer_id'];
            if (isset($this->aDataCustomerIds[$iConsumerDbId])) {
                $dataImport['customer_id'] = $this->aDataCustomerIds[$iConsumerDbId];
            } else {
                $data['error'][] = "\tCustomer id is invalid at row " . $row;
            }
        } else {
            $data['error'][] = "\tCustomer id is empty at row " . $row;
        }

        //check frequency_unit
        if ($dataImport['frequency_unit'] != '') {
            $frequencyUnit = $this->checkFrequencyUnit($dataImport['frequency_unit']);
            if ($frequencyUnit != null) {
                $dataImport['frequency_unit'] = $frequencyUnit;
            } else {
                $data['error'][] = "\tFrequency Unit is invalid at row " . $row;
            }
        } else {
            $data['error'][] = "\tFrequency Unit is invalid at row " . $row;
        }

        //check frequency_interval
        if ($dataImport['frequency_interval'] != '' && $dataImport['frequency_interval'] >= 0) {
            $dataImport['frequency_interval'] = $dataImport['frequency_interval'];
        } else {
            $data['error'][] = "\tFrequency interval is invalid at row " . $row;
        }

        //check payment_method
        if ($dataImport['payment_method'] != '') {
            $paymentMethod = $dataImport['payment_method'];
            if (isset($this->aDataPaymentMethodCode[$paymentMethod])) {
                $dataImport['payment_method'] = $paymentMethod;
            } else {
                $data['error'][] = "\tPayment Method is empty " . $row;
            }
        } else {
            $data['error'][] = "\tPayment Method is invalid " . $row;
        }

        if(isset($dataImport['authorization_failed_times']) && (int)$dataImport['authorization_failed_times'] > 1){
            $dataImport['payment_method'] = '';
        }
        unset($dataImport['authorization_failed_times']);

        if ($typeProfile == \Riki\Subscription\Command\SubscriptionBeforeImport::VERSION_PROFILE) {
            $dataImport['type'] = 'version';
        }

        $data['dataImport'] = $dataImport;
        $data = $this->validateBasicField($data, $dataImport, $row);

        if(is_array($data['error']) && count ($data['error']) >0 ){
            $this->isCheckValidateSuccess = false;
        }

        return $data;
    }

    /**
     * convert data import
     *
     * @param $data
     * @return array
     */
    public function convertDataImport($data)
    {
        $dataImport = array();
        $dataImport['old_profile_id'] = isset($data['PROFILE_ID']) ? $data['PROFILE_ID'] : '';
        $dataImport['course_id'] = isset($data['COURSE_ID']) ? $data['COURSE_ID'] : '';
        $dataImport['course_name'] = null;
        $dataImport['hanpukai_qty'] = null;
        $dataImport['customer_id'] = isset($data['CUSTOMER_ID']) ? $data['CUSTOMER_ID'] : '';
        $dataImport['frequency_unit'] = isset($data['FREQUENCY_UNIT']) ? $data['FREQUENCY_UNIT'] : '';
        $dataImport['frequency_interval'] = isset($data['FREQUENCY_INTERVAL']) ? $data['FREQUENCY_INTERVAL'] : '';
        $dataImport['payment_method'] = isset($data['PAYMENT_METHOD']) ? $data['PAYMENT_METHOD'] : '';
        $dataImport['skip_next_delivery'] = isset($data['SKIP_NEXT_DELIVERY']) ? $data['SKIP_NEXT_DELIVERY'] : '';
        $dataImport['penalty_amount'] = isset($data['PENALTY_AMOUNT']) ? $data['PENALTY_AMOUNT'] : '';
        $dataImport['next_delivery_date'] = isset($data['NEXT_DELIVERY_DATE']) ? $data['NEXT_DELIVERY_DATE'] : '';
        $dataImport['next_order_date'] = isset($data['NEXT_ORDER_DATE']) ? $data['NEXT_ORDER_DATE'] : '';
        $dataImport['status'] = isset($data['STATUS']) ? $data['STATUS'] : '';

        $dataImport['disengagement_date'] = null;
        $dataImport['disengagement_reason'] = null;
        $dataImport['disengagement_user'] = null;

        if($dataImport['status'] == 9){ // disengaged profile
            $dataImport['status'] = 0;
            $dataImport['skip_next_delivery'] = 1;
            $dataImport['disengagement_date'] = $this->_time->date()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            $dataImport['disengagement_reason'] = 18; // default reason (Other)
            $dataImport['disengagement_user'] = 'admin'; // default
        }elseif($dataImport['status'] === 0 || $dataImport['status'] === '0'){
            $dataImport['status'] = 1;
        }
        $dataImport['order_times'] = isset($data['ORDER_TIMES']) ? $data['ORDER_TIMES'] : '';

        //we substract by 1 to make align order times between KSS and Magento
        if((int)$dataImport['order_times']){
            $dataImport['order_times'] = $dataImport['order_times'] - 1;
        }

        $dataImport['sales_count'] = isset($data['SALES_COUNT']) ? $data['SALES_COUNT'] : '';
        $dataImport['sales_value_count'] = isset($data['SALES_AMOUNT']) ? $data['SALES_AMOUNT'] : '';
        $dataImport['created_date'] = isset($data['PLAN_START_DATE']) ? $data['PLAN_START_DATE'] : $this->_time->date()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $dataImport['updated_date'] = isset($data['UPDATED_DATE']) ? $data['UPDATED_DATE'] : $this->_time->date()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $dataImport['store_id'] = 1;
        if (isset($data['PAYMENT_ORDER_ID'])) {
            $dataImport['trading_id'] = isset($data['PAYMENT_ORDER_ID']) ? $data['PAYMENT_ORDER_ID'] : '';
        }
        if (isset($data['CREDIT_ERRMAIL_TIMES'])) {
            $dataImport['authorization_failed_times'] = isset($data['CREDIT_ERRMAIL_TIMES']) ? $data['CREDIT_ERRMAIL_TIMES'] : '';
        }

        $dataImport['earn_point_on_order'] = 0;
        if (isset($data['ORDER_TYPE']) && ($data['ORDER_TYPE'] == 5 || $data['ORDER_TYPE'] == 6)) {
            $dataImport['earn_point_on_order'] = 1;
        }

        $dataImport['order_channel'] = '';
        if (isset($data['ORDER_TYPE']) && $data['ORDER_TYPE'] != '') {
            $orderType = $data['ORDER_TYPE'];
            if(array_key_exists($orderType,$this->orderTypeMapping) && is_numeric($orderType)){
                $dataImport['order_channel'] = $this->orderTypeMapping[$orderType];
            }
            else{
                $dataImport['order_channel'] = 'invalid';
            }
        }

        return $dataImport;
    }

    /**
     * @param $fileName
     * @return array
     * @throws \Exception
     */
    public function prepareData($fileName)
    {
        $dataResult = array();
        $dataRow = array();
        $this->removeBom($fileName);
        $dataCsv = $this->_readerCSV->getData($fileName);

        $aIdOldProfiles = [];
        $aSubscriptionCourseCodes = [];
        $aConsumerIds = [];
        $aListPaymentCode = [];

        foreach ($dataCsv as $key => $value) {
            if ($key == 0) continue;
            foreach ($value as $k => $v) {
                if (isset($dataCsv[0][$k])) {
                    $keyColum = str_replace('"', '', $dataCsv[0][$k]);
                    $dataRow[trim($keyColum)] = $v;
                }
            }

            //emulate virtual data, will remove
            /*$dataRow['COURSE_ID'] = 'H00036';
            $dataRow['CUSTOMER_ID'] = '2000000000048925';
            $dataRow['PENALTY_AMOUNT'] = '10';*/

            if ($dataRow['PROFILE_ID']) {
                $aIdOldProfiles[] = $dataRow['PROFILE_ID'];
            }

            if ($dataRow['COURSE_ID']) {
                $aSubscriptionCourseCodes[] = $dataRow['COURSE_ID'];
            }

            if ($dataRow['CUSTOMER_ID']) {
                $aConsumerIds[] = $dataRow['CUSTOMER_ID'];
            }

            if ($dataRow['PAYMENT_METHOD']) {
                $aListPaymentCode[] = $dataRow['PAYMENT_METHOD'];
            }

            $dataResult[] = $dataRow;
        }

        $aIdOldProfiles = array_unique($aIdOldProfiles);
        $aSubscriptionCourseCodes = array_unique($aSubscriptionCourseCodes);
        $aConsumerIds = array_unique($aConsumerIds);
        $aListPaymentCode = array_unique($aListPaymentCode);

        $dataOldProfiles = $this->getOldProfileId($aIdOldProfiles);

        foreach ($dataOldProfiles as $dataOldProfile) {
            $this->dataOldProfilesWithKey[$dataOldProfile['old_profile_id']][$dataOldProfile['profile_id']] = $dataOldProfile;
        }
        unset($dataOldProfiles);

        $aDataSubscriptionCourse = $this->getDataImportSubscriptionCourse($aSubscriptionCourseCodes);
        foreach ($aDataSubscriptionCourse as $dataSubscriptionCourse) {
            $this->aDataSubscriptionCourseId[$dataSubscriptionCourse['course_code']] = $dataSubscriptionCourse;
        }
        unset($aDataSubscriptionCourse);

        $aDataConsumerIds = $this->getDataImportCustomerId($aConsumerIds);
        foreach ($aDataConsumerIds as $dataConsumerId) {
            $this->aDataCustomerIds[$dataConsumerId['consumer_db_id']] = $dataConsumerId['entity_id'];
        }
        unset($aDataConsumerIds);

        $aDataPaymentMethod = $this->getDataImportPaymentMethod($aListPaymentCode);
        foreach ($aDataPaymentMethod as $dataPaymentMethod) {
            $this->aDataPaymentMethodCode[$dataPaymentMethod['payment_code']] = 1;
        }
        unset($aDataPaymentMethod);

        $dataForValidated = [
            'profile_id_data' => $this->dataOldProfilesWithKey,
            'subscription_course_data' => $this->aDataSubscriptionCourseId,
            'customer_data' => $this->aDataCustomerIds,
            'payment_method_code_data' => $this->aDataPaymentMethodCode
        ];

        return [
            $dataResult, $dataForValidated
        ];

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $flagTime = microtime(true);

        $fileName = $input->getArgument(self::FILE_NAME);

        $typeProfile = $input->getArgument(self::TYPE_PROFILE);
        $this->currentTypeProfile = $typeProfile;

        if ($fileName != "") {
            try {

                list($dataResult, $dataForValidated) = $this->prepareData($fileName);

                $row = 2;
                $totalError = 0;
                foreach ($dataResult as $data) {
                    // convert Data
                    $dataImport = $this->convertDataImport($data);

                    //validate data
                    $dataBeforeImport = $this->validateData($dataImport, $typeProfile, $row);
                    $errors = $dataBeforeImport['error'];

                    if (count($errors) > 0) {
                        $output->writeln("\n------------------------------------------------------------------------------------");
                        $output->writeln("[Row $row] Validate error!\n");
                        $output->writeln($errors);
                        $totalError++;
                    } else {
                        $output->writeln("--------------------------------------------------------------------------------------");
                        $output->writeln("[Row $row] Validate successfully!\n");
                    }
                    $row++;
                }

                if ($totalError == 0) {
                    $output->writeln("===========================================================================================\n");
                    $output->writeln("Validate file successfully \n");
                    $output->writeln("===========================================================================================");
                } else {
                    $output->writeln("===========================================================================================\n");
                    $output->writeln("Validate error \n");
                    $output->writeln("===========================================================================================");
                }

            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
                exit();
            }
        }

        $timeElapsedSecs = microtime(true) - $flagTime;
        echo "Script run time :" . ($timeElapsedSecs) . "\n";
    }


}
