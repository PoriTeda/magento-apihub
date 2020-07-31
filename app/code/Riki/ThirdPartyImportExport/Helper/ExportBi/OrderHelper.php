<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

use Magento\Framework\Exception\NoSuchEntityException;
use Riki\ThirdPartyImportExport\Helper\RedisErrorEntity;

class OrderHelper extends \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ExportHelper
{
    const CONVERTSALESORDER = 1;
    const CONVERTSALESORDERITEM = 2;
    const CONVERTSALESORDERPAYMENT = 3;

    const CONFIG_DEFAULT_LIMIT = 2000;
    const CONFIG_LIMIT = 'di_data_export_setup/data_cron_order/limit';

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $_shipmentRepository;

    /**
     * @var \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface
     */
    protected $_orderStatusHistoryFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory
     */
    protected $orderPaymentCollection;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    protected $_orderPaymentTransactionFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $modelCustomerFactory;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_modelCustomer;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Bluecom\PaymentFee\Helper\Data
     */
    protected $paymentFeeHelper;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $_filterBuilder;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $_profileRepository;

    /**
     * @var \Riki\Preorder\Model\OrderPreorder
     */
    protected $orderPreorder;

    /**
     * @var \Riki\Customer\Api\ShoshaRepositoryInterface
     */
    protected $shoshaRepository;

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper
     */
    protected $bundleItemsHelper;

    /**
     * Default connection
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * Sales connection
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connectionSales;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $subscriptionCourseFactory;

    protected $customers = [];

    protected $profileCourseMapping = [];

    protected $preOrderData = [];

    protected $historyData = [];

    protected $paymentDate = [];

    protected $authorizeData = [];

    protected $paygentOrder = [];

    protected $_allColumnSap = [];
    /*list columns which data type is datetime or timestamp, table sales_order*/
    protected $_orderDateTimeColumns;
    /*list columns which data type is datetime or timestamp, table sales_order_item*/
    protected $_orderItemDateTimeColumns;
    /*list columns which data type is datetime or timestamp, table sales_order_payment*/
    protected $_orderPaymentDateTimeColumns;
    /*current version for next schedule*/
    protected $_currentVersion;
    /*array of entity id which is exported*/
    protected $_exportEntityList = [];

    /*export data for header file*/
    protected $_headerData = [];
    /*export data for detail file*/
    protected $_detailData = [];

    protected $originalHeaderData = [];

    protected $originalDetailData = [];

    /**
     * OrderHelper constructor.
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $historyFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $orderPaymentCollection
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $orderPaymentTransactionFactory
     * @param \Magento\Customer\Model\CustomerFactory $modelCustomerFactory
     * @param \Magento\Customer\Model\Customer $modelCustomer
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Bluecom\PaymentFee\Helper\Data $paymentFeeHelper
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param \Riki\Sales\Model\OrderPayshipStatus $orderPayshipStatus
     * @param \Riki\Preorder\Model\OrderPreorder $orderPreorder
     * @param \Riki\Customer\Api\ShoshaRepositoryInterface $shoshaRepository
     * @param GlobalHelper\SftpExportHelper $sftpHelper
     * @param GlobalHelper\FileExportHelper $fileHelper
     * @param GlobalHelper\ConfigExportHelper $configHelper
     * @param GlobalHelper\EmailExportHelper $emailHelper
     * @param GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper
     * @param GlobalHelper\BundleItemsHelper $bundleItemsHelper
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $subscriptionCourseFactory
     */
    public function __construct(
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $historyFactory,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $orderPaymentCollection,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $orderPaymentTransactionFactory,
        \Magento\Customer\Model\CustomerFactory $modelCustomerFactory,
        \Magento\Customer\Model\Customer $modelCustomer,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Bluecom\PaymentFee\Helper\Data $paymentFeeHelper,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Riki\Sales\Model\OrderPayshipStatus $orderPayshipStatus,
        \Riki\Preorder\Model\OrderPreorder $orderPreorder,
        \Riki\Customer\Api\ShoshaRepositoryInterface $shoshaRepository,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\SftpExportHelper $sftpHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper $configHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\EmailExportHelper $emailHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper,
        \Riki\SubscriptionCourse\Model\CourseFactory $subscriptionCourseFactory
    ) {
        parent::__construct($context, $dateTime, $timezone, $resourceConfig, $sftpHelper, $fileHelper, $configHelper, $emailHelper, $dateTimeColumnsHelper, $connectionHelper);
        $this->_shipmentRepository = $shipmentRepository;
        $this->_orderStatusHistoryFactory = $historyFactory;
        $this->orderPaymentCollection = $orderPaymentCollection;
        $this->_orderPaymentTransactionFactory = $orderPaymentTransactionFactory;
        $this->modelCustomerFactory = $modelCustomerFactory;
        $this->_modelCustomer = $modelCustomer;
        $this->_productRepository = $productRepository;
        $this->paymentFeeHelper = $paymentFeeHelper;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_filterBuilder = $filterBuilder;
        $this->_profileRepository = $profileRepository;
        $this->orderPreorder = $orderPreorder;
        $this->shoshaRepository = $shoshaRepository;
        $this->subscriptionCourseFactory = $subscriptionCourseFactory;
        $this->bundleItemsHelper = $bundleItemsHelper;

        $this->_connection = $connectionHelper->getDefaultConnection();
        $this->_connectionSales = $connectionHelper->getSalesConnection();
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
     * set default config before export
     *
     * @param $defaultLocalPath
     * @param $configLocalPath
     * @param $configSftpPath
     * @param $configReportPath
     * @param $configLastTimeRun
     * @return bool
     */
    public function initExport($defaultLocalPath, $configLocalPath, $configSftpPath, $configReportPath, $configLastTimeRun)
    {
        $initExport = parent::initExport($defaultLocalPath, $configLocalPath, $configSftpPath, $configReportPath, $configLastTimeRun); // TODO: Change the autogenerated stub

        if ($initExport) {
            /*tmp file to ensure that system do not run same mulit process at the same time*/
            $lockFile = $this->getLockFile();
            if ($this->_fileHelper->isExists($lockFile)) {
                $this->_logger->info('Please wait, system have a same process is running and haven’t finish yet.');
                throw new \Magento\Framework\Exception\LocalizedException(__('Please wait, system have a same process is running and haven’t finish yet.'));
            } else {
                $this->_fileHelper->createFile($lockFile);
            }
        }

        return $initExport;
    }

    /**
     * Export process
     */
    public function exportProcess()
    {
        $this->_logger->info('Start tracking: Export Order BI');

        $this->export();

        /* set last time to run */
        $this->setLastRunToCron();

        /*move export folder to ftp*/
        $this->_sftpHelper->moveFileToFtp($this->_pathTmp, $this->_path, $this->getSFTPPathExport(), $this->getReportPathExport());

        /*delete all records which is exported*/
        $this->deleteExportedSuccessRecord();

        $totalRecords = sizeof($this->_exportEntityList);

        $this->_logger->info('End tracking: Export Order BI, total record: '. $totalRecords);

        /*send email notify*/
        $this->sentNotificationEmail();

        /*delete lock file*/
        $this->deleteLockFile();
    }

    /**
     * export main function
     */
    public function export()
    {
        /*generate export data */
        $this->generateExportData();

        /*get export date via config timezone*/
        $exportDate = $this->_timezone->date()->format('YmdHis');
        /*order header file name*/
        $orderHeaderFileName = 'orderheader-'.$exportDate.'.csv';

        if ($this->_headerData) {
            /*export order header*/
            $this->createLocalFile([
                $orderHeaderFileName => $this->_headerData
            ]);

            $this->_logger->info('Create header file for local: '.$orderHeaderFileName);

            $this->_headerData = [];
        }

        //export Detail of Order,(export 1 detail per batch execution)
        if ($this->_detailData) {

            $orderDetailFileName = 'orderdetail-' . $exportDate . '.csv';

            $this->createLocalFile([
                $orderDetailFileName => $this->_detailData
            ]);

            $this->_logger->info('Create detail file for local: '.$orderDetailFileName);

            $this->_detailData = [];
        }

        /*update is bi exported flag for exported records*/
        $this->updateAfterExportSuccess();
    }

    /**
     * GetOrderCustomer
     *
     * @param $aColumnCustomerPlains
     * @param $aColumnCustomerShosha
     * @param $order
     * @return array
     */
    public function getOrderCustomer($aColumnCustomerPlains, $aColumnCustomerShosha, $order)
    {
        $customerId = (isset($order['customer_id']) && $order['customer_id']) ? $order['customer_id'] : null;
        if ($customerId && !isset($this->customers[$customerId])) {
            $this->customers[$customerId] = $this->modelCustomerFactory->create()->load($customerId);
        }

        $customerOrder = null;
        if (isset($this->customers[$customerId])) {
            $customerOrder = $this->customers[$customerId];
        }

        if ($customerOrder) {
            foreach ($aColumnCustomerPlains as $sColumnsCustomer) {
                if (($customerData = $customerOrder->getData($sColumnsCustomer))) {
                    $order[] = $customerData;
                } else {
                    $order[] = '';
                }
            }

            //push data shosha code
            $shoshaBusinessCode = isset($order['shosha_business_code']) ? $order['shosha_business_code'] : '';
            $aOrderShoshaData = [];
            if ($shoshaBusinessCode) {
                try {
                    $filter = $this->_searchCriteriaBuilder->addFilter('shosha_business_code', $shoshaBusinessCode);
                    $aShoshaCustomerItems = $this->shoshaRepository->getList($filter->create());
                    if ($aShoshaCustomerItems->getTotalCount()) {
                        $aShoshaCustomerData = $aShoshaCustomerItems->getItems();
                        foreach ($aShoshaCustomerData as $aShoshaItem) {

                            foreach ($aColumnCustomerShosha as $sColumnShosha) {
                                if ($aShoshaItem->hasData($sColumnShosha)) {
                                    $aOrderShoshaData[] = $aShoshaItem->getData($sColumnShosha);
                                } else {
                                    $aOrderShoshaData[] = '';
                                }
                            }
                            $order = array_merge($order, $aOrderShoshaData);
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            }

            if (empty($aOrderShoshaData)) {
                foreach ($aColumnCustomerShosha as $sColumnShosha) {
                    $order[] = '';
                }
            }
        } else {
            foreach (array_merge($aColumnCustomerPlains, $aColumnCustomerShosha) as $sColumnsCustomer) {
                $order[] = '';
            }
        }

        return $order;
    }

    /**
     * geneeate export data
     */
    public function generateExportData()
    {
        /*header data*/
        $this->_headerData = [];

        /*detail data*/
        $this->_detailData = [];

        //prepare column customer for order
        $aColumnCustomers = [];
        $aColumnCustomerPlains = [];
        $aColumnCustomerShosha = [];
        $aColumnCustomerAttributes = $this->_modelCustomer->getAttributes();
        $aRemoveColumns = ['password_hash','group_id','prefix','lastname','lastnamekana','middlename',
            'firstname','firstnamekana','suffix','email','dob',
            'taxvat','gender','membership'];

        if ($aColumnCustomerAttributes) {
            foreach($aColumnCustomerAttributes as $sColumnCustomer => $value) {
                if(!in_array($sColumnCustomer,$aRemoveColumns)){
                    $aColumnCustomers[] = 'order.customer_'.$sColumnCustomer;
                    $aColumnCustomerPlains[] = $sColumnCustomer;
                }
            }

            $aColumnCustomerShosha = $this->getShoshaCustomerColumn();
            foreach($aColumnCustomerShosha as $sColumn){
                $aColumnCustomers[] = 'order.customer_'.$sColumn;
            }
        }

        $headerColumns = $this->getOrderExportColumns($aColumnCustomers);

        array_push($this->_headerData, $headerColumns);

        $aAllowColumns = [ 'riki_fair_details' => [ 'fair_id'] ];

        $detailColumns = $this->getOrderDetailExportColumns([], $aAllowColumns);

        array_push($this->_detailData, $detailColumns);

        /*array of order entity id will be exported*/
        $this->_exportEntityList = $this->getExportOrderList();

        if ($this->_exportEntityList) {

            /*block order to ensure that do not have any process handle it again*/
            $this->blockOrderForExportProcess();

            /*generate subscription data*/
            $this->generateSubscriptionCourseData();

            /*generate pre-order data*/
            $this->generatePreOrderData();

            /*generate order status history*/
            $this->generateOrderStatusHistory();

            /*generate order data - payment date*/
            $this->generateOrderPaymentDate();

            /*generate list paygent order*/
            $this->generateOrderPaygentList();

            /*generate order data - authorize*/
            $this->generateAuthorizeData();

            foreach ($this->_exportEntityList as $exp) {
                try {
                    $this->originalHeaderData = $this->_headerData;
                    $this->originalDetailData = $this->_detailData;

                    /*sale order table name*/
                    $orderTable = $this->_connectionSales->getTableName('sales_order');

                    $selectOrders = $this->_connectionSales->select()->from($orderTable)->where(
                        $orderTable . '.entity_id = ?', $exp
                    );

                    /*get export data from sale order table*/
                    $orderData = $this->_connectionSales->fetchRow($selectOrders);

                    if ($orderData) {

                        /*order address table name*/
                        $orderAddressTable = $this->_connectionSales->getTableName('sales_order_address');

                        $selectOrderAddresses = $this->_connectionSales->select()->from(
                            $orderAddressTable
                        )->where(
                            $orderAddressTable.'.entity_id = ?',$orderData['billing_address_id']
                        );

                        /*get order address data*/
                        $queryOrderAddresses = $this->_connectionSales->query($selectOrderAddresses);

                        $orderAddressData = [];
                        while ($orderDataAddress = $queryOrderAddresses->fetch()) {
                            foreach($orderDataAddress as $sColumn => $sValue){
                                $orderAddressData[] = $sValue;
                            }
                        }

                        if (!count($orderAddressData)) {
                            foreach ($headerColumns as $sColumn) {
                                if(strpos($sColumn,'order.billing_address_') !== false){
                                    $orderAddressData[] = '';
                                }
                            }
                        }

                        $orderData = array_merge(
                            $this->convertDateTimeColumnsToConfigTimezone(self::CONVERTSALESORDER,$orderData),
                            $orderAddressData
                        );

                        /*order payment table name*/
                        $orderPaymentTable = $this->_connectionSales->getTableName('sales_order_payment');

                        $selectOrderPayments = $this->_connectionSales->select()->from(
                            $orderPaymentTable
                        )->where(
                            $orderPaymentTable.'.parent_id = ?',$orderData['entity_id']
                        );

                        /*get order payment data*/
                        $queryOrderPayments = $this->_connectionSales->query($selectOrderPayments);

                        $orderPaymentData = [];
                        while ($orderDataPayment = $queryOrderPayments->fetch()) {

                            /*convert date time column to config timezone for sales_order_payment*/
                            $orderDataPayment = $this->convertDateTimeColumnsToConfigTimezone(self::CONVERTSALESORDERPAYMENT, $orderDataPayment);

                            foreach ($orderDataPayment as $sColumn => $sValue) {
                                $orderPaymentData[] = $sValue;
                            }
                        }

                        if (!count($orderPaymentData)) {
                            foreach ($headerColumns as $sColumn) {
                                if (strpos($sColumn,'order.payment_') !== false) {
                                    $orderPaymentData[] = '';
                                }
                            }
                        }

                        $orderData = array_merge($orderData,$orderPaymentData);

                        $orderData = $this->getOrderAdditionalInformation($orderData, $headerColumns);

                        $orderData = $this->getOrderCaculateField($orderData);

                        $orderData = $this->getOrderCustomer($aColumnCustomerPlains,$aColumnCustomerShosha,$orderData);

                        array_push($this->_headerData, $orderData);

                        $this->_logger->info('Order entity id '. $orderData['entity_id']. ' has ready to exported to BI.');

                        $this->generateDetailDataByOrderId($orderData['entity_id'], $aAllowColumns);
                    }
                } catch (\Exception $exception) {
                    $this->_logger->critical($exception);
                    $this->_headerData = $this->originalHeaderData;
                    $this->_detailData = $this->originalDetailData;
                    $this->rePutOrderToQueue($exp);
                }

            }
        }
    }

    public function getOrderAdditionalInformation($orderData, $headerColumns) {
        // sales_order_additional_information table name
        $orderAdditional = $this->_connectionSales->getTableName('sales_order_additional_information');

        $selectOrderAdditional = $this->_connectionSales->select()->from(
            $orderAdditional
        )->where(
            $orderAdditional.'.order_id = ?',$orderData['entity_id']
        );

        // Get order additional data
        $queryOrderAdditional = $this->_connectionSales->query($selectOrderAdditional);

        $orderAdditionalData = [];
        while ($orderDataAdditional = $queryOrderAdditional->fetch()) {

            foreach ($orderDataAdditional as $sColumn => $sValue) {
                $orderAdditionalData[] = $sValue;
            }
        }

        if (!count($orderAdditionalData)) {
            foreach ($headerColumns as $sColumn) {
                if (strpos($sColumn,'order.addition_') !== false) {
                    $orderAdditionalData[] = '';
                }
            }
        }

        $orderData = array_merge($orderData,$orderAdditionalData);

        return $orderData;
    }

    /**
     * Get Order Calculate Field
     *
     * @param $orderData
     * @return array|mixed
     */
    public function getOrderCaculateField($orderData)
    {
        //payment_commission_tax_rate
        $orderData[] = $this->paymentFeeHelper->getPaymentTaxRate();

        //payment_date
        $orderData[] = $this->getOrderPaymentDate($orderData);

        //updated_user
        $orderData[] = $this->getOrderUpdatedUser($orderData);

        //creditcard_captured_flg
        //authorization_date
        $orderData = $this->getAuthorizedInfo($orderData);

        $orderData = $this->getPaygentTransactionId($orderData);

        $orderData = $this->getOrderStatusHistory($orderData);

        $orderData = $this->getSubscriptionCourseData($orderData);

        $orderData = $this->getIsPreOrder($orderData);

        return $orderData;
    }

    /**
     * GetIsPreOrder
     *
     * @param $orderData
     * @return mixed
     */
    public function getIsPreOrder($orderData)
    {
        $isPreOrder = 0;

        if (isset($this->preOrderData[$orderData['entity_id']])) {
            $isPreOrder = $this->preOrderData[$orderData['entity_id']];
        }

        $orderData['is_preorder'] = $isPreOrder;

        return $orderData;
    }

    /**
     * Get Authorized info
     *
     * @param $orderData
     * @return mixed
     */
    public function getAuthorizedInfo($orderData)
    {
        $isCaptured = 0;

        $sAuthorizationDate = '';

        if (!empty($this->authorizeData[$orderData['entity_id']])) {

            $isCaptured = $this->authorizeData[$orderData['entity_id']]['isCaptured'];

            if (!empty($this->authorizeData[$orderData['entity_id']]['authorizationDate'])) {
                $sAuthorizationDate = $this->convertToConfigTimezone(
                    $this->authorizeData[$orderData['entity_id']]['authorizationDate']
                );
            }
        }

        $orderData[] = $isCaptured;
        $orderData[] = $sAuthorizationDate;

        return $orderData;
    }

    /**
     * get Paygent Transaction Id
     *
     * @param $orderData
     * @return array
     */
    public function getPaygentTransactionId($orderData)
    {
        $lastTransaction = '';

        if (!empty($this->authorizeData[$orderData['entity_id']])) {

            $lastTransaction = $this->authorizeData[$orderData['entity_id']]['lastTransactionId'];
        }

        $orderData[] = $lastTransaction;

        return $orderData;
    }

    /**
     * get Order Status History
     *
     * @param $orderData
     * @return array
     */
    public function getOrderStatusHistory($orderData)
    {
        if (!empty($this->historyData) && !empty($this->historyData[$orderData['entity_id']])) {
            $orderData[] = implode("<br>",$this->historyData[$orderData['entity_id']]);
        } else {
            $orderData[] = ''; //comments
        }

        return $orderData;
    }

    /**
     * get Subscription Course Data
     *
     * @param $orderData
     * @return array
     */
    public function getSubscriptionCourseData($orderData)
    {
        $subscriptionCourseId = '';
        $subscriptionCourseCode = '';

        if (!empty($this->profileCourseMapping) && !empty($orderData['subscription_profile_id'])) {

            $profileId = $orderData['subscription_profile_id'];

            if (isset($this->profileCourseMapping[$profileId]) && !empty($this->profileCourseMapping[$profileId])) {
                $subscriptionCourseId = $this->profileCourseMapping[$profileId]['course_id'];
                $subscriptionCourseCode = $this->profileCourseMapping[$profileId]['course_code'];
            }
        }

        $orderData[] = $subscriptionCourseId;   //subscription_course_id
        $orderData[] = $subscriptionCourseCode; //subscription_course_code

        return $orderData;
    }

    /**
     * Check order payment method is payment
     *
     * @param $orderId
     * @return bool
     */
    public function isPaygentOrder($orderId)
    {
        if (empty($this->paygentOrder)) {
            return false;
        }

        if (in_array($orderId, $this->paygentOrder)) {
            return true;
        }

        return false;
    }

    /**
     * get Order Payment Date
     *
     * @param $orderData
     * @return string
     */
    public function getOrderPaymentDate($orderData)
    {
        $paymentDate = '';

        if (!empty($this->paymentDate) && !empty($this->paymentDate[$orderData['entity_id']])) {
            $paymentDate = $this->convertToConfigTimezone($this->paymentDate[$orderData['entity_id']]);
        }

        return $paymentDate;
    }

    /**
     * get Order Updated User
     *
     * @param $orderData
     * @return mixed
     */
    public function getOrderUpdatedUser($orderData)
    {
        return $orderData['updated_by'];
    }

    /**
     * Generate export detail data
     *
     * @param $orderId
     * @param array $aAllowColumns
     */
    public function generateDetailDataByOrderId($orderId,$aAllowColumns = [])
    {
        /*order item table*/
        $orderItemTbl = $this->_connectionSales->getTableName('sales_order_item');
        $selectOrderDetail = $this->_connectionSales->select()->from(
            $orderItemTbl
        )->where(
            $orderItemTbl . '.order_id = ?',$orderId
        );

        $queryOrderDetail = $this->_connectionSales->query($selectOrderDetail);

        while ($orderDetailData = $queryOrderDetail->fetch()) {

            /*convert order item date time columns to config timezone*/
            $orderDetailData = $this->convertDateTimeColumnsToConfigTimezone(self::CONVERTSALESORDERITEM,$orderDetailData);

            /*re calculate data for bundle children item*/
            $orderDetailData =  $this->bundleItemsHelper->reCalculateOrderItem($orderDetailData);

            $arrayType = [];

            /*riki fair details table*/
            $fairDetailsTbl = $this->_connection->getTableName('riki_fair_details');

            $selectSeasonProduct = $this->_connection->select()->from(
                $fairDetailsTbl
            )->where(
                $fairDetailsTbl.'.product_id = ?',$orderDetailData['product_id']
            );

            $querySeasonProduct = $this->_connection->query($selectSeasonProduct);

            $orderDetailSeasonal = [];

            while ($orderSeasonProduct = $querySeasonProduct->fetch()) {

                foreach ($orderSeasonProduct as $sColumn => $sValue) {
                    if (in_array($sColumn,$aAllowColumns['riki_fair_details'])) {
                        $orderDetailSeasonal[] = $sValue;
                    }
                }
            }

            if (!count($orderDetailSeasonal)) {
                foreach ($aAllowColumns['riki_fair_details'] as $sColumn) {
                    $orderDetailSeasonal[] = '';
                }
            }

            array_unique($orderDetailSeasonal);

            /*merge fair id to export data*/
            $orderDetailData = array_merge($orderDetailData,[implode(",",$orderDetailSeasonal)]);

            /* calculate field */
            $orderDetailData = $this->getOrderDetailCalculateField($orderDetailData);

            /*join sales order sap booking*/
            if ($this->_allColumnSap) {

                $orderBooking = $this->_connection->select()->from(
                    $this->_connection->getTableName('riki_sales_order_sap_booking'),'*'
                )->where(
                    'order_item_id = ?',$orderDetailData['item_id']
                );

                $queryOrderSap = $this->_connection->query($orderBooking);

                $sapOrderItemArrs = $queryOrderSap->fetchAll();

                if ($sapOrderItemArrs) {
                    foreach ($sapOrderItemArrs as $sapOrderItemArr) {
                        $sType = 'order_item.riki_sale_order_sap_booking_'.$sapOrderItemArr['type'];
                        $arrayType[] = $sType;
                        $value[$sType] = $sapOrderItemArr['value'];
                    }
                }

                foreach ($this->_allColumnSap as $type) {
                    if (!in_array($type,$arrayType)) {
                        $orderDetailData[] = '';
                    } else {
                        $orderDetailData[] = $value[$type];
                    }
                }
            }

            array_push($this->_detailData, $orderDetailData);
        }
    }

    /**
     * get Order Detail Calculate Field
     *
     * @param $orderDetailData
     * @return array
     */
    public function getOrderDetailCalculateField($orderDetailData)
    {
        // Ensure no more serialize string in product_options
        $productOptions = $this->bundleItemsHelper->convertDetailOptionsToJsonFormat($orderDetailData['product_options']);
        $orderDetailData['product_options'] = $productOptions;

        //keep function if need update more column
        return $orderDetailData;
    }

    /**
     * Get Order Export Columns
     *
     * @param array $aAdditionColumns
     * @return array
     */
    public function getOrderExportColumns($aAdditionColumns = [])
    {
        $aColumns = [];

        $aColumnSaleOrders = $this->_connectionSales->describeTable(
            $this->_connectionSales->getTableName('sales_order')
        );

        foreach($aColumnSaleOrders as $sColumnSaleOrder => $value){
            if($sColumnSaleOrder == 'subscription_order_time'){
                $aColumns[] = 'order.order_count';
            }
            else{
                $aColumns[] = 'order.'.$sColumnSaleOrder;
            }
        }

        $aColumnSaleOrderAddresses =  $this->_connectionSales->describeTable($this->_connectionSales->getTableName('sales_order_address'));

        foreach($aColumnSaleOrderAddresses as $sColumnSaleOrderAddress => $value){
            $aColumns[] = 'order.billing_address_'.$sColumnSaleOrderAddress;
        }


        $aColumnSaleOrderPayments =  $this->_connectionSales->describeTable($this->_connectionSales->getTableName('sales_order_payment'));

        foreach($aColumnSaleOrderPayments as $sColumnSaleOrderPayment => $value){
            $aColumns[] = 'order.payment_'.$sColumnSaleOrderPayment;
        }

        $aColumnSaleOrderAdditions =  $this->_connectionSales->describeTable($this->_connectionSales->getTableName('sales_order_additional_information'));

        foreach($aColumnSaleOrderAdditions as $sColumnSaleOrderAddition => $value){
            $aColumns[] = 'order.addition_'.$sColumnSaleOrderAddition;
        }

        $aColumns = array_merge($aColumns,$this->getOrderCalculatedColumn());

        $aColumns = array_merge($aColumns,$aAdditionColumns);

        return $aColumns;
    }

    /**
     * get Order Calculated Column
     *
     * @return array
     */
    public function getOrderCalculatedColumn()
    {
        return [
            'order.payment_commission_tax_rate',
            'order.payment_date',
            'order.updated_user',
            'order.creditcard_captured_flg',
            'order.authorization_date',
            'order.paygent_transaction_id',
            'order.comments',
            'order.subscription_course_id',
            'order.subscription_course_code',
            'order.is_preorder'
        ];
    }

    /**
     * Get Order Detail Export Columns
     *
     * @param array $aAdditionColumns
     * @param array $aAllowColumns
     * @return array
     */
    public function getOrderDetailExportColumns($aAdditionColumns = [],$aAllowColumns = [])
    {

        $aColumns = [];

        $aOrderItemColumns = $this->_connectionSales->describeTable($this->_connectionSales->getTableName('sales_order_item'));

        foreach($aOrderItemColumns as $sColumnOrderItem => $sValue){
            $aColumns[] = 'order_item.'.$sColumnOrderItem;
        }

        $aSeasonalItemColumns = $this->_connection->describeTable($this->_connection->getTableName('riki_fair_details'));

        foreach($aSeasonalItemColumns as $sColumnSeasonItem => $sValue){
            if(in_array($sColumnSeasonItem,$aAllowColumns['riki_fair_details'])){
                $aColumns[] = 'order_item.'.$sColumnSeasonItem;
            }
        }

        $aColumns = array_merge($aColumns,$aAdditionColumns);

        $aColumns = array_merge($aColumns,$this->getOrderDetailCalculatedColumn());
        $aColumns = array_merge($aColumns,$this->getAllColumnSap());
        //get column from riki_sales_order_sap_booking

        return $aColumns;
    }

    /**
     * get Order Detail Calculated Column
     *
     * @return array
     */
    public function getOrderDetailCalculatedColumn()
    {
        //keep function if need update more column
        return [];
    }

    /**
     * get all value attribute "type" in riki_sales_order_sap_booking table
     *
     * @return array
     */
    public function getAllColumnSap()
    {
        $return = [];
        $orderBooking = $this->_connection
            ->select()
            ->distinct()
            ->from($this->_connection->getTableName('riki_sales_order_sap_booking'),'type');
        $queryOrderSap = $this->_connection->query($orderBooking);
        $sapOrderItemArrs = $queryOrderSap->fetchAll();
        foreach($sapOrderItemArrs as $column){
            $return[] = 'order_item.riki_sale_order_sap_booking_' . $column['type'];
        }
        $this->_allColumnSap = $return;
        return $this->_allColumnSap;
    }

    /**
     * get Shosha Customer Column
     *
     * @return array
     */
    public function getShoshaCustomerColumn()
    {
        $sColumns =  array_keys($this->_connection->describeTable($this->_connection->getTableName('riki_shosha_business_code')));
        $sColumnsRemove = ['id','shosha_business_code','orm_rowid','updated_at','created_at','is_bi_exported','is_cedyna_exported'];
        foreach($sColumnsRemove as $sColumnRemove){
            if(($key = array_search($sColumnRemove, $sColumns)) !== false) {
                unset($sColumns[$key]);
            }
        }

        return $sColumns;
    }

    /**
     * get subscription course data for all order
     */
    public function generateSubscriptionCourseData()
    {
        if (empty($this->_exportEntityList)) {
            return;
        }

        /*sales_order*/
        $orderTable = $this->_connectionSales->getTableName('sales_order');

        $getProfileList = $this->_connectionSales->select()->from(
            $orderTable, ['DISTINCT(subscription_profile_id)']
        )->where(
            'entity_id IN (?) AND subscription_profile_id IS NOT NULL', $this->_exportEntityList
        );

        $profileList = $this->_connectionSales->fetchCol($getProfileList);

        if (!empty($profileList)) {
            /*subscription_course*/
            $subscriptionCourseTable = $this->_connectionSales->getTableName('subscription_course');
            /*subscription_profile*/
            $subscriptionProfileTable = $this->_connectionSales->getTableName('subscription_profile');

            $getProfileCourseData = $this->_connectionSales->select()->from(
                $subscriptionProfileTable, ['profile_id', 'course_id']
            )->join(
                $subscriptionCourseTable,
                $subscriptionCourseTable.'.course_id = '.$subscriptionProfileTable.'.course_id',
                'course_code'
            )->where(
                $subscriptionProfileTable.'.profile_id in (?)', $profileList
            );

            $profileCourseData = $this->_connectionSales->query($getProfileCourseData);

            while ($profileCourse = $profileCourseData->fetch()) {
                if (!isset($this->profileCourseMapping[$profileCourse['profile_id']])) {
                    $this->profileCourseMapping[$profileCourse['profile_id']] = [
                        'course_id' => $profileCourse['course_id'],
                        'course_code' => $profileCourse['course_code']
                    ];
                }
            }
        }
    }

    /**
     * Get pre order data for all order
     */
    public function generatePreOrderData()
    {
        if (empty($this->_exportEntityList)) {
            return;
        }

        /*riki_preorder_order_preorder*/
        $preOrderTable = $this->_connectionSales->getTableName('riki_preorder_order_preorder');

        $getPreOrderData = $this->_connectionSales->select()->from(
            $preOrderTable, ['order_id', 'is_preorder']
        )->where(
            'order_id IN (?)', $this->_exportEntityList
        );

        $preOrderData = $this->_connectionSales->query($getPreOrderData);

        while ($preOrder = $preOrderData->fetch()) {
            if (!isset($this->preOrderData[$preOrder['order_id']])) {
                $this->preOrderData[$preOrder['order_id']] = $preOrder['is_preorder'];
            }
        }
    }

    /**
     * Get status history for all orders
     */
    public function generateOrderStatusHistory()
    {
        if (empty($this->_exportEntityList)) {
            return;
        }

        /*sales_order_status_history*/
        $historyTable = $this->_connectionSales->getTableName('sales_order_status_history');

        $getHistoryData = $this->_connectionSales->select()->from(
            $historyTable, ['parent_id', 'comment']
        )->where(
            'parent_id IN (?)', $this->_exportEntityList
        )->order(
            ['DESC' => 'parent_id', 'ASC' => 'created_at']
        );

        $historyData = $this->_connectionSales->query($getHistoryData);

        while ($history = $historyData->fetch()) {
            if (!isset($this->historyData[$history['parent_id']])) {
                $this->historyData[$history['parent_id']] = [];
            }

            if (!empty($history['comment'])) {
                array_push($this->historyData[$history['parent_id']], $history['comment']);
            }
        }
    }

    /**
     * Get payment date for all orders
     */
    public function generateOrderPaymentDate()
    {
        if (empty($this->_exportEntityList)) {
            return;
        }

        /*sales_order_status_history*/
        $shipmentTable = $this->_connectionSales->getTableName('sales_shipment');

        $getPaymentDate = $this->_connectionSales->select()->from(
            $shipmentTable, ['order_id', 'payment_date']
        )->where(
            'order_id in (?)', $this->_exportEntityList
        )->where(
            'payment_status = ?', \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_COLLECTED
        )->order(
            ['DESC' => 'payment_date']
        )->group(
            'order_id'
        );

        $paymentDateData = $this->_connectionSales->query($getPaymentDate);

        while ($payment = $paymentDateData->fetch()) {
            if (!isset($this->paymentDate[$payment['order_id']]) && !empty($payment['payment_date'])) {
                $this->paymentDate[$payment['order_id']] = $payment['payment_date'];
            }
        }
    }

    /**
     * get authorize data for all orders
     */
    public function generateAuthorizeData()
    {
        if (empty($this->_exportEntityList)) {
            return;
        }

        /*sales_payment_transaction*/
        $transactionTable = $this->_connectionSales->getTableName('sales_payment_transaction');

        $getTransactionData = $this->_connectionSales->select()->from(
            $transactionTable
        )->where(
            'order_id in (?)', $this->_exportEntityList
        )->order(
            ['DESC' => 'order_id', 'ASC' => 'created_at']
        );

        $transactionData = $this->_connectionSales->query($getTransactionData);

        while ($transaction = $transactionData->fetch()) {
            if (!isset($this->authorizeData[$transaction['order_id']])) {
                $this->authorizeData[$transaction['order_id']] = [
                    'isCaptured' => 0,
                    'authorizationDate' => '',
                    'lastTransactionId' => ''
                ];
            }

            if ('captured' == $transaction['txn_type']) {
                $this->authorizeData[$transaction['order_id']]['isCaptured'] = 1;
            }

            if ('authorization' == $transaction['txn_type']) {
                $this->authorizeData[$transaction['order_id']]['authorizationDate'] = $transaction['created_at'];
            }

            if ($this->isPaygentOrder($transaction['order_id'])) {
                $this->authorizeData[$transaction['order_id']]['lastTransactionId'] = $transaction['transaction_id'];
            }
        }
    }

    /**
     * Generate order paygent list
     */
    public function generateOrderPaygentList()
    {
        if (empty($this->_exportEntityList)) {
            return;
        }

        /*sales_order_payment*/
        $paymentTable = $this->_connectionSales->getTableName('sales_order_payment');

        $getPaymentData = $this->_connectionSales->select()->from(
            $paymentTable, ['parent_id']
        )->where(
            'parent_id in (?) and method = "paygent"', $this->_exportEntityList
        );

        $this->paygentOrder = $this->_connectionSales->fetchCol($getPaymentData);
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_order
     * @return mixed
     */
    public function getOrderDateTimeColumns()
    {
        if (empty($this->_orderDateTimeColumns)) {
            $this->_orderDateTimeColumns = $this->_dateTimeColumnsHelper->getOrderDateTimeColumns();
        }

        return $this->_orderDateTimeColumns;
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_order_item
     * @return mixed
     */
    public function getOrderItemDateTimeColumns()
    {
        if (empty($this->_orderItemDateTimeColumns)) {
            $this->_orderItemDateTimeColumns = $this->_dateTimeColumnsHelper->getOrderItemDateTimeColumns();
        }

        return $this->_orderItemDateTimeColumns;
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_order_payment
     * @return mixed
     */
    public function getOrderPaymentDateTimeColumns()
    {
        if (empty($this->_orderPaymentDateTimeColumns)) {
            $this->_orderPaymentDateTimeColumns = $this->_dateTimeColumnsHelper->getOrderPaymentDateTimeColumns();
        }

        return $this->_orderPaymentDateTimeColumns;
    }

    /**
     * Convert date time from UTC to config timezone
     *
     * @param $type
     * @param $object {sales_order object, sales_order_item, sales_order_payment object}
     * @return mixed
     */
    public function convertDateTimeColumnsToConfigTimezone($type, $object)
    {
        if ($object) {

            /*get datetime columns by type*/
            if ($type == self::CONVERTSALESORDER) {
                $datetimeColumns = $this->getOrderDateTimeColumns();
            } else if ($type == self::CONVERTSALESORDERITEM) {
                $datetimeColumns = $this->getOrderItemDateTimeColumns();
            } else if ($type == self::CONVERTSALESORDERPAYMENT) {
                $datetimeColumns = $this->getOrderPaymentDateTimeColumns();
            }

            foreach ($datetimeColumns as $column) {

                if (!empty($object[$column])) {
                    /*convert datetime from column data to config timezone*/
                    $object[$column] = $this->convertToConfigTimezone($object[$column]);
                }
            }
        }

        return $object;
    }

    /**
     * Limit record
     *
     * @return mixed
     */
    public function getLimit()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::CONFIG_LIMIT, $storeScope);
    }

    /**
     * Get exported order list
     *
     * @return array|bool
     */
    public function getExportOrderList()
    {
        /*get limit record*/
        $limit = $this->getLimit();
        if (!$limit) {
            $limit = self::CONFIG_DEFAULT_LIMIT;
        }

        /*version tbl*/
        $versionTbl = $this->_connectionSales->getTableName('riki_order_version_bi_export');

        $currentTimestamp = $this->_dateTime->timestamp();
        //order must be created greater than 2 minutes to avoid the created order process has not finished yet
        $maxCreatedAt = $this->_dateTime->date(
            'Y-m-d H:i:s',
            $currentTimestamp - (2 * 60)
        );

        $orderList = $this->_connectionSales->select()->from(
            $versionTbl, ['entity_id', new \Zend_Db_Expr('MIN(version_id)')]
        )->where(
            $versionTbl . '.is_bi_exported = 0'
        )->where(
            $versionTbl . '.created_at < ?', $maxCreatedAt
        )->group('entity_id')->limit(
            $limit, 0
        );

        try {
            return $this->_connectionSales->fetchCol($orderList);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
        return false;
    }

    /**
     * after export success, change flag is_bi_exported = 1
     */
    public function updateAfterExportSuccess()
    {
        /*update data to ensure that this record is exported to BI*/
        if (!empty( $this->_exportEntityList)) {
            $table = $this->_connectionSales->getTableName('riki_order_version_bi_export');
            $bind = [ 'is_bi_exported' => 1 ];

            $where = [
                'entity_id IN (?)' => $this->_exportEntityList,
                'is_bi_exported = ?' => 2
            ];

            try {
                $this->_connectionSales->update($table, $bind, $where);
            } catch (\Exception $e) {
                $this->_logger->info($e->getMessage());
            }
        }
    }

    /**
     * lock order to ensure that do not have any process handle it again
     */
    public function blockOrderForExportProcess()
    {
        if (!empty( $this->_exportEntityList)) {
            $table = $this->_connectionSales->getTableName('riki_order_version_bi_export');
            $bind = [ 'is_bi_exported' => 2 ];
            $where = [
                'entity_id IN (?)' => $this->_exportEntityList,
                'is_bi_exported = ?' => 0
            ];
            try {
                $this->_connectionSales->update($table, $bind, $where);
            } catch (\Exception $e) {
                $this->_logger->info($e->getMessage());
            }
        }
    }

    /**
     * delete all records which is exported success
     */
    public function deleteExportedSuccessRecord()
    {
        $table = $this->_connectionSales->getTableName('riki_order_version_bi_export');
        $where = [
            'is_bi_exported' => 1,
            'entity_id IN (?)' => $this->_exportEntityList
        ];
        try {
            $this->_connectionSales->delete($table, $where);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }

    /**
     * Delete lock file
     */
    public function deleteLockFile()
    {
        $this->_fileHelper->deleteFile($this->getLockFile());
    }

    /**
     * Put error order into queue
     * @param $entityId
     */
    public function rePutOrderToQueue($entityId)
    {
        $table = $this->_connectionSales->getTableName('riki_order_version_bi_export');
        $bind = ['is_bi_exported' => 0];
        $where = [
            'entity_id = ?' => $entityId,
            'is_bi_exported = ?' => 2
        ];

        try {
            $this->_connectionSales->update($table, $bind, $where);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }
}
