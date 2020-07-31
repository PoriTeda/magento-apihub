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
use Magento\Framework\App\Filesystem\DirectoryList;
use Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerCSV;
use Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryOrderHelper;
use Magento\Framework\App\ResourceConnection;
use Riki\Subscription\Helper\Order\Simulator as OrderSimulator;
use Riki\SubscriptionCourse\Model\Course as CourseModel;
use Riki\SubscriptionCourse\Model\ResourceModel\Course as ResourceModelCourse;

use Psr\Log\LoggerInterface;

class SubscriptionProfile
{
    const PROFILETYPE = 'subscription_profile';
    const COURSETYPE = 'subscription_course';
    const ORDERITEMTYPE = 'order_item';

    const DEFAULT_LOCAL_SAVE = 'var/bi_subscription_profile';

    const SUBSCRIPTION_PROFILE_MAIN = 1;
    const SUBSCRIPTION_PROFILE_VERSION = 2;
    const SUBSCRIPTION_PROFILE_VERSION_OUT_OF_DATE = 3;

    const DS = '/';

    /**
     * @var DirectoryList
     */
    protected $_directoryList;
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
     * @var DateTime
     */
    protected $_dateTime;

    /**
     * @var LoggerCSV
     */
    protected $logger;

    /**
     * @var SubProfileNextDeliveryHelper
     */
    protected $_subProfileNextDeliveryHelper;

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var OrderSimulator
     */
    protected $_simulator;

    /**
     * @var CourseModel
     */
    protected $_courseModel;

    /**
     * @var ResourceModelCourse
     */
    protected $_resourceCourseModel;

    /**
     * @var ProductCartModel
     */
    protected $productCartFactory;

    /**
     * @var array
     */
    protected $_aColumnOrderItems = [];

    /**
     * @var \Riki\Customer\Api\ShoshaRepositoryInterface
     */
    protected $shoshaRepository;

    /**
     * @var string
     */
    protected $pathTmp;

    /**
     * @var string
     */
    protected $_path;

    /**
     * @var string
     */
    protected $consumerName;

    /**
     * @var int
     */
    protected $hanpukaiOrderTime = 0;

    /**
     * @var \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee
     */
    protected $_paymentFee;

    /**
     * @var \Bluecom\PaymentFee\Helper\Data
     */
    protected $paymentFeeHelper;

    /**
     * SubscriptionProfile constructor.
     * @param DirectoryList $directoryList
     * @param TimezoneInterface $timezone
     * @param File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param GlobalHelper $globalHelper
     * @param DateTime $dateTime
     * @param LoggerCSV $logger
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\HandlerCSV $handlerCSV
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\Registry $registry
     * @param OrderSimulator\Proxy $orderSimulator
     * @param \Magento\Customer\Model\Customer $modelCustomer
     * @param \Magento\Customer\Model\CustomerFactory $modelCustomerFactory
     * @param \Magento\Customer\Model\Address $modelCustomerAddress
     * @param \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory
     * @param CourseModel $courseModel
     * @param ResourceModelCourse $resourceModelCourse
     * @param SubProfileNextDeliveryOrderHelper $subProfileNextDeliveryHelper
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Customer\Api\ShoshaRepositoryInterface $shoshaRepository
     * @param ResourceConnection $resourceConnection
     * @param \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee $paymentFee
     * @param \Bluecom\PaymentFee\Helper\Data $paymentFeeHelper
     */
    public function __construct(
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\File\Csv $csv,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper $globalHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerCSV $logger,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\HandlerCSV $handlerCSV,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Helper\Order\Simulator\Proxy $orderSimulator,
        \Magento\Customer\Model\Customer $modelCustomer,
        \Magento\Customer\Model\CustomerFactory $modelCustomerFactory,
        \Magento\Customer\Model\Address $modelCustomerAddress,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory,
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $resourceModelCourse,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryOrderHelper $subProfileNextDeliveryHelper,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Customer\Api\ShoshaRepositoryInterface $shoshaRepository,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee $paymentFee,
        \Bluecom\PaymentFee\Helper\Data $paymentFeeHelper
    )
    {
        $this->_directoryList = $directoryList;
        $this->_timezone = $timezone;
        $this->_file = $file;
        $this->_csv = $csv;
        $this->_dataHelper = $globalHelper;
        $this->_dateTime = $dateTime;

        $this->logger = $logger;
        $this->logger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->handlerCSV = $handlerCSV;

        $this->appState = $appState;
        $this->registry = $registry;

        $this->_simulator = $orderSimulator;

        $this->modelCustomer = $modelCustomer;
        $this->modelCustomerFactory = $modelCustomerFactory;
        $this->modelCustomerAddress = $modelCustomerAddress;

        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shoshaRepository = $shoshaRepository;

        $this->_productCartFactory = $productCartFactory;
        $this->_courseModel = $courseModel;
        $this->_resourceCourseModel = $resourceModelCourse;
        $this->_subProfileNextDeliveryHelper = $subProfileNextDeliveryHelper;

        $this->_resource = $resourceConnection;
        $this->_connection = $this->_resource->getConnection();
        $this->_connectionSales = $this->_resource->getConnection('sales');
        $this->_connectionCheckout = $this->_resource->getConnection('checkout');
        $this->_paymentFee = $paymentFee;
        $this->paymentFeeHelper = $paymentFeeHelper;

    }

    /**
     * @return null|void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function initExportProfile()
    {
        if (!$this->_dataHelper->isEnable()) {
            return null;
        }
        $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $localCsv = $this->_dataHelper->getConfig(\Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryOrderHelper::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_LOCAL_PATH);

        if (!$localCsv) {
            $createFileLocal[] = $baseDir . self::DS . self::DEFAULT_LOCAL_SAVE;
            $createFileLocal[] = $baseDir . self::DS . self::DEFAULT_LOCAL_SAVE . '_tmp';
            $this->_path = self::DEFAULT_LOCAL_SAVE;
            $this->pathTmp = self::DEFAULT_LOCAL_SAVE . '_tmp';
        } else {
            rtrim($localCsv, self::DS);
            $createFileLocal[] = $baseDir . self::DS . $localCsv;
            $createFileLocal[] = $baseDir . self::DS . $localCsv . '_tmp';
            $this->_path = $localCsv;
            $this->pathTmp = $localCsv . '_tmp';
        }

        $this->_subProfileNextDeliveryHelper->backupLog('bi_export_subscription_profile_next_delivery_order', $this->logger);

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
     * @param \Riki\ThirdPartyImportExport\Api\ExportNextDelivery\ProfileItemsInterface $message
     * @param $consumerName
     * @return void
     * @throws \Exception
     */
    public function exportSubscriptionProfile(\Riki\ThirdPartyImportExport\Api\ExportNextDelivery\ProfileItemsInterface $message, $consumerName)
    {
        try {

            $this->hanpukaiOrderTime = 0;

            if(!$this->registry->registry('bi_export_subscription')){
                $this->registry->register('bi_export_subscription',true);
            }

            $this->setConsumerName($consumerName);

            $this->handlerCSV->setDynamicFileLog($consumerName);
            $this->logger->setHandlers(['system' => $this->handlerCSV]);

            $this->initExportProfile();

            $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);

            $profileId = null;
            foreach ($message->getItems() as $profileObject) {
                $profileId = $profileObject->getProfileId();
            }

            //handle for one message.
            $arrMapVersionIdToProfileId = $this->getMapVersionAndProfile($profileId);

            $arrExportDataSubProfile = [];
            $arrExportDataSubProfileVersion = [];

            $arrProfileIdHanpukaiSequence = [];

            $selectProfile = $this->_connectionSales->select()->from([
                'sp' => $this->_connectionSales->getTableName('subscription_profile')
            ])->where('profile_id = ?', $profileId);

            $collectionProfileQuery = $this->_connectionSales->query($selectProfile);

            $subProfile = null;
            while ($profileItemData = $collectionProfileQuery->fetch()) {
                $subProfile = $profileItemData;
            }

            if ($subProfile) {

                $aColumnCustomers = [];
                $aColumnCustomerPlains = [];
                $aColumnCustomerShosha = [];
                $aColumnCustomerAttributes = $this->modelCustomer->getAttributes();
                $aRemoveColumns = ['password_hash'];
                if ($aColumnCustomerAttributes) {
                    foreach ($aColumnCustomerAttributes as $sColumnCustomer => $value) {
                        if (!in_array($sColumnCustomer, $aRemoveColumns)) {
                            $aColumnCustomers[] = 'subscription_profile.customer_' . $sColumnCustomer;
                            $aColumnCustomerPlains[] = $sColumnCustomer;
                        }
                    }

                    $aColumnCustomerShosha = $this->getShoshaCustomerColumn();
                    foreach ($aColumnCustomerShosha as $sColumn) {
                        $aColumnCustomers[] = 'subscription_profile.customer_' . $sColumn;
                    }
                }

                $aColumnCustomerAddresses = [];
                $aColumnCustomerAddressPlains = [];
                $aColumnCustomerAddressAttributes = $this->modelCustomerAddress->getAttributes();
                if ($aColumnCustomerAddressAttributes) {
                    foreach ($aColumnCustomerAddressAttributes as $sColumnCustomerAddress => $value) {
                        $aColumnCustomerAddresses[] = 'subscription_profile.billing_address_' . $sColumnCustomerAddress;
                        $aColumnCustomerAddressPlains[] = $sColumnCustomerAddress;
                    }
                }

                $aColumns = $this->getSubscriptionProfileExportColumns(array_merge($aColumnCustomers, $aColumnCustomerAddresses));

                $arrExportHeader[] = $aColumns;

                if ($this->isHanpukaiSequenceProfile($subProfile['course_id']) == true) {
                    $arrProfileIdHanpukaiSequence[$subProfile['profile_id']] = $subProfile;
                } else {
                    $isSubProfileVersion = $this->checkProfileIsVersion($subProfile['profile_id']);

                    if ($isSubProfileVersion == self::SUBSCRIPTION_PROFILE_MAIN) {

                        //collect data for export subscription profile header
                        $subProfile = $this->getSubProfileData($aColumns, $subProfile);
                        $subProfile = $this->getSubProfileCustomer($aColumnCustomerPlains, $aColumnCustomerShosha, $subProfile);
                        $subProfile = $this->appState->emulateAreaCode('adminhtml', [$this, "getSubProfileCustomerAddress"], array($aColumnCustomerAddressPlains, $subProfile));
                        $arrExportDataSubProfile[] = $subProfile;

                    } elseif ($isSubProfileVersion == self::SUBSCRIPTION_PROFILE_VERSION) {

                        //collect data for export subscription profile header
                        $subProfile = $this->getSubProfileData($aColumns, $subProfile, $arrMapVersionIdToProfileId);
                        $subProfile = $this->getSubProfileCustomer($aColumnCustomerPlains, $aColumnCustomerShosha, $subProfile);
                        $subProfile = $this->appState->emulateAreaCode('adminhtml', [$this, "getSubProfileCustomerAddress"], array($aColumnCustomerAddressPlains, $subProfile));
                        $arrExportDataSubProfileVersion[] = $subProfile;
                    }
                }

                // end loop
                $arrExportDataSubProfile = array_merge($arrExportHeader, $arrExportDataSubProfile);
                $arrExportDataSubProfileVersion = array_merge($arrExportHeader, $arrExportDataSubProfileVersion);

                /*write file to local*/
                $nameCsvSubscriptionProfile = 'subscription-profile-' . $this->_timezone->date()->format('Ymd') . '-' . $consumerName . '.csv';
                $nameCsvSubscriptionProfileVersion1 = 'subscription_profile_version-1-' . $this->_timezone->date()->format('Ymd') . '-' . $consumerName . '.csv';

                $nameCsvSubscriptionProfile = $baseDir . self::DS . $this->pathTmp . self::DS . $nameCsvSubscriptionProfile;
                $this->_dataHelper->writeFileLocal($nameCsvSubscriptionProfile, $arrExportDataSubProfile);
                if (count($arrExportDataSubProfile) > 1) {
                    $this->logger->info('Write profile ' . $profileId . ' into ' . $nameCsvSubscriptionProfile . ' successfully');
                }


                $nameCsvSubscriptionProfileVersion1 = $baseDir . self::DS . $this->pathTmp . self::DS . $nameCsvSubscriptionProfileVersion1;
                $this->_dataHelper->writeFileLocal($nameCsvSubscriptionProfileVersion1, $arrExportDataSubProfileVersion);
                if (count($arrExportDataSubProfileVersion) > 1) {
                    $this->logger->info('Write profile ' . $profileId . ' into ' . $nameCsvSubscriptionProfileVersion1 . ' successfully');
                }

                /**
                 * Export Hanpukai sequence
                 */

                $this->exportHanpukaiSequenceSubProfileOnly($arrProfileIdHanpukaiSequence, $arrExportHeader, $aColumnCustomerPlains, $aColumnCustomerShosha, $aColumnCustomerAddressPlains, $baseDir);
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            throw $e;
        }
    }


    /**
     * getSubProfileCustomer
     *
     * @param $aColumnCustomerPlains
     * @param $orderExport
     * @return mixed
     */
    public function getSubProfileCustomer($aColumnCustomerPlains, $aColumnCustomerShosha, $subprofile)
    {

        $customerSubProfile = null;

        if (isset($subprofile['customer_id']) && $subprofile['customer_id']) {
            $customerSubProfile = $this->modelCustomerFactory->create()->load($subprofile['customer_id']);
        }

        if ($customerSubProfile) {
            foreach ($aColumnCustomerPlains as $sColumnsCustomer) {
                if (null != $customerSubProfile->getData($sColumnsCustomer)) {
                    $subprofile[] = $customerSubProfile->getData($sColumnsCustomer);
                } else {
                    $subprofile[] = '';
                }
            }

            //push data shosha code
            $shoshaBusinessCode = $customerSubProfile->hasData('shosha_business_code') ? $customerSubProfile->getData('shosha_business_code') : '';
            $aSubShoshaData = [];
            if ($shoshaBusinessCode) {
                try {
                    $filter = $this->searchCriteriaBuilder->addFilter('shosha_business_code', $shoshaBusinessCode);
                    $aShoshaCustomerItems = $this->shoshaRepository->getList($filter->create());
                    if ($aShoshaCustomerItems->getTotalCount()) {
                        $aShoshaCustomerData = $aShoshaCustomerItems->getItems();
                        foreach ($aShoshaCustomerData as $aShoshaItem) {

                            foreach ($aColumnCustomerShosha as $sColumnShosha) {
                                if ($aShoshaItem->hasData($sColumnShosha)) {
                                    $aSubShoshaData[] = $aShoshaItem->getData($sColumnShosha);
                                } else {
                                    $aSubShoshaData[] = '';
                                }
                            }
                            $subprofile = array_merge($subprofile, $aSubShoshaData);
                            break;
                        }
                    }

                } catch (\Exception $e) {
                    $this->logger->info($e->getMessage());
                }

            }

            if (empty($aSubShoshaData)) {
                foreach ($aColumnCustomerShosha as $sColumnShosha) {
                    $subprofile[] = '';
                }
            }

        } else {

            foreach (array_merge($aColumnCustomerPlains, $aColumnCustomerShosha) as $sColumnsCustomer) {
                $subprofile[] = '';
            }
        }

        return $subprofile;
    }

    /**
     * getSubProfileCustomer
     *
     * @param $aColumnCustomerPlains
     * @param $orderExport
     * @return mixed
     */
    public function getSubProfileCustomerAddress($aColumnCustomerAddressesPlain, $subprofile)
    {

        $iBillingAddressId = $this->getBillingAddressIdProfileCart($subprofile['profile_id']);

        if ($iBillingAddressId) {
            $customerSubProfile = $this->modelCustomerAddress->load($iBillingAddressId);

            foreach ($aColumnCustomerAddressesPlain as $sColumnsCustomerAddress) {
                if (null != $customerSubProfile->getData($sColumnsCustomerAddress)) {
                    $subprofile[] = $customerSubProfile->getData($sColumnsCustomerAddress);
                } else {
                    $subprofile[] = '';
                }
            }
        } else {

            foreach ($aColumnCustomerAddressesPlain as $sColumnsCustomerAddress) {
                $subprofile[] = '';
            }
        }

        return $subprofile;
    }

    /**
     * getSubscriptionProfileExportColumns
     *
     * @return array
     */
    public function getSubscriptionProfileExportColumns($aAdditionColumns = [])
    {

        $aColumns = [];

        $aColumnSubscriptionProfiles = $this->_connectionSales->describeTable($this->_connectionSales->getTableName('subscription_profile'));

        foreach ($aColumnSubscriptionProfiles as $sColumnSubscriptionProfile => $value) {
            $aColumns[] = 'subscription_profile.' . $sColumnSubscriptionProfile;
        }

        $aColumnSubscriptionCourses = $this->_connectionSales->describeTable($this->_connectionSales->getTableName('subscription_course'));

        foreach ($aColumnSubscriptionCourses as $sColumnSubscriptionCourse => $value) {
            $aColumns[] = 'subscription_profile.course_' . $sColumnSubscriptionCourse;
        }

        $aColumns[] = 'subscription_profile.last_order_date';

        /*
         * Ticket RIKI-9397
         * Add value to bi export
         */
        $aColumns[] = 'subscription_profile.cod_payment_fee';
        $aColumns[] = 'subscription_profile.tax_rate';


        $aColumns = array_merge($aColumns, $aAdditionColumns);

        return $aColumns;
    }

    /**
     * getSubProfileData
     *
     * @return bool|\Magento\Sales\Api\Data\OrderInterface[]
     */
    public function getSubProfileData($aColumns = [], $subProfile, $arrMapVersionIdToProfileId = [])
    {

        $selectSubProfiles = $this->_connectionSales->select()->from(
            $this->_connectionSales->getTableName('subscription_profile')
        )->where($this->_connectionSales->getTableName('subscription_profile') . '.profile_id = ?', $subProfile['profile_id']);

        $querySubProfiles = $this->_connectionSales->query($selectSubProfiles);

        while ($subProfileData = $querySubProfiles->fetch()) {

            /*convert subscription profile datetime columns to config timezone*/
            $subProfileData = $this->convertDateTimeColumnsToConfigTimezone(self::PROFILETYPE, $subProfileData);

            if (count($arrMapVersionIdToProfileId)) {
                $subProfileData['profile_id'] = $arrMapVersionIdToProfileId[$subProfileData['profile_id']];
            }
            //subscription course
            $selectSubProfileCourse = $this->_connectionSales->select()->from(
                $this->_connectionSales->getTableName('subscription_course')
            )->where($this->_connectionSales->getTableName('subscription_course') . '.course_id = ?', $subProfileData['course_id']);


            $querySubProfileCourse = $this->_connectionSales->query($selectSubProfileCourse);

            $subProfileCourseData = [];
            while ($subProfileCourse = $querySubProfileCourse->fetch()) {

                /*convert subscription course date time columns to config timezone */
                $subProfileCourse = $this->convertDateTimeColumnsToConfigTimezone(self::COURSETYPE, $subProfileCourse);

                foreach ($subProfileCourse as $sColumn => $sValue) {
                    $subProfileCourseData[] = $sValue;
                }
            }

            if (!count($subProfileCourseData)) {
                foreach ($aColumns as $sColumn) {
                    if (strpos($sColumn, 'subscription_profile.course_') !== false) {
                        $subProfileCourseData[] = '';
                    }
                }
            }

            if(isset($this->hanpukaiOrderTime) && $this->hanpukaiOrderTime > 0){
                $subProfileData['order_times'] = $this->hanpukaiOrderTime;
            }
            else
            if(isset($subProfileData['order_times'])){
                $subProfileData['order_times'] = $subProfileData['order_times'] + 1;
            }

            $subProfileData = array_merge($subProfileData, $subProfileCourseData);

            $subProfileData = $this->getAdditionalLastOrderCreationDate($subProfileData);

            /*
             * Ticket RIKI-9397
             * Add value to bi export
             */
            $subProfileData = $this->getAdditionalForPaymentCommit($subProfileData);

            return $subProfileData;
        }
        return [];
    }

    /**
     * @param $subProfileData
     * @return array
     */
    public function getAdditionalLastOrderCreationDate($subProfileData)
    {

        $order = $this->_subProfileNextDeliveryHelper->getLastOrder($subProfileData);

        $sOrderCreationDate = '';

        if ($order) {
            $sOrderCreationDate = $this->convertDateTimeValueToConfigTimezone($order->getData('created_at'));
        }

        $subProfileData[] = $sOrderCreationDate;

        return $subProfileData;
    }

    /**
     * Get payment fee,tax rate
     *
     * @param $subProfileData
     * @return array
     */
    public function getAdditionalForPaymentCommit($subProfileData)
    {

        $codPaymentFee    = '';
        $taxRate          = '';

        $codPaymentMethod = \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE;
        if(isset($subProfileData['payment_method']) && $subProfileData['payment_method']== $codPaymentMethod) {

            //load code payment fee
            $dataPaymentFee = $this->_paymentFee->loadPaymentCode($codPaymentMethod);
            if(isset($dataPaymentFee['fixed_amount'])) {
                $codPaymentFee = $dataPaymentFee['fixed_amount'];
            }

            //load tax zones and rates
            $paymentRate = $this->paymentFeeHelper->getPaymentTaxRate();
            if ($paymentRate > 0) {
                $taxRate = ($paymentRate / 100);
            }
        }

        $subProfileData[] = $codPaymentFee;
        $subProfileData[] = $taxRate;
        return $subProfileData;
    }

    /**
     * GetBillingAddressIdProfileCart
     *
     * @param $iProfileId
     * @return mixed
     */
    public function getBillingAddressIdProfileCart($iProfileId)
    {
        $profileCartItems = $this->_productCartFactory->create()->getCollection()->addFieldToFilter('profile_id', $iProfileId, true);
        $profileCartFirstItem = null;
        foreach ($profileCartItems as $profileCartItem) {
            $profileCartFirstItem = $profileCartItem;
            break;
        }
        if ($profileCartFirstItem) {
            return $profileCartFirstItem->getBillingAddressId();
        }

        return null;
    }

    /**
     * Export for hanpukai sequence
     *
     * @param $arrProfileIdDetail
     */
    public function exportHanpukaiSequenceSubProfileOnly($arrProfileIdDetail, $arrExportProfileHeader, $aColumnCustomerPlains, $aColumnCustomerShosha, $aColumnCustomerAddressesPlain, $baseDir)
    {
        if (count($arrProfileIdDetail)) {

            $arrFileMake = [];
            $arrProfileDataExport = [];

            foreach ($arrProfileIdDetail as $profileId => $profileDataDetail) {
                $courseModel = $this->_courseModel->load($profileDataDetail['course_id']);
                $arrHanpukaiSequenceProductData = $this->_resourceCourseModel->getHanpukaiSequenceProductsData($courseModel);
                $arrAllDelivery = $this->howManyDelivery($arrHanpukaiSequenceProductData);
                $arrDeliveryNeedExport = $this->deleteDeliveredHanpukaiSequence($profileDataDetail['order_times'], $arrAllDelivery);
                foreach ($arrDeliveryNeedExport as $deliveryNumber) {
                    if (!in_array($deliveryNumber, $arrFileMake)) {
                        $arrFileMake[] = $deliveryNumber;
                    }
                    $this->hanpukaiOrderTime = $deliveryNumber;
                    //collect data for export subscription profile cart header
                    $subProfile = $this->getSubProfileData($arrExportProfileHeader[0], $profileDataDetail);
                    $subProfile = $this->getSubProfileCustomer($aColumnCustomerPlains, $aColumnCustomerShosha, $subProfile);
                    $subProfile = $this->appState->emulateAreaCode('adminhtml', [$this, "getSubProfileCustomerAddress"], array($aColumnCustomerAddressesPlain, $subProfile));

                    $arrProfileDataExport[$deliveryNumber][$profileId] = $subProfile;
                }
            }

            if (empty($arrFileMake)) {
                $profileId = key(reset($arrProfileIdDetail));
            }

            // Make file export
            foreach ($arrFileMake as $fileNumber) {

                $arrExportData = [];

                $arrExportForThisFile = $arrProfileDataExport[$fileNumber];

                $iProfileId = 0;
                //collect data for export subscription profile header
                if (!empty($arrExportForThisFile)) {
                    foreach ($arrExportForThisFile as $profileId => $data) {
                        $arrExportData[] = $data;
                        $iProfileId = $profileId;
                    }
                }

                $arrExportData = array_merge($arrExportProfileHeader, $arrExportData);

                if (!empty($arrExportData)) {

                    $nameCsvSubProfileHanpukaiSequence = 'subscription_profile_version-' . $fileNumber . '-' . $this->_timezone->date()->format('Ymd') . '-' . $this->getConsumerName() . '.csv';
                    $nameCsvSubProfileHanpukaiSequence = $baseDir . self::DS . $this->pathTmp . self::DS . $nameCsvSubProfileHanpukaiSequence;

                    $this->_dataHelper->writeFileLocal($nameCsvSubProfileHanpukaiSequence, $arrExportData);
                    if (count($arrExportData) > 1) {
                        $this->logger->info('Write profile ' . $iProfileId . ' into ' . $nameCsvSubProfileHanpukaiSequence . ' successfully');
                    }
                }
            }
        }
    }

    /**
     * How many delivery hanpukai sequence
     *
     * @param $arrHanpukaiSequenceProductData
     *
     * @return array
     */
    public function howManyDelivery($arrHanpukaiSequenceProductData)
    {
        $arrDelivery = array();
        foreach ($arrHanpukaiSequenceProductData as $productId => $detail) {
            if (!in_array($detail['delivery_number'], $arrDelivery)) {
                $arrDelivery[] = $detail['delivery_number'];
            }
        }
        return $arrDelivery;
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
     * Delete Delivery Hanpukai Sequence
     *
     * @param $deliveredNumber
     * @param $arrHanpukaiSequenceDelivery
     *
     * @return array
     *
     */
    public function deleteDeliveredHanpukaiSequence($deliveredNumber, $arrHanpukaiSequenceDelivery)
    {
        $count = count($arrHanpukaiSequenceDelivery);
        $arrResult = [];
        for ($i = 0; $i < $count; $i++) {
            if ($arrHanpukaiSequenceDelivery[$i] > $deliveredNumber) {
                $arrResult[] = $arrHanpukaiSequenceDelivery[$i];
            }
        }
        return $arrResult;
    }

    /**
     * Get arr profile and version to map
     *
     * @return array()
     */
    public function getMapVersionAndProfile($profileId)
    {
        $arrResult = [];

        if ($profileId) {
            $profileVersion = $this->_connectionSales->select()->from([
                'sp_version' => $this->_connectionSales->getTableName('subscription_profile_version')
            ])->where('moved_to = ?', $profileId);

            $collectionVersionQuery = $this->_connectionSales->query($profileVersion);
            while ($profileVersionData = $collectionVersionQuery->fetch()) {
                if (!empty($profileVersionData)) {
                    $arrResult[$profileVersionData['moved_to']] = $profileVersionData['rollback_id'];
                }
            }
        }

        return $arrResult;
    }

    /**
     * @param $profileId
     * @return int (1 subscription profile version, 2 not subscription profile version, 3 subscription profile version out of date)
     */
    public function checkProfileIsVersion($profileId)
    {
        $profileVersion = $this->_connectionSales->select()->from([
            'sp_version' => $this->_connectionSales->getTableName('subscription_profile_version')
        ])->where('moved_to = ?', $profileId);

        $collectionVersionQuery = $this->_connectionSales->query($profileVersion);

        while ($profileVersionData = $collectionVersionQuery->fetch()) {
            if (!empty($profileVersionData)) {
                if (isset($profileVersionData['status']) && $profileVersionData['status'] == 0) {
                    return self::SUBSCRIPTION_PROFILE_VERSION_OUT_OF_DATE;

                } else {
                    return self::SUBSCRIPTION_PROFILE_VERSION;
                }
            }
        }
        return self::SUBSCRIPTION_PROFILE_MAIN;
    }

    /**
     * @param $profileId
     * @return bool
     */
    public function checkHasProfileVersion($profileId)
    {
        $profileVersion = $this->_connectionSales->select()->from([
            'sp_version' => $this->_connectionSales->getTableName('subscription_profile_version')
        ])->where('rollback_id = ?', $profileId);

        $collectionVersionQuery = $this->_connectionSales->query($profileVersion);

        while ($profileVersionData = $collectionVersionQuery->fetch()) {
            if (isset($profileVersionData) && $profileVersionData['status'] > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param $profileId
     * @return bool
     */
    public function isHanpukaiSequenceProfile($profileId)
    {
        $courseModel = $this->_courseModel->load($profileId);
        if ($courseModel instanceof \Riki\SubscriptionCourse\Model\Course) {
            if ($courseModel->getData('hanpukai_type') == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_SEQUENCE) {
                return true;
            }
        }
        return false;
    }

    /**
     * getShoshaCustomerColumn
     *
     * @return array
     */
    public function getShoshaCustomerColumn()
    {
        $sColumns = array_keys($this->_connection->describeTable($this->_connection->getTableName('riki_shosha_business_code')));
        $sColumnsRemove = ['id', 'shosha_business_code', 'orm_rowid', 'updated_at', 'created_at', 'is_bi_exported', 'is_cedyna_exported'];
        foreach ($sColumnsRemove as $sColumnRemove) {
            if (($key = array_search($sColumnRemove, $sColumns)) !== false) {
                unset($sColumns[$key]);
            }
        }

        return $sColumns;
    }

    /**
     * Convert datetime columns to config timezone for
     *      subscription_profile/subscription_course/sales_order_item object
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
        } else if ($type == self::COURSETYPE) {
            $dateTimeColumns = $this->getSubscriptionCourseDateTimeColumns();
        } else if ($type == self::ORDERITEMTYPE) {
            $dateTimeColumns = $this->getOrderItemDateTimeColumns();
        }

        if ($dateTimeColumns) {
            foreach ($dateTimeColumns as $cl) {
                if (!empty($object[$cl])) {
                    $object[$cl] = $this->convertDateTimeValueToConfigTimezone($object[$cl]);
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
    public function getSubscriptionCourseDateTimeColumns()
    {
        return [
            'created_date', 'updated_date', 'launch_date', 'close_date', 'hanpukai_delivery_date_from', 'hanpukai_delivery_date_to', 'hanpukai_first_delivery_date'
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
}
