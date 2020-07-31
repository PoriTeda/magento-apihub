<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\ThirdPartyImportExport\Model\ExportNextDelivery;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper;
use Magento\Framework\File\Csv;
use Magento\Framework\App\Filesystem\DirectoryList;
use Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryShipmentHelper;
use Magento\Framework\App\ResourceConnection;

class SubscriptionShipmentDetail
{
    const PROFILETYPE = 'subscription_profile';
    const SHIPMENTTYPE = 'shipment';
    const ORDERITEMTYPE = 'order_item';

    const DEFAULT_LOCAL_SAVE = 'var/bi_subscription_next_delivery_shipment_detail';
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
     * @var
     */
    protected $logger;

    /**
     * @var
     */
    protected $_subProfileNextDeliveryShipmentHelper;

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
     * @var DateTime
     */
    protected $_dateTime;

    /**
     * @var \Magento\Framework\Pricing\Adjustment\CalculatorInterface
     */
    protected $_adjustmentCalculator;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connectionSales;

    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerSubShipmentProfileCart
     */
    protected $cartLogger;

    /**
     * @var
     */
    protected $consumerName;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper
     */
    protected $bundleItemsHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $defaultColumn = [];
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var array
     */
    protected $aSimulateSimulateData = [];

    /**
     * @var array
     */
    protected $aSimulateOrderData = [];
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $subscriptionConnection;

    /**
     * SubscriptionShipmentDetail constructor.
     * @param TimezoneInterface $timezone
     * @param DateTime $dateTime
     * @param File $file
     * @param GlobalHelper $globalHelper
     * @param DirectoryList $directoryList
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerSubShipmentProfileCart $loggerCart
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\HandlerSubProfileCartShipment $handlerCartCSV
     * @param ResourceConnection $resourceConnection
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $subscriptionCourseFactory
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param SubProfileNextDeliveryShipmentHelper $subProfileNextDeliveryHelper
     */
    public function __construct(
        TimezoneInterface $timezone,
        DateTime $dateTime,
        File $file,
        GlobalHelper $globalHelper,
        DirectoryList $directoryList,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerSubShipmentProfileCart $loggerCart,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\HandlerSubProfileCartShipment $handlerCartCSV,
        ResourceConnection $resourceConnection,
        \Magento\Framework\App\State $appState,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper,
        \Magento\Framework\Registry $registry,
        \Riki\SubscriptionCourse\Model\CourseFactory $subscriptionCourseFactory,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryShipmentHelper $subProfileNextDeliveryHelper
    ) {
    
        $this->_resource = $resourceConnection;
        $this->_directoryList = $directoryList;

        $this->_timezone = $timezone;
        $this->handlerCartCSV = $handlerCartCSV;
        $this->cartLogger = $loggerCart;
        $this->cartLogger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));

        $this->_file = $file;
        $this->_dataHelper = $globalHelper;
        $this->_dateTime = $dateTime;

        $this->_connection = $this->_resource->getConnection();
        $this->_connectionSales = $this->_resource->getConnection('sales');
        $this->_connectionCheckout = $this->_resource->getConnection('checkout');

        $this->appState = $appState;
        $this->_customerRepository = $customerRepository;
        $this->bundleItemsHelper = $bundleItemsHelper;
        $this->registry = $registry;
        $this->subscriptionCourseFactory = $subscriptionCourseFactory;
        $this->profileRepository = $profileRepository;
        $this->profileFactory = $profileFactory;
        $this->_subProfileNextDeliveryHelper = $subProfileNextDeliveryHelper;
        $this->subscriptionConnection = $this->_resource->getConnection('subscription');
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
        $localCsv = $this->_subProfileNextDeliveryHelper->getPathLocalSubShipmentDetailExport();
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

        $this->_subProfileNextDeliveryHelper->backupLog('bi_export_subscription_profile_next_delivery_shipment', $this->cartLogger);
        foreach ($createFileLocal as $path) {
            if (!$this->_file->isDirectory($path)) {
                if (!$this->_file->createDirectory($path)) {
                    $this->cartLogger->info(__('Can not create dir file') . $path);
                    return;
                }
            }
            if (!$this->_file->isWritable($path)) {
                $this->cartLogger->info(__('The folder have to change permission to 755') . $path);
                return;
            }
        }
    }


    /**
     * @param $subProfile
     * @param $consumerName
     * @throws \Exception
     */
    public function exportSubscriptionShipmentDetail($subProfile, $consumerName)
    {
        try {
            $this->setConsumerName($consumerName);

            $this->handlerCartCSV->setDynamicFileLog($consumerName);
            $this->cartLogger->setHandlers(['system' => $this->handlerCartCSV]);

            $this->initExport();

            $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);

            $iProfileId = $subProfile['profile_id'];

            if ($subProfile) {
                if ($subProfile['type_profile'] == \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionProfileSimulate::SUBSCRIPTION_PROFILE_MAIN) {
                    $arrExportShipmentData = $this->appState->emulateAreaCode('adminhtml', [$this, "exportSubProfileShipmentDetail"], [$subProfile]);
                    $nameCsvSubscriptionProfileShipmentDetail = 'subscription-profile-shipment-detail-' . $subProfile['profile_id'] . '-' . $this->_timezone->date()->format('Ymd') . '-' . $consumerName . '.csv';
                    $nameCsvSubscriptionProfileShipmentDetail = $baseDir . self::DS . $this->pathTmp . self::DS . $nameCsvSubscriptionProfileShipmentDetail;
                    $this->_dataHelper->writeFileLocal($nameCsvSubscriptionProfileShipmentDetail, $arrExportShipmentData);
                    if (count($arrExportShipmentData) > 1) {
                        $this->cartLogger->info('Write profile ' . $iProfileId . ' into ' . $nameCsvSubscriptionProfileShipmentDetail . ' successfully');
                    }
                } elseif ($subProfile['type_profile'] == \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionProfileSimulate::SUBSCRIPTION_PROFILE_VERSION) {
                    $arrExportShipmentVersionData = $this->appState->emulateAreaCode('adminhtml', [$this, "exportSubProfileShipmentDetail"], [$subProfile, true]);
                    $iOriginProfileId = isset($subProfile['origin_profile_id'])?$subProfile['origin_profile_id']:$subProfile['profile_id'];
                    $nameCsvSubscriptionProfileShipmentDetailVersion1 = 'subscription-profile-shipment-detail_version-1-' . $iOriginProfileId . '-' . $this->_timezone->date()->format('Ymd') . '-' . $consumerName . '.csv';
                    $nameCsvSubscriptionProfileShipmentDetailVersion1 = $baseDir . self::DS . $this->pathTmp . self::DS . $nameCsvSubscriptionProfileShipmentDetailVersion1;
                    $this->_dataHelper->writeFileLocal($nameCsvSubscriptionProfileShipmentDetailVersion1, $arrExportShipmentVersionData);
                    if (count($arrExportShipmentVersionData) > 1) {
                        $this->cartLogger->info('Write profile ' . $iProfileId . ' into ' . $nameCsvSubscriptionProfileShipmentDetailVersion1 . ' successfully');
                    }
                } elseif ($subProfile['type_profile'] == \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionProfileSimulate::SUBSCRIPTION_PROFILE_HANPUKAI) {

                    /**
                     * Export Hanpukai sequence
                     */
                    $this->appState->emulateAreaCode('adminhtml', [$this, "exportSubShipmentSequenceOnlyDetail"], [$subProfile, $baseDir]);
                }
            }
        } catch (\Exception $e) {
            $this->cartLogger->info($e->getMessage());
            throw $e;
        }
    }

    /**
     * @param $arrSubProfileId
     * @param bool|false $isExportShipmentVersion
     */
    public function exportSubProfileShipmentDetail($subProfile, $isExportShipmentVersion = false, $iDeliveryNumber = null)
    {

        $iSubProfileId = $subProfile['profile_id'];

        $simulateData  = $this->getInfoSimulateShipmentWithId($iSubProfileId, $iDeliveryNumber);

        $simulateOrderItemData    = $simulateData['orderItemData'];
        $simulateShipmentData     = $simulateData['shipmentData'];
        $simulateShipmentItemData = $simulateData['shipmentItemData'];

        $iMapVersionAndProfileId = isset($subProfile['origin_profile_id']) ? $subProfile['origin_profile_id'] : null;

        $arrExportShipmentDetailHeader = [];

        $exportDataSubShipmentDetail = [];

        $arrExportShipmentDetailHeaderPrefix = $this->getDefaultColumn();

        if ($simulateShipmentItemData) {
            foreach ($simulateShipmentItemData as $iShipmentId => $aShipmentItemData) {
                $aShipmentData = $simulateShipmentData[$iShipmentId];

                foreach ($aShipmentItemData as $aShipmentItem) {
                    $aColumnShipmentItems = array_keys($aShipmentItem);
                    foreach ($aColumnShipmentItems as $sColumnShipmentItem) {
                        if (!in_array($sColumnShipmentItem, $arrExportShipmentDetailHeader)) {
                            $arrExportShipmentDetailHeader[] = $sColumnShipmentItem;
                        }
                    }
                    break;
                }

                if (empty($arrExportShipmentDetailHeader)) {
                    continue;
                }

                //collect data for shipment detail
                $exportDataSubShipmentDetail = array_merge($exportDataSubShipmentDetail, $this->exportShipmentDetail($aShipmentData, $aShipmentItemData, $arrExportShipmentDetailHeader, $simulateOrderItemData, $isExportShipmentVersion, $iMapVersionAndProfileId, $iSubProfileId));
            }

            $exportDataSubShipmentDetail = array_merge([$arrExportShipmentDetailHeaderPrefix], $exportDataSubShipmentDetail);
        }

        return $exportDataSubShipmentDetail;
    }


    /**
     * @param $subProfile
     * @param $baseDir
     */
    public function exportSubShipmentSequenceOnlyDetail($subProfile, $baseDir)
    {
        $arrFileMake = [];

        $arrShipmentHeaderDataExport = [];
        $arrExportShipmentHeader = [];

        $iProfileId = $subProfile['profile_id'];
        $iMapVersionAndProfileId = isset($subProfile['origin_profile_id']) ? $subProfile['origin_profile_id'] : null;
        $arrDeliveryNeedExport = $subProfile['hanpukai_delivery_number'];

        foreach ($arrDeliveryNeedExport as $deliveryNumber) {
            if (!in_array($deliveryNumber, $arrFileMake)) {
                $arrFileMake[] = $deliveryNumber;
            }

            //collect data for export subscription profile cart header
            $subShipmenDetailtData = $this->exportSubProfileShipmentDetail($subProfile, false, $deliveryNumber);

            if (!empty($subShipmenDetailtData)) {
                array_shift($subShipmenDetailtData);
            }

            $arrShipmentHeaderDataExport[$deliveryNumber] = $subShipmenDetailtData;
        }

        if (empty($arrExportShipmentHeader)) {
            $arrExportShipmentHeader = $this->getDefaultColumn();
        }

        // Make file export
        foreach ($arrFileMake as $fileNumber) {
            $arrShipmentDetailData = $arrShipmentHeaderDataExport[$fileNumber];

            if ($iMapVersionAndProfileId) {
                $iProfileId = $iMapVersionAndProfileId;
            }

            $nameCsvSubShipmentHeaderHanpukaiSequence = 'subscription-profile-shipment-detail_version-' . $fileNumber . '-' . $iProfileId . '-' . $this->_timezone->date()->format('Ymd') . '-' . $this->getConsumerName() . '.csv';
            $nameCsvSubShipmentHeaderHanpukaiSequence = $baseDir . self::DS . $this->pathTmp . self::DS . $nameCsvSubShipmentHeaderHanpukaiSequence;

            $arrShipmentDetailData = array_merge([$arrExportShipmentHeader], $arrShipmentDetailData);

            if (count($arrShipmentDetailData) > 0) {
                $this->_dataHelper->writeFileLocal($nameCsvSubShipmentHeaderHanpukaiSequence, $arrShipmentDetailData);
                $this->cartLogger->info('Write profile ' . $iProfileId . ' into ' . $nameCsvSubShipmentHeaderHanpukaiSequence . ' successfully');
            }
        }
    }

    /**
     * @param $aShipmenData
     * @param $aShipmentItemData
     * @param $aShipmentItemHeader
     * @param $aShipmentOrderItem
     * @param $isExportShipmentVersion
     * @param $iMapVersionAndProfileId
     * @param $iSubProfileId
     * @return array
     */
    public function exportShipmentDetail($aShipmenData, $aShipmentItemData, $aShipmentItemHeader, $aShipmentOrderItem, $isExportShipmentVersion, $iMapVersionAndProfileId, $iSubProfileId)
    {
        if ($isExportShipmentVersion == true && $iMapVersionAndProfileId) {
            $iSubProfileId = $iMapVersionAndProfileId;
        }

        $aResult = [];

        foreach ($aShipmentItemData as $aShipmentItem) {
            $aShipmentItemDataExport = [];

            if (!empty($aShipmentItemHeader)) {
                foreach ($aShipmentItemHeader as $itemHeader) {
                    $aShipmentItemDataExport[] = isset($aShipmentItem[$itemHeader]) ? $aShipmentItem[$itemHeader] : '';
                }
                if (isset($aShipmentItem['order_item_id']) && isset($aShipmentOrderItem[$aShipmentItem['order_item_id']])) {
                    $orderItem = $aShipmentOrderItem[$aShipmentItem['order_item_id']];
                    if (isset($orderItem['parent_item_id']) and $orderItem['parent_item_id']) {
                        $iParentItemId = $aShipmentOrderItem[$orderItem['parent_item_id']]['item_id'];
                        foreach ($aShipmentOrderItem as $key => $eachOrderItem) {
                            if ($eachOrderItem['parent_item_id'] != null and $eachOrderItem['parent_item_id'] == $iParentItemId) {
                                $allChildrenItems[] = $eachOrderItem;
                            }
                        }
                        $orderItem = $this->bundleItemsHelper->reCalculateOrderItem($orderItem, $aShipmentOrderItem[$orderItem['parent_item_id']], $allChildrenItems);
                    }
                    $orderItemWithoutKey = array_values($orderItem);
                    $aShipmentItemDataExport = array_merge($aShipmentItemDataExport, $orderItemWithoutKey);
                }
                $aShipmentItemDataExport[] = isset($aShipmenData['increment_id']) ? $aShipmenData['increment_id'] : '';
                $courseData['product_catalog_discount'] = $this->calCatalogRulePrice($orderItem);
                //push data subscription course
                $courseData['hanpukai_qty'] = '';
                $courseData['course_subscription_type'] = '';
                $courseData['course_hanpukai_type'] = '';
                $courseData['course_hanpukai_maximum_order_times'] = '';
                $courseData['course_hanpukai_delivery_date_allowed'] = '';
                $courseData['course_hanpukai_delivery_date_from'] = '';
                $courseData['course_hanpukai_delivery_date_to'] = '';
                $courseData['course_hanpukai_first_delivery_date'] = '';

                if ($iSubProfileId) {
                    try {
                        $profileData = $this->profileRepository->get($iSubProfileId);
                        if ($profileData->getHanpukaiQty()) {
                            $courseData['hanpukai_qty'] = $profileData->getHanpukaiQty();
                        }

                        $subscriptionCourseId = $profileData->getCourseId();
                        if ($subscriptionCourseId) {
                            $subscriptionCourseData = $this->subscriptionCourseFactory->create()->load($subscriptionCourseId);
                            if ($subscriptionCourseData) {
                                $courseData['course_subscription_type']                  = $subscriptionCourseData->getData('subscription_type');
                                $courseData['course_hanpukai_type']                  = $subscriptionCourseData->getData('hanpukai_type');
                                $courseData['course_hanpukai_maximum_order_times']   = $subscriptionCourseData->getData('hanpukai_maximum_order_times');
                                $courseData['course_hanpukai_delivery_date_allowed'] = $subscriptionCourseData->getData('hanpukai_delivery_date_allowed');

                                if ($subscriptionCourseData->getData('hanpukai_delivery_date_from')) {
                                    $courseData['course_hanpukai_delivery_date_from']    = $this->convertDateTimeValueToConfigTimezone($subscriptionCourseData->getData('hanpukai_delivery_date_from'));
                                }

                                if ($subscriptionCourseData->getData('hanpukai_delivery_date_to')) {
                                    $courseData['course_hanpukai_delivery_date_to']      = $this->convertDateTimeValueToConfigTimezone($subscriptionCourseData->getData('hanpukai_delivery_date_to'));
                                }

                                if ($subscriptionCourseData->getData('hanpukai_first_delivery_date')) {
                                    $courseData['course_hanpukai_first_delivery_date']   = $this->convertDateTimeValueToConfigTimezone($subscriptionCourseData->getData('hanpukai_first_delivery_date'));
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $this->cartLogger->info($e->getMessage());
                    }
                }
                $aShipmentItemDataExport = array_merge($aShipmentItemDataExport, $courseData);
            }
            $aResult[] = $aShipmentItemDataExport;
        }
        return $aResult;
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
    public function getInfoSimulateShipmentWithId($subProfileId, $iDeliveryNumber = null)
    {
        $orderSimulate = null;
        $shipmentSimulate = null;

        $orderItemData = [];
        $orderAddressData = [];
        $shipmentData = [];
        $shipmentItemData = [];

        try {
            /** @var \Riki\Subscription\Model\Profile\Profile $objProfile */
            $objProfile  = $objProfile = $this->profileFactory->create()->load($subProfileId);
            ;
            $frequencyId = $objProfile->getSubProfileFrequencyID();
            // Reset data in message
            $this->registry->unregister('subscription_profile_obj');
            $this->registry->unregister(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID);
            $this->registry->unregister(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID);

            // Set data
            $this->registry->register('subscription_profile_obj', $objProfile);
            $this->registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $subProfileId);
            $this->registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);


            $orderSimulate = null;
            $shipmentSimulate = null;

            if ($iDeliveryNumber) {
                $orderSimulate = $this->getSimulateOrderData($subProfileId, $iDeliveryNumber);
                $shipmentSimulate = $this->getSimulateShipmentData($subProfileId, $iDeliveryNumber);
            } else {
                $orderSimulate = $this->getSimulateOrderData($subProfileId, 0);
                $shipmentSimulate = $this->getSimulateShipmentData($subProfileId, 0);
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
                    //get data from sales_order_item
                    $selectSalesOrderItem = $this->subscriptionConnection->select()->from([
                        'sc' => $this->subscriptionConnection->getTableName('emulator_sales_order_item_tmp')
                    ])->where('sc.item_id  IN(?)', $orderItemIds);

                    $querySalesOrderItem = $this->subscriptionConnection->query($selectSalesOrderItem);

                    while ($salesOrderItemDataRow = $querySalesOrderItem->fetch()) {
                        /*convert sales order item date time columns to config timezone*/
                        $salesOrderItemDataRow = $this->convertDateTimeColumnsToConfigTimezone(self::ORDERITEMTYPE, $salesOrderItemDataRow);

                        $orderItemData[$salesOrderItemDataRow['item_id']] = $salesOrderItemDataRow;
                    }
                }

                if (!empty($orderIds)) {
                    //get data from sales_order_address
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
                    //get data from sales shipment
                    $selectSalesShipment = $this->subscriptionConnection->select()->from([
                        'sc' => $this->subscriptionConnection->getTableName('emulator_sales_shipment_tmp')
                    ])->where('sc.entity_id  IN(?)', $iItemShipmentIds);

                    $querySalesShipment = $this->subscriptionConnection->query($selectSalesShipment);

                    while ($salesShipmentDataRow = $querySalesShipment->fetch()) {
                        /*convert sales shipment date time columns to config timezone*/
                        $salesShipmentDataRow = $this->convertDateTimeColumnsToConfigTimezone(self::SHIPMENTTYPE, $salesShipmentDataRow);

                        $shipmentData[$salesShipmentDataRow['entity_id']] = $salesShipmentDataRow;
                    }

                    //get data from sales shipment item
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
            $this->cartLogger->info($e->getMessage());
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
                $this->cartLogger->info($e->getMessage());
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
        } elseif ($type == self::SHIPMENTTYPE) {
            $dateTimeColumns = $this->getShipmentDateTimeColumns();
        } elseif ($type == self::ORDERITEMTYPE) {
            $dateTimeColumns = $this->getOrderItemDateTimeColumns();
        }

        if ($dateTimeColumns) {
            foreach ($dateTimeColumns as $cl) {
                if (!empty($object[$cl])) {
                    $object[$cl] = $this->_dateTime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($object[$cl], 2, 2));
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
            'updated_date', 'disengagement_date'
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
            'created_at', 'updated_at', 'shipment_date', 'payment_date', 'export_sap_date', 'nestle_payment_receive_date'
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
            'created_at', 'updated_at'
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
    public function getDefaultColumn(){

        if (!empty($this->defaultColumn)) {
            return $this->defaultColumn;
        }
        $columnShipmentItemHeader = [];

        /*merge column from sales_shipment_item*/
        $columnShipmentItem             = $this->_connectionSales->describeTable('sales_shipment_item');
        $aColumnShipmentItemPrefix = [];
        foreach ($columnShipmentItem as $sColumnShipmentItem => $value) {
            $aColumnShipmentItemPrefix[] = 'shipment_item.' . $sColumnShipmentItem;
        }

        $columnShipmentItemHeader = array_merge($columnShipmentItemHeader, $aColumnShipmentItemPrefix);

        /*merge column from sales_order_item*/
        $columnSalesOrderItem  = $this->_connectionSales->describeTable('sales_order_item');
        $aColumnSalesOrderItemPrefix = [];
        foreach ($columnSalesOrderItem as $sColumnSalesOrderItem => $value) {
            $aColumnSalesOrderItemPrefix[] = 'shipment_item.order_item_' . $sColumnSalesOrderItem;
        }

        $columnShipmentItemHeader = array_merge($columnShipmentItemHeader, $aColumnSalesOrderItemPrefix);

        //add more caculate field
        $columnShipmentItemHeader[] = 'shipment_item.shipment_increment_id';
        $columnShipmentItemHeader[] = 'shipment_item.product_discount_amount';
        $columnShipmentItemHeader[] = 'subscription_profile.hanpukai_qty';
        $columnShipmentItemHeader[] = 'subscription_profile.course_subscription_type';
        $columnShipmentItemHeader[] = 'subscription_profile.course_hanpukai_type';
        $columnShipmentItemHeader[] = 'subscription_profile.course_hanpukai_maximum_order_times';
        $columnShipmentItemHeader[] = 'subscription_profile.course_hanpukai_delivery_date_allowed';
        $columnShipmentItemHeader[] = 'subscription_profile.course_hanpukai_delivery_date_from';
        $columnShipmentItemHeader[] = 'subscription_profile.course_hanpukai_delivery_date_to';
        $columnShipmentItemHeader[] = 'subscription_profile.course_hanpukai_first_delivery_date';

        $this->defaultColumn = $columnShipmentItemHeader;

        return $columnShipmentItemHeader;
    }

    /**
     * get price of catalog rule on product
     *
     * @param $item
     * @return int
     */
    public function calCatalogRulePrice($item) {
        if ((float)$item['rule_price']) {
            return floor(($item['original_price'] * (1 + $item['tax_percent'] / 100)) - $item['price_incl_tax']);
        } else {
            return 0;
        }
    }

    /**
     * @param $shipmentSimulate
     * @param $profileId
     * @param int $iDeliveryNumber
     */
    public function addSimulateShipmentData($shipmentSimulate, $profileId, $iDeliveryNumber = 0){
        $this->aSimulateSimulateData[$profileId][$iDeliveryNumber] = $shipmentSimulate;
    }

    /**
     * @param $profileId
     * @param $iDeliveryNumber
     * @return mixed
     */
    public function getSimulateShipmentData($profileId, $iDeliveryNumber){

        if (isset($this->aSimulateSimulateData[$profileId][$iDeliveryNumber])) {
            return $this->aSimulateSimulateData[$profileId][$iDeliveryNumber];
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
