<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\ThirdPartyImportExport\Model\ExportNextDelivery;

use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper;
use Magento\Framework\File\Csv;
use Magento\Framework\App\Filesystem\DirectoryList;
use Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerCSV;
use Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryShipmentHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Riki\TimeSlots\Model\TimeSlots as TimeSlots;
use Riki\DeliveryType\Model\Delitype as DeliveryType;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\GiftWrapping\Api\WrappingRepositoryInterface;
use Riki\SubscriptionCourse\Model\Course as CourseModel;
use Riki\SubscriptionCourse\Model\ResourceModel\Course as ResourceModelCourse;
use Riki\Subscription\Helper\Hanpukai\Data as HelperHanpukaiData;
use Riki\Subscription\Model\ProductCart\ProductCartFactory as ProductCartModel;
use Riki\Subscription\Helper\Order\Simulator as OrderSimulator;

class SubscriptionShipment
{
    const PROFILETYPE = 'subscription_profile';
    const SHIPMENTTYPE = 'shipment';
    const ORDERITEMTYPE = 'order_item';

    const DEFAULT_LOCAL_SAVE = 'var/bi_subscription_next_delivery_shipment';
    const DS = '/';

    /**
     * @var TimezoneInterface
     */
    protected $_timezone;

    /**
     * @var File
     */
    protected $_file;

    /**
     * @var GlobalHelper
     */
    protected $_dataHelper;

    /**
     * @var
     */
    protected $_csv;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerShipmentCSV
     */
    protected $logger;

    /**
     * @var
     */
    protected $pathTmp;

    /**
     * @var
     */
    protected $_path;

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connectionSales;

    /**
     * @var SubProfileNextDeliveryShipmentHelper
     */
    protected $_subProfileNextDeliveryHelper;
    /**
     * @var DateTime
     */
    protected $_dateTime;

    /**
     * @var
     */
    protected $consumerName;

    /**
     * @var
     */
    protected $defaultColumn;

    /**
     * @var
     */
    protected $defaultColumnShipment;

    /**
     * @var
     */
    protected $defaultColumnShipmentPrefix;

    /**
     * @var
     */
    protected $defaultColumnOrderAddress;

    /**
     * @var
     */
    protected $defaultColumnOrderAddressPrefix;
    /**
     * @var
     */
    protected $defaultColumnSubProfile;

    /**
     * @var
     */
    protected $defaultColumnSubProfilePrefix;

    /**
     * @var int
     */
    protected $hanpukaiOrderTime = 0;

    /**
     * @var array
     */
    protected $aSimulateShipmentData = [];

    /**
     * @var array
     */
    protected $aSimulateOrderData = [];
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $subscriptionConnection;

    /**
     * @var \Bluecom\PaymentFee\Helper\Data
     */
    protected $paymentFeeHelper;

    /**
     * SubscriptionShipment constructor.
     * @param DirectoryList $directoryList
     * @param File $file
     * @param GlobalHelper $globalHelper
     * @param DateTime $dateTime
     * @param TimezoneInterface $timezone
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerShipmentCSV $logger
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\HandlerShipmentCSV $handlerShipmentCSV
     * @param ResourceConnection $resourceConnection
     * @param \Magento\Framework\App\State $appState
     * @param \Bluecom\PaymentFee\Helper\Data $paymentFeeHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $subscriptionCourseFactory
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param SubProfileNextDeliveryShipmentHelper $subProfileNextDeliveryHelper
     */
    public function __construct(
        DirectoryList $directoryList,
        File $file,
        GlobalHelper $globalHelper,
        DateTime $dateTime,
        TimezoneInterface $timezone,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerShipmentCSV $logger,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\HandlerShipmentCSV $handlerShipmentCSV,
        ResourceConnection $resourceConnection,
        \Magento\Framework\App\State $appState,
        \Bluecom\PaymentFee\Helper\Data $paymentFeeHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\SubscriptionCourse\Model\CourseFactory $subscriptionCourseFactory,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryShipmentHelper $subProfileNextDeliveryHelper
    ) {
        $this->_directoryList = $directoryList;
        $this->_file = $file;
        $this->_dataHelper = $globalHelper;
        $this->_dateTime = $dateTime;

        $this->_timezone = $timezone;
        $this->logger = $logger;
        $this->logger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->handlerShipmentCSV = $handlerShipmentCSV;

        $this->_resource = $resourceConnection;
        $this->_connectionSales = $this->_resource->getConnection('sales');
        $this->subscriptionConnection = $this->_resource->getConnection('subscription');

        $this->appState = $appState;
        $this->paymentFeeHelper = $paymentFeeHelper;
        $this->_customerRepository = $customerRepository;
        $this->subscriptionCourseFactory = $subscriptionCourseFactory;
        $this->profileRepository = $profileRepository;
        $this->_subProfileNextDeliveryHelper = $subProfileNextDeliveryHelper;
    }

    /**
     * @return null|void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function initExport()
    {

        if (!$this->_dataHelper->isEnable()) {
            return null;
        }

        $this->_csv = new Csv(new File());
        $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $localCsv = $this->_dataHelper->getConfig(\Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryShipmentHelper::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_SHIPMENT_LOCAL_PATH);
        if (!$localCsv) {
            $createFileLocal[] = $baseDir . self::DS . self::DEFAULT_LOCAL_SAVE;
            $createFileLocal[] = $baseDir . self::DS . self::DEFAULT_LOCAL_SAVE . '_tmp';
            $this->_path = self::DEFAULT_LOCAL_SAVE;
            $this->pathTmp = self::DEFAULT_LOCAL_SAVE . '_tmp';
        } else {
            if (trim($localCsv, -1) == self::DS) {
                $localCsv = str_replace(self::DS, '', $localCsv);
            }
            $createFileLocal[] = $baseDir . self::DS . $localCsv;
            $createFileLocal[] = $baseDir . self::DS . $localCsv . '_tmp';
            $this->_path = $localCsv;
            $this->pathTmp = $localCsv . '_tmp';
        }

        $this->_subProfileNextDeliveryHelper->backupLog(
            'bi_export_subscription_profile_next_delivery_shipment',
            $this->logger
        );
        foreach ($createFileLocal as $path) {
            if (!$this->_file->isDirectory($path)) {
                if (!$this->_file->createDirectory($path)) {
                    $this->logger->info(__('Can not create dir file') . $path);
                    return;
                }
            }
            if (!$this->_file->isWritable($path)) {
                $this->logger->info(__('The folder have to change permission to 755') . $path);
                return;
            }
        }
    }

    /**
     * @param ItemsInterface $message
     * @return void
     */
    public function exportSubscriptionShipmentHeader($subProfile, $consumerName)
    {
        try {
            $this->hanpukaiOrderTime = 0;

            $this->setConsumerName($consumerName);

            $this->handlerShipmentCSV->setDynamicFileLog($consumerName);
            $this->logger->setHandlers(['system' => $this->handlerShipmentCSV]);

            $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);

            $this->initExport();

            $iProfileId = $subProfile['profile_id'];

            if ($subProfile) {
                if ($subProfile['type_profile'] == \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionProfileSimulate::SUBSCRIPTION_PROFILE_MAIN) {
                    $nameCsvSubscriptionProfileShipment = 'subscription-profile-shipment-' . $this->_timezone->date()->format('Ymd') . '-' . $consumerName . '.csv';
                    $arrExportShipmentData = $this->appState->emulateAreaCode(
                        'adminhtml',
                        [$this, "exportSubProfileShipment"],
                        [$subProfile]
                    );
                    $nameCsvSubscriptionProfileShipment = $baseDir . self::DS . $this->pathTmp . self::DS . $nameCsvSubscriptionProfileShipment;
                    $this->_dataHelper->writeFileLocal($nameCsvSubscriptionProfileShipment, $arrExportShipmentData);
                    if (count($arrExportShipmentData) > 1) {
                        $this->logger->info('Write profile ' . $iProfileId . ' into ' . $nameCsvSubscriptionProfileShipment . ' successfully');
                    }
                } elseif ($subProfile['type_profile'] == \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionProfileSimulate::SUBSCRIPTION_PROFILE_VERSION) {
                    $nameCsvSubscriptionProfileShipmentVersion = 'subscription_profile_shipment_version-1-' . $this->_timezone->date()->format('Ymd') . '-' . $consumerName . '.csv';
                    $arrExportShipmentVersionData = $this->appState->emulateAreaCode(
                        'adminhtml',
                        [$this, "exportSubProfileShipment"],
                        [$subProfile, true]
                    );
                    $nameCsvSubscriptionProfileShipmentVersion = $baseDir . self::DS . $this->pathTmp . self::DS . $nameCsvSubscriptionProfileShipmentVersion;
                    $this->_dataHelper->writeFileLocal(
                        $nameCsvSubscriptionProfileShipmentVersion,
                        $arrExportShipmentVersionData
                    );
                    if (count($arrExportShipmentVersionData) > 1) {
                        $this->logger->info('Write profile ' . $iProfileId . ' into ' . $nameCsvSubscriptionProfileShipmentVersion . ' successfully');
                    }
                } elseif ($subProfile['type_profile'] == \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionProfileSimulate::SUBSCRIPTION_PROFILE_HANPUKAI) {

                    /**
                     * Export Hanpukai sequence
                     */
                    $this->appState->emulateAreaCode(
                        'adminhtml',
                        [$this, "exportSubShipmentSequenceOnlyHeader"],
                        [$subProfile, $baseDir]
                    );
                }
            }

            $this->hanpukaiOrderTime = 0;
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            throw $e;
        }
    }

    /**
     * @param $subProfile
     * @param bool $isExportShipmentVersion
     * @param null $iDeliveryNumber
     * @return array
     */
    public function exportSubProfileShipment($subProfile, $isExportShipmentVersion = false, $iDeliveryNumber = null)
    {
        $iProfileId = $subProfile['profile_id'];
        $iMapVersionAndProfileId = isset($subProfile['origin_profile_id']) ? $subProfile['origin_profile_id'] : null;

        $aSubscriptionProfileData = [];

        $selectSubProfiles = $this->_connectionSales->select()->from(
            $this->_connectionSales->getTableName('subscription_profile')
        )->where($this->_connectionSales->getTableName('subscription_profile') . '.profile_id = ?', $iProfileId);

        $querySubProfiles = $this->_connectionSales->query($selectSubProfiles);

        while ($subProfileData = $querySubProfiles->fetch()) {
            /*convert subscription profile date time columns to config timezone*/
            $subProfileData = $this->convertDateTimeColumnsToConfigTimezone(self::PROFILETYPE, $subProfileData);

            if (isset($this->hanpukaiOrderTime) && $this->hanpukaiOrderTime > 0) {
                $subProfileData['order_times'] = $this->hanpukaiOrderTime;
            } else {
                if (isset($subProfileData['order_times'])) {
                    $subProfileData['order_times'] = $subProfileData['order_times'] + 1;
                }
            }

            $aSubscriptionProfileData[$subProfileData['profile_id']] = $subProfileData;
        }

        $simulateData = $this->getInfoSimulateShipmentWithId($iProfileId, $iDeliveryNumber);

        $orderItemData = $simulateData['orderItemData'];
        $orderAddressData = $simulateData['orderAddressData'];
        $aShipmentData = $simulateData['shipmentData'];

        $columnShipmentHeader = [];
        $exportDataShipment = [];

        if ($aShipmentData) {
            foreach ($aShipmentData as $itemShipmentData) {
                $aColumnShipment = $this->getDefaultColumnShipment();
                $aColumnShipmentAddresses = $this->getDefaultColumnOrderAddress();
                $aColumnSubscriptionProfile = $this->getDefaultColumnSubscriptionProfile();
                $columnShipmentHeader = $this->getDefaultColumn();

                //get shipment data
                if (empty($aColumnShipment) || empty($aColumnShipmentAddresses)) {
                    continue;
                }

                $aColumn = [];
                $aColumn['shipment'] = $aColumnShipment;
                $aColumn['shipmentAddress'] = $aColumnShipmentAddresses;
                $aColumn['subProfile'] = $aColumnSubscriptionProfile;

                $aData['shipment'] = $itemShipmentData;
                $aData['order'] = $orderItemData;
                $aData['shipmentAddress'] = $orderAddressData;
                $aData['subProfile'] = $aSubscriptionProfileData;

                $exportDataShipment[] = $this->getExportSubShipmentData(
                    $iProfileId,
                    $aColumn,
                    $aData,
                    $isExportShipmentVersion,
                    $iMapVersionAndProfileId
                );
            }
            $exportDataShipment = array_merge([$columnShipmentHeader], $exportDataShipment);
        }

        return $exportDataShipment;
    }

    /**
     * @param $iProfileId
     * @param $aColumn
     * @param $aData
     * @param $isExportShipmentVersion
     * @param $arrMapVersionAndProfile
     * @return array
     */
    public function getExportSubShipmentData(
        $iProfileId,
        $aColumn,
        $aData,
        $isExportShipmentVersion,
        $iMapVersionAndProfileId
    ) {
        $aColumnShipments = $aColumn['shipment'];
        $aColumnShipmentAddresses = $aColumn['shipmentAddress'];
        $sColumnSubProfiles = $aColumn['subProfile'];

        $aShipmentData = $aData['shipment'];
        $aOrderData = $aData['order'];
        $aShipmentAddressData = $aData['shipmentAddress'];
        $aSubProfileData = $aData['subProfile'];

        $aSubProfileItemData = $aSubProfileData[$iProfileId];

        if ($isExportShipmentVersion == true && $iMapVersionAndProfileId) {
            $iProfileId = $iMapVersionAndProfileId;
        }

        $aShipmentProfileData[] = $iProfileId;

        //push shipment data
        foreach ($aColumnShipments as $sColumnShipment) {
            $aShipmentProfileData[] = isset($aShipmentData[$sColumnShipment]) ? $aShipmentData[$sColumnShipment] : '';
        }

        //push shipment address data
        $aShipmentAddressData = isset($aShipmentAddressData[$aShipmentData['shipping_address_id']]) ? $aShipmentAddressData[$aShipmentData['shipping_address_id']] : [];
        foreach ($aColumnShipmentAddresses as $sColumnShipmentAddresses) {
            $aShipmentProfileData[] = isset($aShipmentAddressData[$sColumnShipmentAddresses]) ? $aShipmentAddressData[$sColumnShipmentAddresses] : '';
        }

        //push subscription profile data
        foreach ($sColumnSubProfiles as $sColumnSubProfile) {
            $aShipmentProfileData[] = isset($aSubProfileItemData[$sColumnSubProfile]) ? $aSubProfileItemData[$sColumnSubProfile] : '';
        }

        //add more caculate field
        $iOrderId = $aShipmentData['order_id'];
        $sTimeSlotStart = isset($aOrderData[$iOrderId]['delivery_timeslot_from']) ? $aOrderData[$iOrderId]['delivery_timeslot_from'] : '';
        $sTimeSlotEnd = isset($aOrderData[$iOrderId]['delivery_timeslot_to']) ? $aOrderData[$iOrderId]['delivery_timeslot_to'] : '';

        $aShipmentProfileData[] = $sTimeSlotStart;
        $aShipmentProfileData[] = $sTimeSlotEnd;
        $aShipmentProfileData[] = $this->paymentFeeHelper->getPaymentTaxRate();
        $aShipmentProfileData[] = $this->getConsumerIdByUserId($aShipmentData['customer_id']); //consumer_db_id


        //push subscription course into file
        $courseData['hanpukai_qty'] = '';
        $courseData['course_hanpukai_type'] = '';
        $courseData['course_hanpukai_maximum_order_times'] = '';
        $courseData['course_hanpukai_delivery_date_allowed'] = '';
        $courseData['course_hanpukai_delivery_date_from'] = '';
        $courseData['course_hanpukai_delivery_date_to'] = '';
        $courseData['course_hanpukai_first_delivery_date'] = '';

        if ($iProfileId) {
            try {
                $profileData = $this->profileRepository->get($iProfileId);
                if ($profileData->getHanpukaiQty()) {
                    $courseData['hanpukai_qty'] = $profileData->getHanpukaiQty();
                }

                $subscriptionCourseId = $profileData->getCourseId();
                if ($subscriptionCourseId) {
                    $subscriptionCourseData = $this->subscriptionCourseFactory->create()->load($subscriptionCourseId);
                    if ($subscriptionCourseData) {
                        $courseData['course_hanpukai_type'] = $subscriptionCourseData->getData('hanpukai_type');
                        $courseData['course_hanpukai_maximum_order_times'] = $subscriptionCourseData->getData('hanpukai_maximum_order_times');
                        $courseData['course_hanpukai_delivery_date_allowed'] = $subscriptionCourseData->getData('hanpukai_delivery_date_allowed');

                        if ($subscriptionCourseData->getData('hanpukai_delivery_date_from')) {
                            $courseData['course_hanpukai_delivery_date_from'] = $this->convertDateTimeValueToConfigTimezone($subscriptionCourseData->getData('hanpukai_delivery_date_from'));
                        }

                        if ($subscriptionCourseData->getData('hanpukai_delivery_date_to')) {
                            $courseData['course_hanpukai_delivery_date_to'] = $this->convertDateTimeValueToConfigTimezone($subscriptionCourseData->getData('hanpukai_delivery_date_to'));
                        }

                        if ($subscriptionCourseData->getData('hanpukai_first_delivery_date')) {
                            $courseData['course_hanpukai_first_delivery_date'] = $this->convertDateTimeValueToConfigTimezone($subscriptionCourseData->getData('hanpukai_first_delivery_date'));
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
            }
        }

        $aShipmentProfileData = array_merge($aShipmentProfileData, $courseData);

        return $aShipmentProfileData;
    }

    /**
     * @param $subProfile
     * @param $baseDir
     */
    public function exportSubShipmentSequenceOnlyHeader($subProfile, $baseDir)
    {
        $arrFileMake = [];
        $arrShipmentHeaderDataExport = [];
        $arrExportShipmentHeader = [];

        $iProfileId = $subProfile['profile_id'];
        $arrDeliveryNeedExport = $subProfile['hanpukai_delivery_number'];

        foreach ($arrDeliveryNeedExport as $deliveryNumber) {
            $this->hanpukaiOrderTime = $deliveryNumber;

            if (!in_array($deliveryNumber, $arrFileMake)) {
                $arrFileMake[] = $deliveryNumber;
            }

            //collect data for export subscription profile header
            $subShipmentData = $this->exportSubProfileShipment($subProfile, false, $deliveryNumber);

            if (!empty($subShipmentData)) {
                array_shift($subShipmentData);
            }

            $arrShipmentHeaderDataExport[$deliveryNumber] = $subShipmentData;
        }

        if (empty($arrExportShipmentHeader)) {
            $arrExportShipmentHeader = $this->getDefaultColumn();
        }

        // Make file export
        foreach ($arrFileMake as $fileNumber) {
            $arrShipmentDetailData = $arrShipmentHeaderDataExport[$fileNumber];

            $nameCsvSubShipmentHeaderHanpukaiSequence = 'subscription_profile_shipment_version-' . $fileNumber . '-' . $this->_timezone->date()->format('Ymd') . '-' . $this->getConsumerName() . '.csv';
            $nameCsvSubShipmentHeaderHanpukaiSequence = $baseDir . self::DS . $this->pathTmp . self::DS . $nameCsvSubShipmentHeaderHanpukaiSequence;

            $arrShipmentDetailData = array_merge([$arrExportShipmentHeader], $arrShipmentDetailData);

            if (count($arrShipmentDetailData) > 0) {
                $this->_dataHelper->writeFileLocal($nameCsvSubShipmentHeaderHanpukaiSequence, $arrShipmentDetailData);
                $this->logger->info('Write profile ' . $iProfileId . ' into ' . $nameCsvSubShipmentHeaderHanpukaiSequence . ' successfully');
            }
        }
    }

    /**
     * Simulate profile to shipment with profile id
     *
     * @param $subProfileId
     * @param null $iDeliveryNumber
     *
     * @return array
     * @throws \Exception
     * @internal param $arrProfileId
     */
    public function getInfoSimulateShipmentWithId($iProfileId, $iDeliveryNumber = null)
    {
        $orderSimulate = null;
        $shipmentSimulate = null;

        $orderItemData = [];
        $orderAddressData = [];
        $shipmentData = [];
        $shipmentItemData = [];

        try {
            $orderSimulate = null;
            $shipmentSimulate = null;

            if ($iDeliveryNumber) {
                $orderSimulate = $this->getSimulateOrderData($iProfileId, $iDeliveryNumber);
                $shipmentSimulate = $this->getSimulateShipmentData($iProfileId, $iDeliveryNumber);
            } else {
                $orderSimulate = $this->getSimulateOrderData($iProfileId, 0);
                $shipmentSimulate = $this->getSimulateShipmentData($iProfileId, 0);
            }

            if ($orderSimulate && $shipmentSimulate) {
                $orderItemIds = [];
                $orderIds = [];

                foreach ($orderSimulate->getItems() as $item) {
                    $orderItemIds[] = $item->getItemId();
                    $orderIds[] = $item->getOrderId();
                }

                $orderItemData = [];

                if (!empty($orderItemIds)) {
                    //get data from sales_order and sales_order_item
                    $selectSalesOrderItem = $this->subscriptionConnection->select()->from([
                        'sc' => $this->subscriptionConnection->getTableName('emulator_sales_order_item_tmp')
                    ])->where('sc.item_id  IN(?)', $orderItemIds);

                    $querySalesOrderItem = $this->subscriptionConnection->query($selectSalesOrderItem);

                    while ($salesOrderItemDataRow = $querySalesOrderItem->fetch()) {
                        /*convert sales order item date time columns to config timezone*/
                        $salesOrderItemDataRow = $this->convertDateTimeColumnsToConfigTimezone(
                            self::ORDERITEMTYPE,
                            $salesOrderItemDataRow
                        );

                        $orderItemData[$salesOrderItemDataRow['item_id']] = $salesOrderItemDataRow;
                    }
                }

                if (!empty($orderIds)) {
                    $selectSalesOrderAddress = $this->subscriptionConnection->select()->from([
                        'soa' => $this->subscriptionConnection->getTableName('emulator_sales_order_address_tmp')
                    ])->where('soa.parent_id  IN(?)', $orderIds);

                    $querySalesOrderAddress = $this->subscriptionConnection->query($selectSalesOrderAddress);

                    while ($salesOrderAddressDataRow = $querySalesOrderAddress->fetch()) {
                        $orderAddressData[$salesOrderAddressDataRow['entity_id']] = $salesOrderAddressDataRow;
                    }
                }

                $iItemShipmentIds = [];
                foreach ($shipmentSimulate as $itemShipment) {
                    $iItemShipmentIds[] = $itemShipment ? $itemShipment->getEntityId() : 0;
                }

                $shipmentData = [];
                $shipmentItemData = [];

                if (!empty($orderItemIds)) {
                    //get data from sales_order and sales_order_item
                    $selectSalesShipment = $this->subscriptionConnection->select()->from([
                        'sc' => $this->subscriptionConnection->getTableName('emulator_sales_shipment_tmp')
                    ])->where('sc.entity_id  IN(?)', $iItemShipmentIds);

                    $querySalesShipment = $this->subscriptionConnection->query($selectSalesShipment);

                    while ($salesShipmentDataRow = $querySalesShipment->fetch()) {
                        /*convert sales shipment date time columns to config timezone*/
                        $salesShipmentDataRow = $this->convertDateTimeColumnsToConfigTimezone(
                            self::SHIPMENTTYPE,
                            $salesShipmentDataRow
                        );

                        $shipmentData[$salesShipmentDataRow['entity_id']] = $salesShipmentDataRow;
                    }

                    $selectSalesShipmentItem = $this->subscriptionConnection->select()->from([
                        'sc' => $this->subscriptionConnection->getTableName('emulator_sales_shipment_item_tmp')
                    ])->where('sc.parent_id  IN(?)', $iItemShipmentIds);

                    $querySalesShipmentItem = $this->subscriptionConnection->query($selectSalesShipmentItem);

                    while ($salesShipmentItemDataRow = $querySalesShipmentItem->fetch()) {
                        $shipmentItemData[$salesShipmentItemDataRow['parent_id']][$salesShipmentItemDataRow['entity_id']] = $salesShipmentItemDataRow;
                    }
                }
            }
        } catch (LocalizedException $e) {
            $this->logger->info($e->getMessage());
        }

        return [
            'orderItemData' => $orderItemData,
            'orderAddressData' => $orderAddressData,
            'shipmentData' => $shipmentData,
            'shipmentItemData' => $shipmentItemData
        ];
    }

    /**
     * @param $customerId
     * @return \Magento\Framework\Api\AttributeInterface|null|string
     */
    public function getConsumerIdByUserId($customerId)
    {
        $consumerDb = '';
        if ($customerId) {
            try {
                $customer = $this->_customerRepository->getById($customerId);
                if ($customer->getCustomAttribute('consumer_db_id')) {
                    $consumerDb = $customer->getCustomAttribute('consumer_db_id')->getValue();
                }
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
            }
        }
        return $consumerDb;
    }

    /**
     * Convert datetime columns to config timezone for
     *      subscription_profile/sales_shipment/sales_order_item object
     *
     * @param $type
     * @param $object
     * @return mixed
     */
    public function convertDateTimeColumnsToConfigTimezone($type, $object)
    {
        $dateTimeColumns = [];
        if ($type == self::PROFILETYPE) {
            $dateTimeColumns = $this->getSubscriptionProfileDateTimeColumns();
        } else {
            if ($type == self::SHIPMENTTYPE) {
                $dateTimeColumns = $this->getShipmentDateTimeColumns();
            } else {
                if ($type == self::ORDERITEMTYPE) {
                    $dateTimeColumns = $this->getOrderItemDateTimeColumns();
                }
            }
        }

        if ($dateTimeColumns) {
            foreach ($dateTimeColumns as $cl) {
                if (!empty($object[$cl])) {
                    $object[$cl] = $this->_dateTime->date(
                        'Y-m-d H:i:s',
                        $this->_timezone->formatDateTime($object[$cl], 2, 2)
                    );
                }
            }
        }

        return $object;
    }

    /**
     * Convert utc time to config timezone
     *
     * @param $value
     * @return string
     */
    public function convertDateTimeValueToConfigTimezone($value)
    {
        return $this->_dateTime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($value, 2, 2));
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table subscription_profile
     * @return mixed
     */
    public function getSubscriptionProfileDateTimeColumns()
    {
        return [
            'updated_date',
            'disengagement_date'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table subscription_course
     * @return mixed
     */
    public function getShipmentDateTimeColumns()
    {
        return [
            'created_at',
            'updated_at',
            'shipment_date',
            'payment_date',
            'export_sap_date',
            'nestle_payment_receive_date'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_order_item
     * @return mixed
     */
    public function getOrderItemDateTimeColumns()
    {
        return [
            'created_at',
            'updated_at'
        ];
    }

    /**
     * @param $consumerName
     */
    public function setConsumerName($consumerName)
    {
        $this->consumerName = $consumerName;
    }

    /**
     * @return string
     */
    public function getConsumerName()
    {
        if (!$this->consumerName) {
            return '';
        }
        return $this->consumerName;
    }

    /**
     * @return array
     */
    public function getDefaultColumn()
    {

        if (!empty($this->defaultColumn)) {
            return $this->defaultColumn;
        }
        $columnShipmentHeader = [];

        $columnShipmentHeader[] = 'shipment.profile_id';

        /*merge column from sales_shipment*/
        $aColumnShipment = $this->getDefaultColumnShipment(true);
        $columnShipmentHeader = array_merge($columnShipmentHeader, $aColumnShipment);

        /*merge column from sales_order_address*/
        $aColumnSalesOrderAddressPrefix = $this->getDefaultColumnOrderAddress(true);
        $columnShipmentHeader = array_merge($columnShipmentHeader, $aColumnSalesOrderAddressPrefix);

        /*merge column from subscription_profile*/
        $aColumnSubscriptionProfilePrefix = $this->getDefaultColumnSubscriptionProfile(true);
        $columnShipmentHeader = array_merge($columnShipmentHeader, $aColumnSubscriptionProfilePrefix);

        //add more caculate field
        $columnShipmentHeader[] = 'shipment.time_slot_start';
        $columnShipmentHeader[] = 'shipment.time_slot_end';

        $columnShipmentHeader[] = 'shipment.shipping_tax_rate';
        $columnShipmentHeader[] = 'shipment.customer_consumer_db_id';

        $columnShipmentHeader[] = 'subscription_profile.hanpukai_qty';
        $columnShipmentHeader[] = 'subscription_profile.course_hanpukai_type';
        $columnShipmentHeader[] = 'subscription_profile.course_hanpukai_maximum_order_times';
        $columnShipmentHeader[] = 'subscription_profile.course_hanpukai_delivery_date_allowed';
        $columnShipmentHeader[] = 'subscription_profile.course_hanpukai_delivery_date_from';
        $columnShipmentHeader[] = 'subscription_profile.course_hanpukai_delivery_date_to';
        $columnShipmentHeader[] = 'subscription_profile.course_hanpukai_first_delivery_date';

        $this->defaultColumn = $columnShipmentHeader;

        return $columnShipmentHeader;
    }

    /**
     * @return array
     */
    public function getDefaultColumnShipment($isPrefix = false)
    {

        if ($isPrefix) {
            if (!empty($this->defaultColumnShipmentPrefix)) {
                return $this->defaultColumnShipmentPrefix;
            }

            $columnShipment = $this->_connectionSales->describeTable('sales_shipment');

            foreach ($columnShipment as $sColumnShipment => $value) {
                $aColumnShipmentPrefix[] = 'shipment.' . $sColumnShipment;
            }

            $this->defaultColumnShipmentPrefix = $aColumnShipmentPrefix;

            return $this->defaultColumnShipmentPrefix;
        } else {
            if (!empty($this->defaultColumnShipment)) {
                return $this->defaultColumnShipment;
            }

            $columnShipment = $this->_connectionSales->describeTable('sales_shipment');

            $this->defaultColumnShipment = array_keys($columnShipment);

            return $this->defaultColumnShipment;
        }
    }

    /**
     * @return array
     */
    public function getDefaultColumnOrderAddress($isPrefix = false)
    {

        if ($isPrefix) {
            if (!empty($this->defaultColumnOrderAddressPrefix)) {
                return $this->defaultColumnOrderAddressPrefix;
            }

            $columnSalesOrderAddress = $this->_connectionSales->describeTable('sales_order_address');
            $aColumnSalesOrderAddressPrefix = [];
            foreach ($columnSalesOrderAddress as $sColumnSalesOrderAddress => $value) {
                $aColumnSalesOrderAddressPrefix[] = 'shipment.shipping_address_' . $sColumnSalesOrderAddress;
            }

            $this->defaultColumnOrderAddressPrefix = $aColumnSalesOrderAddressPrefix;

            return $aColumnSalesOrderAddressPrefix;
        } else {
            if (!empty($this->defaultColumnOrderAddress)) {
                return $this->defaultColumnOrderAddress;
            }

            $columnSalesOrderAddress = $this->_connectionSales->describeTable('sales_order_address');

            $this->defaultColumnOrderAddress = array_keys($columnSalesOrderAddress);

            return $this->defaultColumnOrderAddress;
        }
    }

    /**
     * @return array
     */
    public function getDefaultColumnSubscriptionProfile($isPrefix = false)
    {

        if ($isPrefix) {
            if (!empty($this->defaultColumnSubProfilePrefix)) {
                return $this->defaultColumnSubProfilePrefix;
            }

            $columnSubscriptionProfile = $this->_connectionSales->describeTable('subscription_profile');
            $aColumnSubscriptionProfilePrefix = [];
            foreach ($columnSubscriptionProfile as $sColumnSubscriptionProfile => $value) {
                $aColumnSubscriptionProfilePrefix[] = 'shipment.subscription_profile_' . $sColumnSubscriptionProfile;
            }

            $this->defaultColumnSubProfilePrefix = $aColumnSubscriptionProfilePrefix;

            return $aColumnSubscriptionProfilePrefix;
        } else {
            if (!empty($this->defaultColumnSubProfile)) {
                return $this->defaultColumnSubProfile;
            }

            $columnSubscriptionProfile = $this->_connectionSales->describeTable('subscription_profile');

            $this->defaultColumnSubProfile = array_keys($columnSubscriptionProfile);

            return $this->defaultColumnSubProfile;
        }
    }

    /**
     * @param $shipmentSimulate
     * @param $profileId
     * @param int $iDeliveryNumber
     */
    public function addSimulateShipmentData($shipmentSimulate, $profileId, $iDeliveryNumber = 0)
    {
        $this->aSimulateShipmentData[$profileId][$iDeliveryNumber] = $shipmentSimulate;
    }

    /**
     * @param $profileId
     * @param $iDeliveryNumber
     * @return mixed
     */
    public function getSimulateShipmentData($profileId, $iDeliveryNumber)
    {

        if (isset($this->aSimulateShipmentData[$profileId][$iDeliveryNumber])) {
            return $this->aSimulateShipmentData[$profileId][$iDeliveryNumber];
        }

        return null;
    }

    /**
     * @param $orderSimulate
     * @param $profileId
     * @param int $iDeliveryNumber
     */
    public function addSimulateOrderData($orderSimulate, $profileId, $iDeliveryNumber = 0)
    {
        $this->aSimulateOrderData[$profileId][$iDeliveryNumber] = $orderSimulate;
    }


    /**
     * @param $profileId
     * @param $iDeliveryNumber
     * @return mixed
     */
    public function getSimulateOrderData($profileId, $iDeliveryNumber)
    {
        if (isset($this->aSimulateOrderData[$profileId][$iDeliveryNumber])) {
            return $this->aSimulateOrderData[$profileId][$iDeliveryNumber];
        }
        return null;
    }

    /**
     *
     */
    public function freeSimulateOrderData(){
        $this->aSimulateOrderData = [];
    }

    /**
     *
     */
    public function freeSimulateShipmentData(){
        $this->aSimulateShipmentData = [];
    }
}
