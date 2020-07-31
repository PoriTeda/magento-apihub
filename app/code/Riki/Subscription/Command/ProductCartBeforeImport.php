<?php
namespace Riki\Subscription\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Riki\Subscription\Model\Profile\ResourceModel\Profile\Collection as ProfileCollection;

class ProductCartBeforeImport extends Command
{
    const FILE_NAME = 'file_name';

    const TYPE_PROFILE_CART = 'type_profile_cart';

    const MAIN_PROFILE_CART = 'main_profile_cart';

    const VERSION_PROFILE_CART = 'version_profile_cart';

    /**
     * @var \Magento\Framework\File\Csv $_readerCSV
     */
    protected $_readerCSV;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $_time
     */
    protected $_time;

    /**
     * @var \Riki\Subscription\Model\ProductCart\ProductCart
     */
    protected $productCart;
    /**
     * @var \Magento\Customer\Api\Data\AddressInterfaceFactory
     */

    protected $resourceConnection;

    /**
     * @var \Magento\Customer\Model\Address
     */
    protected $modelAddress;
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_varDirectory;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_fileSystem;
    /**
     * @var ProfileCollection
     */
    protected $resourceProfileConnection;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    protected $aIdOldProfiles;
    protected $aProductIds;
    protected $aCustomerIds;

    protected $dataOldProfile;
    protected $aCustomerAddressData;
    protected $aCustomerAddressLegacyData;
    protected $hanpukaiFixed;
    protected $hanpukaiSequence;
    protected $aProductData;

    protected $timeSlotFrom = [];
    protected $timeSlotTo   = [];


    /**
     * ProductCartBeforeImport constructor.
     * @param \Magento\Framework\File\Csv $reader
     * @param \Riki\Subscription\Model\ProductCart\ProductCart $productCart
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     * @param \Magento\Customer\Model\Address $modelAddress
     * @param \Magento\Framework\Filesystem $filesystem
     * @param ProfileCollection $profileCollection
     */
    public function __construct(
        \Magento\Framework\File\Csv $reader,
        \Riki\Subscription\Model\ProductCart\ProductCart $productCart,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Customer\Model\Address $modelAddress,
        \Magento\Framework\Filesystem $filesystem,
        ProfileCollection $profileCollection,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_readerCSV = $reader;
        $this->_time = $timezoneInterface;
        $this->productCart = $productCart;
        $this->resourceConnection = $resourceConnection;
        $this->modelAddress = $modelAddress;
        $this->_fileSystem = $filesystem;
        $this->_varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->resourceProfileConnection = $profileCollection;
        $this->_eavAttribute = $eavAttribute;
        $this->logger = $logger;
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
                self::TYPE_PROFILE_CART,
                InputArgument::REQUIRED,
                'Check type profile cart'
            )
        ];
        $this->setName('riki:product-cart:before-import')
            ->setDescription('A cli Import Subscription Product Cart')
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
     * GetProductBySku
     *
     * @param $sku
     * @param $aDataProduct
     * @return null
     */
    public function getProductBySku($sku, $aDataProduct)
    {
        if ($sku != '') {
            if (isset($aDataProduct[$sku])) {
                return $aDataProduct[$sku];
            }
        }
        return null;
    }

    /**
     * GetCustomerAddressData
     *
     * @param $aCustomerId
     * @return array
     */
    public function getCustomerAddressData()
    {
        $iRikiTypeAddressAttrId = $this->_eavAttribute->getIdByCode('customer_address', 'riki_type_address');

        if ($iRikiTypeAddressAttrId) {
            $connection = $this->resourceConnection->getConnection();
            $selectCustomerAddress = $connection->select()
                ->from([$connection->getTableName('customer_address_entity')])
                ->join(['caev' => $connection->getTableName('customer_address_entity_varchar')],
                    'caev.entity_id = customer_address_entity.entity_id'
                )
                ->where("caev.attribute_id = ?", $iRikiTypeAddressAttrId)
                ->where("customer_address_entity.parent_id IN (?)", $this->aCustomerIds)
                ->where("caev.value IN (?)", ['home', 'company']);

            $dataCustomerAddressSelect = $connection->fetchAll($selectCustomerAddress);

            $dataCustomerAddress = [];
            foreach ($dataCustomerAddressSelect as $dataCustomerAddressItem) {
                $dataCustomerAddress[$dataCustomerAddressItem['parent_id']][$dataCustomerAddressItem['value']] = $dataCustomerAddressItem['entity_id'];
            }

            return $dataCustomerAddress;
        }

        return [];
    }

    /**
     * GetCustomerAddressLegacyData
     *
     * @param $aShippingIds
     * @return array
     */
    public function getCustomerAddressLegacyData($aShippingIds)
    {

        if (!empty($aShippingIds)) {
            $connection = $this->resourceConnection->getConnection();
            $selectCustomerAddressLegacy = $connection->select()
                ->from([$connection->getTableName('customer_address_entity')])
                ->where("customer_address_entity.consumer_db_address_id IN (?)", $aShippingIds);

            $aCustomerAddressLegacySelect = $connection->fetchAll($selectCustomerAddressLegacy);

            $aCustomerAddressLegacy = [];
            foreach ($aCustomerAddressLegacySelect as $customerAddress) {
                $aCustomerAddressLegacy[$customerAddress['parent_id']][$customerAddress['consumer_db_address_id']] = $customerAddress['entity_id'];
            }

            return $aCustomerAddressLegacy;
        }
        return [];
    }

    /**
     * GetListProductBySku
     *
     * @param $aListSku
     * @return array
     */
    public function getListProductBySku()
    {
        $connection = $this->resourceConnection->getConnection();
        $selectProduct = $connection->select()
            ->from([$connection->getTableName('catalog_product_entity')])
            ->where("catalog_product_entity.sku IN (?)", $this->aProductIds);

        $aListProductSelect = $connection->fetchAll($selectProduct);
        $aListProduct = [];
        foreach ($aListProductSelect as $product) {
            $aListProduct[$product['sku']] = $product;
        }

        return $aListProduct;
    }

    /**
     * @param $timeSlot
     * @return mixed|null
     */
    public function getDeliveryTimeSlot($timeSlot)
    {
        if (is_array($timeSlot) && count($timeSlot) == 2) {
            $from = trim($timeSlot[0]);
            $to = trim($timeSlot[1]);
            if ($from != '' && $to != '') {
                $connection = $this->resourceConnection->getConnection();
                $select = $connection->select()
                    ->from(['riki_timeslots' => $connection->getTableName('riki_timeslots')])
                    ->where("riki_timeslots.from ='$from' AND riki_timeslots.to ='$to' ");
                $data = $connection->fetchRow($select);
                if (isset($data['id']) && $data['id'] != '') {
                    return $data['id'];
                }
            }
        }
        return null;
    }

    /**
     * GetAllDeliveryTimeSlot
     *
     * @return array
     */
    public function getAllDeliveryTimeSlot()
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()->from(['riki_timeslots' => $connection->getTableName('riki_timeslots')]);

        $aTimeSlotSelect = $connection->fetchAll($select);

        $aTimeSlotData = [];
        $aTimeSlotFrom = [];
        $aTimeSlotTo   = [];
        foreach ($aTimeSlotSelect as $aTimeSlot) {
            $aTimeSlotFrom[$aTimeSlot['id']] =  strtotime(trim($aTimeSlot['from']));
            $aTimeSlotTo[$aTimeSlot['id']]   =  strtotime(trim($aTimeSlot['to']));
            $aTimeSlotData[$aTimeSlot['from'] . "-" . $aTimeSlot['to']] = $aTimeSlot['id'];
        }

        $from  = $aTimeSlotFrom;
        $to    = $aTimeSlotTo;



        //convert time from
        ksort($aTimeSlotFrom);
        foreach ($aTimeSlotFrom as $valueFrom ){
            $keyFrom =  array_search($valueFrom, $from);
            $this->timeSlotFrom[$keyFrom] = $valueFrom;
        }

        //convert time to
        ksort($aTimeSlotTo);
        foreach ($aTimeSlotTo as $valueTo ){
            $keyTo =  array_search($valueTo, $to);
            $this->timeSlotTo[$keyTo] = $valueTo;
        }

        return $aTimeSlotData;
    }

    /**
     * Get all id gift warping
     *
     * @return array
     */
    public function getGiftwrapping()
    {
        $connection = $this->resourceConnection->getConnection();
        $select     = $connection->select()->from(['magento_giftwrapping' => $connection->getTableName('magento_giftwrapping')]);
        $allData = $connection->fetchAll($select);

        $arrData = [];
        foreach ($allData as $item) {
            $arrData[$item['gift_code']] = $item['wrapping_id'];
        }

        return $arrData;
    }




    /**
     * ValidateProductId
     *
     * @param $data
     * @param $aDataProduct
     * @return mixed
     */
    public function validateProductId($data)
    {
        $dataImport = $data['dataImport'];
        if ($dataImport['product_id'] != '') {
            $product = $this->getProductBySku($dataImport['product_id'], $this->aProductData);
            if (is_array($product) && isset($product['entity_id']) && $product['entity_id'] != null) {
                $dataImport['product_id'] = $product['entity_id'];
                $dataImport['product_type'] = $product['type_id'];
            } else {
                $data['error'][] = "\tProduct id is invalid";
            }
        } else {
            $data['error'][] = "\tProduct id is invalid";
        }

        $data['dataImport'] = $dataImport;
        return $data;
    }

    /**
     * @param $timeSlotInput
     * @return int|string
     */
    public function convertTimeSlot($timeSlotInput)
    {

        foreach ($this->timeSlotTo as $key=> $to)
        {
            if(strtotime($timeSlotInput[0]) <= $to)
            {
                return $key;
            }
        }

        //not selected
        return -1;
    }


    /**
     * Get time slot min < 12:00
     * @param $timeSlotInput
     * @return int|string
     */
    public function getTimeSlotFromMin($timeSlotInput)
    {
        foreach ($this->timeSlotFrom as $key=> $from)
        {
            if($from< strtotime('12:00'))
            {
                return $key;
            }
        }

        //not selected
        return -1;
    }

    /**
     * Get max time slot . time slot >=22
     *
     * @return int|mixed
     */
    public function getTimeSlotFromMax()
    {
        $keyMax = array_search(strtotime('19:00'),$this->timeSlotFrom);
        if(isset($this->timeSlotFrom[$keyMax])){
            return $keyMax;
        }
        //not selected
        return -1;
    }
    /**
     * ValidateTimeSlot
     *
     * @param $data
     * @param $aTimeSlotIds
     * @return mixed
     */
    public function validateTimeSlot($data, $aTimeSlotIds)
    {
        $dataImport = $data['dataImport'];

        /**
         * Ticket 9412
         */
        if (isset($dataImport['delivery_time_slot']) && $dataImport['delivery_time_slot'] !=null )
        {
            $timeSlotInput = explode('-',$dataImport['delivery_time_slot']);
            if (count($timeSlotInput)==2 ){
                //get time slot <12:00
                if(strtotime(trim($timeSlotInput[0])) < strtotime('12:00') )
                {
                    $dataImport['delivery_time_slot'] = $this->getTimeSlotFromMin($timeSlotInput);
                }else if(strtotime(trim($timeSlotInput[0])) >= strtotime('22:00') ){
                    $dataImport['delivery_time_slot'] = $this->getTimeSlotFromMax();
                }else{
                    $dataImport['delivery_time_slot'] = $this->convertTimeSlot($timeSlotInput);
                }
            }else{
                $dataImport['delivery_time_slot'] = '-1';
            }
        }else{
            $dataImport['delivery_time_slot'] = '-1';
        }


/*        if ($dataImport['delivery_time_slot'] != '') {
            $value = explode('-', $dataImport['delivery_time_slot']);
            if (is_array($value) && count($value) == 2) {
                $dataImport['delivery_time_slot'] = trim($value[0]) .'-'.trim($value[1]);
                if (isset($aTimeSlotIds[$dataImport['delivery_time_slot']])) {
                    $dataImport['delivery_time_slot'] = $aTimeSlotIds[$dataImport['delivery_time_slot']];
                } else {
                    $data['error'][] = "\tDelivery time slot (" . $dataImport['delivery_time_slot'] . ') not found on database.Please insert value in master data';
                }
            } else {
                $data['error'][] = "\tDelivery time slot is invalid";
            }
        } else {
            //unselect option time slot
            $dataImport['delivery_time_slot'] = '-1';
        }*/
        $data['dataImport'] = $dataImport;
        return $data;
    }

    /**
     * validate unit
     *
     * @param $data
     * @return mixed
     */
    public function validateUnit($data)
    {
        $dataImport = $data['dataImport'];
        if ($dataImport['unit'] == '' || $dataImport['unit'] < 0) {
            $data['error'][] = 'Unit is invalid';
        } else {
            if ($dataImport['unit'] != 1 && $dataImport['unit'] != 3) {
                $data['error'][] = "\tUnit is invalid";
            } else if ($dataImport['unit'] == 1) {
                $dataImport['unit'] = 'CS';
                $dataImport['unit_case'] = 'CS';
                if ($dataImport['unit_qty'] != '' && $dataImport['unit_qty'] >= 0 && $dataImport['qty'] != '' &&  $dataImport['qty']>0 ) {
                    $dataImport['qty'] = (int)$dataImport['qty'] *  (int)$dataImport['unit_qty'];
                }
            } else if ($dataImport['unit'] == 3) {
                $dataImport['unit'] = 'EA';
                $dataImport['unit_case'] = 'EA';
            }
        }
        $data['dataImport'] = $dataImport;
        return $data;
    }

    /**
     * Get address id by customer
     *
     * @param $customerId
     * @param $consumerAddressId
     *
     * @return null
     */
    public function getAddressByCustomerID($customerId, $consumerAddressId)
    {
        if (isset($this->aCustomerAddressLegacyData[$customerId]) && isset($this->aCustomerAddressLegacyData[$customerId][$consumerAddressId])) {
            return $this->aCustomerAddressLegacyData[$customerId][$consumerAddressId];
        }
        return null;
    }

    /**
     * Get address by address type
     *
     * @param $customer_id
     * @param $type
     * @return mixed|null
     */
    public function getAddressIdByAddressType($customer_id, $type, $aCustomerAddressData = [])
    {
        if ( isset($aCustomerAddressData[$customer_id]) && isset($aCustomerAddressData[$customer_id][$type]) ) {
            return $aCustomerAddressData[$customer_id][$type];
        }
        return null;
    }

    /**
     * @param $data
     * @param array $aCustomerAddressData
     * @param array $aCustomerAddressLegacyData
     * @return mixed
     */
    public function checkShippingAndBillingAddress($data)
    {
        $dataImport = $data['dataImport'];
        $shippingId = $dataImport['shipping_address_id'];
        $billingId = $dataImport['billing_address_id'];

        $homeAddressId = $this->getAddressIdByAddressType($dataImport['customer_id'], 'home', $this->aCustomerAddressData);
        if ($homeAddressId == null) {
            $data['error'][] = "\tThe customer does not have home address.";
        }

        $companyAddressId = $this->getAddressIdByAddressType($dataImport['customer_id'], 'company', $this->aCustomerAddressData);

        /**
         * if shipping address is 0 , billing address will also be 0
         * home address
         */

        if ($shippingId == 0) {
            $dataImport['shipping_address_id'] = $homeAddressId;

        }
        if ($billingId == 0) {
            $dataImport['billing_address_id'] = $homeAddressId;
        }

        /**
         * if shipping address is 99999999,billing address will also be 99999999
         * company address
         */

        if ($shippingId == 99999999) {
            if ($companyAddressId == null) {
                $data['error'][] = "\tShipping address is invalid because the customer does not have company address.";
            } else {
                $dataImport['shipping_address_id'] = $companyAddressId;
            }
        }
        if ($billingId == 99999999) {
            if ($companyAddressId == null) {
                $data['error'][] = "\tBilling address is invalid because the customer does not have company address.";
            } else {
                $dataImport['billing_address_id'] = $companyAddressId;
            }
        }
        /**
         * if shipping address is other value than 0 or 99999999 ,billing address will be 0
         * other address
         */
        if ($shippingId != 0 && $shippingId != 99999999) {
            $dataImport['billing_address_id'] = $homeAddressId;
            $addressOtherId = $this->getAddressByCustomerID($dataImport['customer_id'], $shippingId);
            if ($addressOtherId) {
                $dataImport['shipping_address_id'] = $addressOtherId;
            } else {
                $data['error'][] = "\tShipping address $shippingId does not exist.";
            }
        }

        $data['dataImport'] = $dataImport;
        return $data;
    }

    /**
     * Convert delivery date for main profile
     *
     * @param $deliveryDateImport
     * @param $dataVersionProfileItem
     * @return false|string
     */
    public function convertDeliveryData($deliveryDateImport,$dataVersionProfileItem){

        //check profile version
        if ($dataVersionProfileItem['type'] !='version'){

            $frequencyUnit     = $dataVersionProfileItem['frequency_unit'];
            $frequencyInterval = $dataVersionProfileItem['frequency_interval'];

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
                return date('Y-m-d', strtotime($deliveryDateImport . "-".$frequencyInterval." ".$frequencyUnit));
            }
        }

        return $deliveryDateImport;
    }


    /**
     * CheckProfile
     *
     * @param $data
     * @param $dataProfiles
     * @param $typeProfile
     * @return mixed
     */
    public function checkProfile($data ,$typeProfile)
    {
        $dataImport = $data['dataImport'];

        if ($dataImport['profile_id'] != '' && $dataImport['order_times'] != null && !empty($this->dataOldProfile)) {
            $iProfileId = $dataImport['profile_id'];

            if(!isset($this->dataOldProfile[$iProfileId])){
                $data['error'][] = "\tProfile id ".$iProfileId." does not exist";
                return $data;
            }
            else{
                $proFileId = reset($this->dataOldProfile[$iProfileId]);
            }

            if($typeProfile == \Riki\Subscription\Command\ProductCartBeforeImport::MAIN_PROFILE_CART){

                if(isset($proFileId['has_version_profile']) && $proFileId['has_version_profile']){
                    if($proFileId['order_times'] + 1 != ($dataImport['order_times']) && $proFileId['order_times'] != ($dataImport['order_times'])){
                        $data['error'][] = "\tOrder times does not match with subscription profile";
                    }
                }
                else{
                    if($proFileId['order_times'] != ($dataImport['order_times'])){
                        $data['error'][] = "\tOrder times does not match with subscription profile";
                    }
                }
            }
            else{
                if($proFileId['order_times'] != ($dataImport['order_times'])){
                    $data['error'][] = "\tOrder times does not match with subscription profile";
                }
            }


            if (isset($proFileId['profile_id']) && $proFileId['profile_id'] != null) {
                $dataImport['profile_id']  = $proFileId['profile_id'];
                $dataImport['order_times'] = $proFileId['order_times'];

                if ($proFileId['customer_id'] != null) {
                    $dataImport['customer_id'] = $proFileId['customer_id'];

                    if ($dataImport['delivery_date'] !='' && count($this->dataOldProfile[$iProfileId])>1){
                        $dataImport['delivery_date'] = $this->convertDeliveryData($dataImport['delivery_date'],$proFileId);
                    }
                } else {
                    $data['error'][] = "\tCustomer doesn't exit";
                }
            } else {
                $data['error'][] = "\tProfile id is invalid";
            }
        } else {
            $data['error'][] = "\tProfile id is invalid or empty";
        }

        $data['dataImport'] = $dataImport;
        return $data;
    }

    /**
     * Check is spot data
     *
     * @param $data
     * @return mixed
     */
    public function checkIsSpot($data)
    {
        $dataImport = $data['dataImport'];
        if (isset($dataImport['is_spot']) && $dataImport['is_spot'] !=null ){
            if ($dataImport['is_spot'] !=0 && $dataImport['is_spot'] != 1){
                $data['error'][] = "\tCombined shipping type is invalid. Only allow value 0 or 1";
            }
        }else {
            $dataImport['is_spot'] = 0;
        }

        $data['dataImport'] = $dataImport;
        return $data;
    }

    public function updateHanpukaiQty($dataUpdate,$iProfileId)
    {
        $connection = $this->resourceConnection->getConnection('sales');
        return $connection->update($connection->getTableName('subscription_profile'),$dataUpdate,'profile_id = '.(int)$iProfileId);
    }

    public function validateHanpukaiQty($data,$arrProduct,$aDataForValidated)
    {
        $arrCourse        = $aDataForValidated['aCourseCodeIds'];
        $dataImport       = $data['dataImport'];

        if (isset($dataImport['profile_id'])  ){
            $profileId = $dataImport['profile_id'];

            if (isset($arrCourse[$profileId]))
            {
                $courseId  = $arrCourse[$profileId];
                $qtyDataImport = $dataImport['qty'];
                $productId     = $dataImport['product_id'];
                if (isset($this->hanpukaiFixed[$courseId]) && isset($this->hanpukaiFixed[$courseId][$productId])){
                    //hanpukai fixed
                    $qtyHanpukaiFixed = $this->hanpukaiFixed[$courseId][$productId]['qty'];
                    if(($qtyDataImport % $qtyHanpukaiFixed)!=0){
                        $data['error'][] = "\tQuantity hanpukai fixed is invalid";
                    }
                }else if (isset($this->hanpukaiSequence[$courseId]) && isset($this->hanpukaiSequence[$courseId][$productId])){
                    //hanpukai sequence
                    if ($this->hanpukaiSequence[$courseId][$productId]['delivery_number'] == $dataImport['order_times']){
                        $qtyHanpukaiSequence = $this->hanpukaiSequence[$courseId][$productId]['qty'];
                        if(($qtyDataImport % $qtyHanpukaiSequence)!=0 ){
                            $data['error'][] = "\tQuantity hanpukai sequence is invalid";
                        }
                    }
                }
            }
        }

        $data['dataImport'] = $dataImport;
        return $data;
    }


    public function checkHanpukaiQty($profileId,$arrProduct,$aDataForValidated)
    {
        $hanpukaiFixed    = $aDataForValidated['hanpukaiFixed'];
        $hanpukaiSequence = $aDataForValidated['hanpukaiSequence'];
        $arrCourse        = $aDataForValidated['aCourseCodeIds'];
        if (isset($arrCourse[$profileId])){
            foreach ($arrProduct as $productId=>$productItem){
                $courseId = $arrCourse[$profileId];
                $qtyDataImport = $productItem['qty'];
                if (isset($hanpukaiFixed[$courseId]) && isset($hanpukaiFixed[$courseId][$productId])){
                    //hanpukai fixed
                    $qtyHanpukaiFixed = $hanpukaiFixed[$courseId][$productId]['qty'];
                    $qty = $qtyDataImport /$qtyHanpukaiFixed;
                    if(($qtyDataImport % $qtyHanpukaiFixed)==0){
                        $this->updateHanpukaiQty(['hanpukai_qty'=>(int)$qty],$profileId);
                        return true;
                    }

                }
                if (isset($hanpukaiSequence[$courseId]) && isset($hanpukaiSequence[$courseId][$productId])){
                    //hanpukai sequence
                    if ($hanpukaiSequence[$courseId][$productId]['delivery_number'] == $productItem['order_times']){
                        $qtyHanpukaiSequence = $hanpukaiSequence[$courseId][$productId]['qty'];
                        $qty = $qtyDataImport /$qtyHanpukaiSequence;
                        if(($qtyDataImport % $qtyHanpukaiSequence)==0 ){
                            $this->updateHanpukaiQty(['hanpukai_qty'=>(int)$qty],$profileId);
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }


    public function checkGwused($data,$dataGiftwrapping)
    {
        $dataImport = $data['dataImport'];
        if (isset($dataImport['gw_used']) && $dataImport['gw_used'] !=null ){
            $giftCode = $dataImport['gw_used'];
            if(isset($dataGiftwrapping[$giftCode])){
                $dataImport['gw_id'] = $dataGiftwrapping[$giftCode];
            }else{
                $dataImport['gw_id'] = 0;
            }
        }else{
            $dataImport['gw_id'] = 0;
        }

        $data['dataImport'] = $dataImport;
        return $data;
    }

    /**
     * ValidateData
     *
     * @param $dataImport
     * @return string[]
     */
    public function validateData($dataImport, $aDataForValidated, $typeProfile = \Riki\Subscription\Command\ProductCartBeforeImport::MAIN_PROFILE_CART, $row)
    {
        $data = array(
            'error' => null,
            'dataImport' => $dataImport
        );

        $aTimeSlotIds = $aDataForValidated['time_slot_data'];
        //$dataGiftwrapping = $aDataForValidated['gift_wrapping'];

        //check order times
        if ($dataImport['order_times'] == null) {
            $data['error'][] = "\tOrder times is invalid";
        }

        //check delivery_date
        if ($dataImport['delivery_date'] == null) {
            $data['error'][] = "\tDelivery date is invalid";
        }

        //check profile id
        $data = $this->checkProfile($data,$typeProfile);

        //check qty
        if ($dataImport['qty'] == '' || $dataImport['qty'] < 0) {
            $data['error'][] = "\tQuantity is invalid";
        }

        //check unit
        $data = $this->validateUnit($data);

        //check unit qty
        if ($dataImport['unit_qty'] == '' || $dataImport['unit_qty'] < 0) {
            $data['error'][] = "\tUnit quantity is invalid";
        }

        //check gw_used
/*        if ($dataImport['gw_used'] == '' || ($dataImport['gw_used'] != 0 && $dataImport['gw_used'] != 1)) {
            $data['error'][] = "\tGW_USED id is invalid";
        }*/

        //check shipping address
        if ($dataImport['shipping_address_id'] == null) {
            $data['error'][] = "\tShipping address is empty";
        }

        //check billing address
        if ($dataImport['billing_address_id'] == null) {

            if ($dataImport['shipping_address_id'] == null) {
                $data['error'][] = "\tBilling address is empty";
            }
            else{
                if($dataImport['shipping_address_id'] != 0 && $dataImport['shipping_address_id'] != 99999999){
                    $dataImport['billing_address_id'] = 0;
                }
                else{
                    $dataImport['billing_address_id'] = $dataImport['shipping_address_id'];
                }
            }
        }

        //check time slot
        $data = $this->validateTimeSlot($data, $aTimeSlotIds);

        //check address
        $data = $this->checkShippingAndBillingAddress($data);

        //check product id
        $data = $this->validateProductId($data);

        //check is spot
        $data = $this->checkIsSpot($data);

        //validate hanpukai qty
        $data = $this->validateHanpukaiQty($data,$dataImport,$aDataForValidated);


        //$data = $this->checkGwused($data,$dataGiftwrapping);

        //unset($data['dataImport']['order_times']);
        unset($data['dataImport']['customer_id']);
        unset($data['dataImport']['unit']);
        unset($data['dataImport']['combined_shipping_type']);
        unset($data['dataImport']['gw_used']);

        $data['dataImport']['created_at'] = $this->_time->date()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $data['dataImport']['updated_at'] = $this->_time->date()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $data['dataImport']['product_options'] = '';
        return $data;
    }

    public function getHanpukaiFixed()
    {
        $connection = $this->resourceConnection->getConnection('sales');
        $select     = $connection->select()->from(['hanpukai_fixed' => $connection->getTableName('hanpukai_fixed')]);
        $allData = $connection->fetchAll($select);

        $arrData = [];
        foreach ($allData as $item) {
            $arrData[$item['course_id']][$item['product_id']] = $item;
        }
        return $arrData;
    }

    public function getHanpukaiSequence()
    {
        $connection = $this->resourceConnection->getConnection('sales');
        $select     = $connection->select()->from(['hanpukai_sequence' => $connection->getTableName('hanpukai_sequence')]);
        $allData = $connection->fetchAll($select);

        $arrData = [];
        foreach ($allData as $item) {
            $arrData[$item['course_id']][$item['product_id']] = $item;
        }
        return $arrData;
    }

    /**
     * prepareData
     *
     * @param $fileName
     * @return array
     * @throws \Exception
     */
    public function prepareData($fileName, $typeProfileCart = self::MAIN_PROFILE_CART)
    {
        $dataResult = array();
        $dataRow = array();

        $this->removeBom($fileName);
        $dataCsv = $this->_readerCSV->getData($fileName);

        $aIdOldProfiles = [];
        $aProductIds = [];
        $aShippingIds = [];

        foreach ($dataCsv as $key => $value) {
            if ($key == 0) continue;
            foreach ($value as $k => $v) {
                if (isset($dataCsv[0][$k])) {
                    $keyColum = str_replace('"', '', $dataCsv[0][$k]);
                    $dataRow[trim($keyColum)] = $v;
                }
            }
            //emulate virtual data, will remove
            /*$dataRow['PROFILE_ID'] = '1820025595';
            $dataRow['DELIVERY_TIME_SLOT'] = '12:01-14:00';
            $dataRow['BILLING_ADDRESS_ID'] = 5981;
            $dataRow['SHIPPING_ADDRESS_ID'] = 617;
            $dataRow['ORDER_TIMES'] = 24;*/

            if ($dataRow['PROFILE_ID']) {
                $aIdOldProfiles[] = $dataRow['PROFILE_ID'];
            }

            if ($dataRow['PRODUCT_ID']) {
                $aProductIds[] = $dataRow['PRODUCT_ID'];
            }

            if ($dataRow['SHIPPING_ADDRESS_ID']) {
                $aShippingIds[] = $dataRow['SHIPPING_ADDRESS_ID'];
            }
            $dataResult[] = $dataRow;
        }

        $this->aIdOldProfiles = array_unique($aIdOldProfiles);
        $this->aProductIds   = array_unique($aProductIds);
        $aShippingIds = array_unique($aShippingIds);

        list($dataOldProfile, $aCustomerIds,$aCourseCodeIds) = $this->getOldProfileId($typeProfileCart);

        $this->aCustomerIds = array_unique($aCustomerIds);

        $dataTimeSlotIds = $this->getAllDeliveryTimeSlot();

        $this->aProductData = $this->getListProductBySku();

        $this->aCustomerAddressData = $this->getCustomerAddressData();

        $this->aCustomerAddressLegacyData = $this->getCustomerAddressLegacyData($aShippingIds);

        $dataGiftWrapping  = $this->getGiftwrapping();

        $this->hanpukaiFixed     = $this->getHanpukaiFixed();
        $this->hanpukaiSequence  = $this->getHanpukaiSequence();

        $this->dataOldProfile = $dataOldProfile;

        $dataForValidated = [
            'profile_id_data' => $this->dataOldProfile,
            'aCourseCodeIds' => $aCourseCodeIds,
            'time_slot_data' => $dataTimeSlotIds,
            'gift_wrapping' => $dataGiftWrapping,
            'product_data' => $this->aProductData,
            'customer_address_data' => $this->aCustomerAddressData,
            'customer_address_legacy_data' => $this->aCustomerAddressLegacyData,
            'hanpukaiFixed'=>$this->hanpukaiFixed,
            'hanpukaiSequence'=>$this->hanpukaiSequence,
        ];

        return [
            $dataResult, $dataForValidated
        ];
    }

    /**
     * GetOldProfileId
     *
     * @param null $oldProfileId
     * @return bool
     */
    public function getOldProfileId($typeProfileCart = self::MAIN_PROFILE_CART)
    {
        $aProfileData = [];
        $aCustomerIds = [];
        $aCourseCodeIds = [];
        if (!empty($this->aIdOldProfiles)) {
            $connection = $this->resourceConnection->getConnection('sales');
            $selectProfile = $connection->select()
                ->from([$connection->getTableName('subscription_profile')])
                ->joinLeft(['spv' => $connection->getTableName('subscription_profile_version')],
                    'subscription_profile.profile_id = spv.rollback_id'
                )
                ->where("subscription_profile.old_profile_id IN (?)", $this->aIdOldProfiles);

            $dataProfileSelect = $connection->fetchAll($selectProfile);

            if ($typeProfileCart == self::MAIN_PROFILE_CART) {
                foreach ($dataProfileSelect as $dataProfileItem) {
                    if ((int)$dataProfileItem['rollback_id'] > 0) {
                        $dataProfileItem['has_version_profile'] = 1;
                        $aProfileData[$dataProfileItem['old_profile_id']][$dataProfileItem['profile_id']] = $dataProfileItem;
                        $aCustomerIds[] = $dataProfileItem['customer_id'];
                        $aCourseCodeIds[$dataProfileItem['profile_id']] = $dataProfileItem['course_id'];
                    }
                    else{
                        $dataProfileItem['has_version_profile'] = 0;
                        $aProfileData[$dataProfileItem['old_profile_id']][$dataProfileItem['profile_id']] = $dataProfileItem;
                        $aCustomerIds[] = $dataProfileItem['customer_id'];
                        $aCourseCodeIds[$dataProfileItem['profile_id']] = $dataProfileItem['course_id'];
                    }
                }
            } else {
                foreach ($dataProfileSelect as $key => $dataProfileItem) {
                    if (!$dataProfileItem['rollback_id']) {
                        $aProfileData[$dataProfileItem['old_profile_id']][$dataProfileItem['profile_id']] = $dataProfileItem;
                        $aCustomerIds[] = $dataProfileItem['customer_id'];
                        $aCourseCodeIds[$dataProfileItem['profile_id']] = $dataProfileItem['course_id'];
                    }
                }
            }
            return [$aProfileData, $aCustomerIds,$aCourseCodeIds];
        }

        return [];
    }

    /**
     * show message
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $error
     * @param $row
     */
    public function showMessageError(
        InputInterface $input,
        OutputInterface $output,
        $error,
        $row
    )
    {
        $output->writeln("------------------------------------------");
        $output->writeln("[Row $row] Validate error!\n");
        $output->writeln($error . "\n");
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
     * Convert data import
     *
     * @param $data
     *
     * @return array
     */
    public function convertDataImport($data)
    {
        $dataImport = array();
        $dataImport['cart_id'] = $this->checkColumExit('CART_ID', $data);
        $dataImport['order_times'] = $this->checkColumExit('ORDER_TIMES', $data, 'int');
        //we substract by 1 to make align order times between KSS and Magento
        if((int)$dataImport['order_times']){
            $dataImport['order_times'] = $dataImport['order_times'] - 1;
        }
        $dataImport['profile_id'] = $this->checkColumExit('PROFILE_ID', $data);
        $dataImport['qty'] = $this->checkColumExit('QTY', $data, 'int');
        $dataImport['unit'] = $this->checkColumExit('UNIT', $data, 'int');
        $dataImport['unit_case'] = null;
        $dataImport['customer_id'] = null;
        $dataImport['unit_qty'] = $this->checkColumExit('UNIT_QTY', $data, 'int');
        $dataImport['product_type'] = $this->checkColumExit('PRODUCT_TYPE', $data);
        $dataImport['product_id'] = $this->checkColumExit('PRODUCT_ID', $data);
        $dataImport['old_product_id'] = $this->checkColumExit('PRODUCT_ID', $data);
        $dataImport['product_options'] = $this->checkColumExit('PRODUCT_OPTIONS', $data);
        $dataImport['parent_item_id'] = $this->checkColumExit('PARENT_ITEM_ID', $data);
        $dataImport['created_at'] = $this->checkColumExit('CREATED_AT', $data, 'datetime');
        $dataImport['updated_at'] = $this->checkColumExit('UPDATED_AT', $data, 'datetime');
        $dataImport['gw_used'] = $this->checkColumExit('GW_USED', $data);
        $dataImport['delivery_date'] = $this->checkColumExit('DELIVERY_DATE', $data, 'date');
        $dataImport['delivery_time_slot'] = $this->checkColumExit('DELIVERY_TIME_SLOT', $data);
        $dataImport['billing_address_id'] = $this->checkColumExit('BILLING_ADDRESS_ID', $data);;
        $dataImport['shipping_address_id'] = $this->checkColumExit('SHIPPING_ADDRESS_ID', $data);;
        $dataImport['is_spot'] = $this->checkColumExit('COMBINED_SHIPPING_TYPE', $data);
        return $dataImport;
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
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $flagTime = microtime(true);

        $fileName = $input->getArgument(self::FILE_NAME);

        $typeProfileCart = $input->getArgument(self::TYPE_PROFILE_CART);

        if ($fileName != "") {
            try {

                list($dataResult, $aDataForValidated) = $this->prepareData($fileName, $typeProfileCart);

                $row = 2;
                $totalError = 0;
                foreach ($dataResult as $data) {
                    // convert Data
                    $dataImport = $this->convertDataImport($data);

                    //validate data
                    $dataBeforeImport = $this->validateData($dataImport, $aDataForValidated, $typeProfileCart, $row);
                    $dataImport = $dataBeforeImport['dataImport'];
                    $errors = $dataBeforeImport['error'];

                    if (count($errors) > 0) {
                        $output->writeln("\n------------------------------------------------------------------------------------");
                        $output->writeln("[Row $row] Validate error!\n");
                        $output->writeln($errors);
                        $totalError++;
                    } else {
                        $output->writeln("------------------------------------------------------------------------------------");
                        $output->writeln("[Row $row] Validate successfully!\n");
                    }
                    $row++;
                }

                if ($totalError == 0) {
                    $output->writeln("===========================================================================================");
                    $output->writeln("\t\tValidate file successfully \n");
                    $output->writeln("===========================================================================================");
                } else {
                    $output->writeln("\n\n===========================================================================================");
                    $output->writeln("\n\tValidate error \n");
                    $output->writeln("===========================================================================================");
                }
            } catch (\Exception $e) {
                //$output->writeln($e->getMessage());
                $this->logger->critical($e);
                exit();
            }
        }

        $timeElapsedSecs = microtime(true) - $flagTime;
        echo "Script run time :" . ($timeElapsedSecs) . "\n";
    }
}