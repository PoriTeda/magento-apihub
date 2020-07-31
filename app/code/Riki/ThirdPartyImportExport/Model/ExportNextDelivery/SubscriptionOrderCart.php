<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\ThirdPartyImportExport\Model\ExportNextDelivery;

use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryOrderHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Riki\Subscription\Model\ProductCart\ProductCartFactory as ProductCartModel;

class SubscriptionOrderCart
{
    const PROFILETYPE = 'subscription_profile';
    const COURSETYPE = 'subscription_course';
    const ORDERITEMTYPE = 'order_item';

    const DEFAULT_LOCAL_SAVE = 'var/bi_subscription_next_delivery_order';

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
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerSubProfileCart
     */
    protected $cartLogger;

    /**
     * @var SubProfileNextDeliveryHelper
     */
    protected $_subProfileNextDeliveryHelper;

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
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var ProductCartModel
     */
    protected $productCartFactory;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper
     */
    protected $bundleItemsHelper;

    /**
     * @var array
     */
    protected $_aColumnOrderItems = [];

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var int
     */
    protected $hanpukaiOrderTime = 0;

    /**
     * @var array
     */
    protected $aSimulateOrderData;

    /**
     * @var
     */
    protected $defaultColumn = [];
    /**
     * @var
     */
    protected $defaultColumnSalesOrderItem = [];

    /**
     * @var
     */
    protected $defaultColumnSalesOrderItemPrefix = [];
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $subscriptionConnection;

    /**
     * SubscriptionOrderCart constructor.
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param TimezoneInterface $timezone
     * @param DateTime $dateTime
     * @param File $file
     * @param GlobalHelper $globalHelper
     * @param DirectoryList $directoryList
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\HandlerSubProfileCart $handlerCartCSV
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerSubProfileCart $loggerCart
     * @param SubProfileNextDeliveryOrderHelper $subProfileNextDeliveryHelper
     * @param ResourceConnection $resourceConnection
     * @param ProductCartModel $productCartFactory
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        ProductRepositoryInterface $productRepositoryInterface,
        TimezoneInterface $timezone,
        DateTime $dateTime,
        File $file,
        GlobalHelper $globalHelper,
        DirectoryList $directoryList,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\HandlerSubProfileCart $handlerCartCSV,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerSubProfileCart $loggerCart,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryOrderHelper $subProfileNextDeliveryHelper,
        ResourceConnection $resourceConnection,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper,
        \Magento\Framework\Registry $registry
    ) {

        $this->_directoryList = $directoryList;
        $this->_timezone = $timezone;
        $this->_file = $file;

        $this->_productRepository = $productRepositoryInterface;
        $this->_subProfileNextDeliveryHelper = $subProfileNextDeliveryHelper;

        $this->cartLogger = $loggerCart;
        $this->handlerCartCSV = $handlerCartCSV;
        $this->cartLogger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));

        $this->_dataHelper = $globalHelper;
        $this->_dateTime = $dateTime;
        $this->_productCartFactory = $productCartFactory;

        $this->_resource = $resourceConnection;
        $this->_connectionSales = $this->_resource->getConnection('sales');
        $this->subscriptionConnection = $this->_resource->getConnection('subscription');

        $this->appState = $appState;
        $this->bundleItemsHelper = $bundleItemsHelper;
        $this->registry = $registry;
    }

    /**
     * @return null|void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function initExportProfileCart()
    {

        if (!$this->_dataHelper->isEnable()) {
            return null;
        }

        $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $localCsv = $this->_dataHelper->getConfig(\Riki\ThirdPartyImportExport\Helper\ExportBi\SubProfileNextDeliveryOrderHelper::CONFIG_CRON_EXPORT_SUBSCRIPTION_PROFILE_CART_LOCAL_PATH);
        if (!$localCsv) {
            $createFileLocal[] = $baseDir . self::DS . self::DEFAULT_LOCAL_SAVE;
            $createFileLocal[] = $baseDir . self::DS . self::DEFAULT_LOCAL_SAVE . '_tmp';
            $this->_path = self::DEFAULT_LOCAL_SAVE;
            $this->pathTmp = self::DEFAULT_LOCAL_SAVE . '_tmp';
        } else {
            rtrim($localCsv);
            $createFileLocal[] = $baseDir . self::DS . $localCsv;
            $createFileLocal[] = $baseDir . self::DS . $localCsv . '_tmp';
            $this->_path = $localCsv;
            $this->pathTmp = $localCsv . '_tmp';
        }

        $this->_subProfileNextDeliveryHelper->backupLog(
            'bi_export_subscription_profile_next_delivery_order',
            $this->cartLogger
        );
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
    public function exportSubscriptionProfileCart($subProfile, $consumerName)
    {
        try {
            $this->hanpukaiOrderTime = 0;

            $this->setConsumerName($consumerName);

            $this->handlerCartCSV->setDynamicFileLog($consumerName);
            $this->cartLogger->setHandlers(['system' => $this->handlerCartCSV]);

            $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);

            $this->initExportProfileCart();
            $iProfileId = $subProfile['profile_id'];

            if ($subProfile['type_profile'] == \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionProfileSimulate::SUBSCRIPTION_PROFILE_MAIN) {
                $arrExportDataSubProfileProductCart = $this->appState->emulateAreaCode(
                    'adminhtml',
                    [$this, "exportSubProfileProductCart"],
                    [$subProfile]
                );

                $nameCsvSubscriptionProfileProductCart = 'subscription-profile-product-cart-' . $this->_timezone->date()->format('Ymd') . '-' . $consumerName . '.csv';
                $nameCsvSubscriptionProfileProductCart = $baseDir . self::DS . $this->pathTmp . self::DS . $nameCsvSubscriptionProfileProductCart;

                $this->_dataHelper->writeFileLocal(
                    $nameCsvSubscriptionProfileProductCart,
                    $arrExportDataSubProfileProductCart
                );
                if (count($arrExportDataSubProfileProductCart) > 1) {
                    $this->cartLogger->info('Write profile ' . $iProfileId . ' into ' . $nameCsvSubscriptionProfileProductCart . ' successfully');
                }
            } elseif ($subProfile['type_profile'] == \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionProfileSimulate::SUBSCRIPTION_PROFILE_VERSION) {
                $arrExportDataSubProfileProductCartVersion1 = $this->appState->emulateAreaCode(
                    'adminhtml',
                    [$this, "exportSubProfileProductCart"],
                    [$subProfile, true]
                );

                $nameCsvSubscriptionProfileProductCartVersion = 'subscription-profile-product-cart-version-1-' . $this->_timezone->date()->format('Ymd') . '-' . $consumerName . '.csv';
                $nameCsvSubscriptionProfileProductCartVersion = $baseDir . self::DS . $this->pathTmp . self::DS . $nameCsvSubscriptionProfileProductCartVersion;

                $this->_dataHelper->writeFileLocal(
                    $nameCsvSubscriptionProfileProductCartVersion,
                    $arrExportDataSubProfileProductCartVersion1
                );
                if (count($arrExportDataSubProfileProductCartVersion1) > 1) {
                    $this->cartLogger->info('Write profile ' . $iProfileId . ' into ' . $nameCsvSubscriptionProfileProductCartVersion . ' successfully');
                }
            } elseif ($subProfile['type_profile'] == \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\SubscriptionProfileSimulate::SUBSCRIPTION_PROFILE_HANPUKAI) {

                /**
                 * Export Hanpukai sequence
                 */
                $this->appState->emulateAreaCode(
                    'adminhtml',
                    [$this, "exportHanpukaiSequenceSubProfileCartOnly"],
                    [$subProfile, $baseDir]
                );
            }

            $this->hanpukaiOrderTime = 0;
        } catch (\Exception $e) {
            $this->cartLogger->info($e->getMessage());
            throw $e;
        }
    }


    /**
     * @return array
     */
    public function getDefaultColumn(){

        if (!empty($this->defaultColumn)) {
            return $this->defaultColumn;
        }

        //merge header of product cart
        $aColumnProfileCarts = [];

        $aColumnOrderItemPrefixs = $this->getSaleOrderItemColumn(true);

        $aColumnProfileCarts = array_merge($aColumnProfileCarts, $aColumnOrderItemPrefixs);

        $aColumnProfileCarts = array_merge($aColumnProfileCarts, $this->getSubscriptionProfileCartColumns());

        $this->defaultColumn = $aColumnProfileCarts;

        return $this->defaultColumn;
    }

    /**
     * getSubscriptionProfileCartColumns
     *
     * @return array
     */
    public function getSubscriptionProfileCartColumns($aAdditionColumns = [])
    {

        $aColumns = [];

        $aColumns[] = 'product.is_spot';

        $aColumns[] = 'product.parent_id';

        $aColumnSubscriptionProfiles = $this->_connectionSales->describeTable($this->_connectionSales->getTableName('subscription_profile'));

        foreach ($aColumnSubscriptionProfiles as $sColumnSubscriptionProfile => $value) {
            $aColumns[] = 'product.subscription_profile_' . $sColumnSubscriptionProfile;
        }

        $aColumnSubscriptionCourses = $this->_connectionSales->describeTable($this->_connectionSales->getTableName('subscription_course'));

        foreach ($aColumnSubscriptionCourses as $sColumnSubscriptionCourse => $value) {
            $aColumns[] = 'product.course_' . $sColumnSubscriptionCourse;
        }

        $aColumns = array_merge($aColumns, $aAdditionColumns);

        return $aColumns;
    }

    /**
     * @param bool $isPrefix
     * @return array
     */
    public function getSaleOrderItemColumn($isPrefix = false){
        if ($isPrefix) {
            if (!empty($this->defaultColumnSalesOrderItemPrefix)) {
                return $this->defaultColumnSalesOrderItemPrefix;
            }

            $columnSaleOrderItem = $this->_connectionSales->describeTable('sales_order_item');

            foreach ($columnSaleOrderItem as $sColumnOrderItem => $value) {
                $aColumnOrderItemPrefix[] = 'product.' . $sColumnOrderItem;
            }

            $this->defaultColumnSalesOrderItemPrefix = $aColumnOrderItemPrefix;

            return $this->defaultColumnSalesOrderItemPrefix;
        } else {
            if (!empty($this->defaultColumnSalesOrderItem)) {
                return $this->defaultColumnSalesOrderItem;
            }

            $columnSaleOrderItem = $this->_connectionSales->describeTable('sales_order_item');

            $this->defaultColumnSalesOrderItem = array_keys($columnSaleOrderItem);

            return $this->defaultColumnSalesOrderItem;
        }
    }


    /**
     * @param $productCartData
     * @param $aOrderItemData
     * @param $aColumnProfileCarts
     * @param $isExportProductCartVersion
     * @param $iMapVersionAndProfileId
     * @return array
     */
    public function getSubscriptionProfileCartData(
        $productCartData,
        $aOrderItemData,
        $aColumnProfileCart,
        $isExportProductCartVersion,
        $iMapVersionAndProfileId
    ) {

        $subProfileCartData = [];

        if (!isset($productCartData['profile_id'])) {
            return $subProfileCartData;
        }

        $subProfileCartData = array_merge($subProfileCartData, $aOrderItemData);

        $subProfileCartData[] = isset($productCartData['is_spot']) ? $productCartData['is_spot'] : 0;

        $subProfileCartData[] = isset($productCartData['parent_item_id']) ? $productCartData['parent_item_id'] : '';

        //subscription profile
        $selectSubProfiles = $this->_connectionSales->select()->from(
            $this->_connectionSales->getTableName('subscription_profile')
        )->where(
            $this->_connectionSales->getTableName('subscription_profile') . '.profile_id = ?',
            $productCartData['profile_id']
        );

        $querySubProfiles = $this->_connectionSales->query($selectSubProfiles);

        $iCourseId = 0;
        $subProfileData = [];
        while ($subProfile = $querySubProfiles->fetch()) {
            /*convert subscription profile date time columns to config timezone*/
            $subProfile = $this->convertDateTimeColumnsToConfigTimezone(self::PROFILETYPE, $subProfile);

            foreach ($subProfile as $sColumn => $sValue) {
                if ($sColumn == 'course_id') {
                    $iCourseId = $sValue;
                }
                //replace real profile id for version
                if ($sColumn == 'profile_id') {
                    if ($isExportProductCartVersion == true && $iMapVersionAndProfileId) {
                        $sValue = $iMapVersionAndProfileId;
                    }
                }
                if ($sColumn == 'order_times') {
                    if (isset($this->hanpukaiOrderTime) && $this->hanpukaiOrderTime > 0) {
                        $sValue = $this->hanpukaiOrderTime;
                    } else {
                        $sValue++;
                    }
                }

                $subProfileData[] = $sValue;
            }
        }

        if (empty($subProfileData)) {
            foreach ($aColumnProfileCart as $sColumn) {
                if (strpos($sColumn, 'product.subscription_profile_') !== false) {
                    $subProfileData[] = '';
                }
            }
        }


        $subProfileCartData = array_merge($subProfileCartData, $subProfileData);

        $subProfileCourseData = [];

        if ($iCourseId) {
            //subscription course
            $selectSubProfileCourse = $this->_connectionSales->select()->from(
                $this->_connectionSales->getTableName('subscription_course')
            )->where($this->_connectionSales->getTableName('subscription_course') . '.course_id = ?', $iCourseId);


            $querySubProfileCourse = $this->_connectionSales->query($selectSubProfileCourse);

            while ($subCourse = $querySubProfileCourse->fetch()) {
                /*convert subscription course date time columns to config timezone*/
                $subCourse = $this->convertDateTimeColumnsToConfigTimezone(self::COURSETYPE, $subCourse);

                foreach ($subCourse as $sColumn => $sValue) {
                    $subProfileCourseData[] = $sValue;
                }
            }
        }

        if (!count($subProfileCourseData)) {
            foreach ($aColumnProfileCart as $sColumn) {
                if (strpos($sColumn, 'product.course_') !== false) {
                    $subProfileCourseData[] = '';
                }
            }
        }


        $subProfileCartData = array_merge($subProfileCartData, $subProfileCourseData);


        return $subProfileCartData;
    }

    /**
     * @param $subProfile
     * @param $baseDir
     */
    public function exportHanpukaiSequenceSubProfileCartOnly($subProfile, $baseDir)
    {

        $arrFileMake = [];

        $arrProductCartDataExport = [];
        $arrExportProductCartHeader = [];

        $iProfileId = $subProfile['profile_id'];
        $arrDeliveryNeedExport = $subProfile['hanpukai_delivery_number'];
        foreach ($arrDeliveryNeedExport as $deliveryNumber) {
            $this->hanpukaiOrderTime = $deliveryNumber;

            if (!in_array($deliveryNumber, $arrFileMake)) {
                $arrFileMake[] = $deliveryNumber;
            }

            /*collect data for export subscription profile cart header*/
            list($arrExportProductCartHeader, $arrProductCartDataExport[$deliveryNumber]) = $this->makeArrSubProductCartSequenceExport($deliveryNumber, $subProfile, $arrExportProductCartHeader);
        }

        // Make file export
        foreach ($arrFileMake as $fileNumber) {
            $arrProductCartData = $arrProductCartDataExport[$fileNumber];

            $nameCsvSubProductCartHanpukaiSequence = 'subscription-profile-product-cart-version-' . $fileNumber . '-' . $this->_timezone->date()->format('Ymd') . '-' . $this->getConsumerName() . '.csv';

            $arrProductCartData = array_merge([$arrExportProductCartHeader], $arrProductCartData);

            if (count($arrProductCartData) > 1) {
                $nameCsvSubProductCartHanpukaiSequence = $baseDir . DS . $this->pathTmp . DS . $nameCsvSubProductCartHanpukaiSequence;
                $this->_dataHelper->writeFileLocal($nameCsvSubProductCartHanpukaiSequence, $arrProductCartData);
                $this->cartLogger->info('Write profile ' . $iProfileId . ' into ' . $nameCsvSubProductCartHanpukaiSequence . ' successfully');
            }
        }
    }

    /**
     * MakeArrSubProductCartSequenceExport
     *
     * @param $deliveryNumber
     * @param $profileDataDetail
     * @return array
     */
    public function makeArrSubProductCartSequenceExport($deliveryNumber, $profileDataDetail, $aColumnProfileCart)
    {
        $arrData = [];

        $productCartModel = $this->_productCartFactory->create()->getCollection();
        $productCartModel->addFieldToFilter('profile_id', $profileDataDetail['profile_id'], true);
        $productHanpukaiInfo = $productCartModel->getFirstItem();

        $arrOrderItem = $this->getInfoSimulateOrderWithId($profileDataDetail['profile_id'], $deliveryNumber);

        $productCartTotalData = [];
        foreach ($productCartModel->getItems() as $productCartRawData) {
            $iProductId = $productCartRawData->getProductId();
            $iParentId = (null != $productCartRawData->getParentItemId()) ? $productCartRawData->getParentItemId() : 0;
            $productCartTotalData[$iProductId . '_' . $iParentId] = $productCartRawData->getData();
        }

        foreach ($arrOrderItem as $item) {
            $aOrderItemData = [];

            $iProductId = $item['product_id'];
            $iParenId = 0;
            if (isset($item['parent_item_id']) && isset($arrOrderItem[$item['parent_item_id']])) {
                $iParenId = $arrOrderItem[$item['parent_item_id']]['product_id'];
            }

            $productObj = $this->_productRepository->getById($item['product_id']);

            if ($productObj && $productObj->getId()) {
                $productType = $productObj->getTypeId();
            } else {
                $productType = $sku = $productName = $price = '';
            }

            $productCartData = isset($productCartTotalData[$iProductId . '_' . $iParenId]) ? $productCartTotalData[$iProductId . '_' . $iParenId] : [];

            $aColumnProfileCart = $this->getDefaultColumn();
            if ($item) {
                if (empty($aColumnOrderItems)) {
                    $aColumnOrderItems = array_keys($item);
                }
                //get product cart data
                foreach ($aColumnOrderItems as $sColumnProfileCart) {
                    $aOrderItemData[] = isset($item[$sColumnProfileCart]) ? $item[$sColumnProfileCart] : '';
                }
            }

            if (empty($aOrderItemData)) {
                continue;
            }

            //handle and replace product hanpukai
            $productCartDataReplace = $productHanpukaiInfo->getData();
            $productCartDataReplace['qty'] = isset($productCartData['qty']) ? $productCartData['qty'] : 0;
            $productCartDataReplace['product_type'] = $productType;
            $productCartDataReplace['product_id'] = $iProductId;
            $productCartDataReplace['product_options'] = '';
            $productCartDataReplace['parent_item_id'] = '';
            $productCartDataReplace['created_at'] = '';
            $productCartDataReplace['updated_at'] = '';
            $productCartDataReplace['unit_case'] = isset($productCartData['unit_case']) ? $productCartData['unit_case'] : \Riki\CreateProductAttributes\Model\Product\UnitEc::UNIT_PIECE;
            $productCartDataReplace['unit_qty'] = isset($productCartData['unit_qty']) ? $productCartData['unit_qty'] : 1;

            //collection profile product cart
            $arrData[] = $this->getSubscriptionProfileCartData($productCartDataReplace, $aOrderItemData, $aColumnProfileCart, false, []);
        }

        return [$aColumnProfileCart, $arrData];
    }


    /**
     * @param $subProfile
     * @param bool $isExportProductCartVersion
     * @return array
     */
    public function exportSubProfileProductCart($subProfile, $isExportProductCartVersion = false)
    {
        $iProfileId = $subProfile['profile_id'];
        $iMapVersionAndProfileId = isset($subProfile['origin_profile_id']) ? $subProfile['origin_profile_id'] : null;

        $arrOrderItem = $this->getInfoSimulateOrderWithId($iProfileId);

        $exportSubProductCartHeader = [];
        $exportDataSubProductCart = [];
        $selectProfileCart = $this->_connectionSales->select()->from([
            'sc' => $this->_connectionSales->getTableName('subscription_profile_product_cart')
        ])->where('sc.profile_id  = ?', $iProfileId);

        $queryProfileCart = $this->_connectionSales->query($selectProfileCart);

        $productCartTotalData = [];
        while ($productCartRawData = $queryProfileCart->fetch()) {
            $iProductId = $productCartRawData['product_id'];
            $iParentId = isset($productCartRawData['parent_item_id']) ? $productCartRawData['parent_item_id'] : 0;
            $productCartTotalData[$iProductId . '_' . $iParentId] = $productCartRawData;
        }

        $aColumnProfileCart = $this->getDefaultColumn();
        $exportSubProductCartHeader[] = $this->getDefaultColumn();

        $aColumnOrderItems = [];
        //get data order item
        foreach ($arrOrderItem as $item) {
            $aOrderItemData = [];
            $iProductId = $item['product_id'];
            $iParenId = 0;
            $allChildrenItems = [];
            if (isset($item['parent_item_id']) && isset($arrOrderItem[$item['parent_item_id']])) {
                $iParenId = $arrOrderItem[$item['parent_item_id']]['product_id'];
                $iParentItemId = $arrOrderItem[$item['parent_item_id']]['item_id'];
                foreach ($arrOrderItem as $key => $eachOrderItem) {
                    if ($eachOrderItem['parent_item_id'] != null and $eachOrderItem['parent_item_id'] == $iParentItemId) {
                        $allChildrenItems[] = $arrOrderItem[$key];
                    }
                }
                $item = $this->bundleItemsHelper->reCalculateOrderItem($item, $arrOrderItem[$item['parent_item_id']], $allChildrenItems);
            }

            $productCartData = isset($productCartTotalData[$iProductId . '_' . $iParenId]) ? $productCartTotalData[$iProductId . '_' . $iParenId] : [];

            if (empty($aColumnOrderItems)) {
                $aColumnOrderItems = array_keys($item);
            }

            foreach ($aColumnOrderItems as $sColumnOrderItem) {
                $aOrderItemData[] = isset($item[$sColumnOrderItem]) ? $item[$sColumnOrderItem] : '';
            }

            if (empty($aOrderItemData)) {
                continue;
            }

            $productCartData['profile_id'] = $iProfileId;
            //collection profile product cart
            $exportDataSubProductCart[] = $this->getSubscriptionProfileCartData($productCartData, $aOrderItemData, $aColumnProfileCart, $isExportProductCartVersion, $iMapVersionAndProfileId);
        }

        $exportDataSubProductCart = array_merge($exportSubProductCartHeader, $exportDataSubProductCart);

        return $exportDataSubProductCart;
    }

    /**
     * @param $iProfileId
     * @param null $iDeliveryNumber
     * @return array
     */
    public function getInfoSimulateOrderWithId($iProfileId, $iDeliveryNumber = null)
    {
        $arrResult = [];

        $orderSimulate = null;

        if ($iDeliveryNumber) {
            $orderSimulate = $this->getSimulateOrderData($iProfileId, $iDeliveryNumber);
        } else {
            $orderSimulate = $this->getSimulateOrderData($iProfileId, 0);
        }

        if ($orderSimulate) {
            $arrResult = $this->extractInfoFromOrderSimulate($orderSimulate);
        }

        return $arrResult;
    }

    /**
     * ExtractInfoFromOrderSimulate
     *
     * @param $order
     * @return array
     */
    public function extractInfoFromOrderSimulate($order)
    {
        $arrResult = [];
        /* @var $order \Magento\Sales\Model\Order */
        if ($order && $order->getId()) {
            $iItemIds = [];
            foreach ($order->getAllItems() as $item) {
                $iItemIds[] = $item->getItemId();
            }

            if (!empty($iItemIds)) {
                //get data from sales_order and sales_order_item
                $selectSalesOrderItem = $this->subscriptionConnection->select()->from([
                    'sc' => $this->_connectionSales->getTableName('emulator_sales_order_item_tmp')
                ])->where('sc.item_id  IN(?)', $iItemIds);

                $querySalesOrderItem = $this->subscriptionConnection->query($selectSalesOrderItem);

                while ($salesOrderItemDataRow = $querySalesOrderItem->fetch()) {
                    /*convert sales_order_item date time columns to config timezone*/
                    $salesOrderItemDataRow = $this->convertDateTimeColumnsToConfigTimezone(
                        self::ORDERITEMTYPE,
                        $salesOrderItemDataRow
                    );
                    $arrResult[$salesOrderItemDataRow['item_id']] = $salesOrderItemDataRow;
                }
            }
        }
        return $arrResult;
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
        } else {
            if ($type == self::COURSETYPE) {
                $dateTimeColumns = $this->getSubscriptionCourseDateTimeColumns();
            } else {
                if ($type == self::ORDERITEMTYPE) {
                    $dateTimeColumns = $this->getOrderItemDateTimeColumns();
                }
            }
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
            'updated_date',
            'disengagement_date'
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
            'created_date',
            'updated_date',
            'launch_date',
            'close_date',
            'hanpukai_delivery_date_from',
            'hanpukai_delivery_date_to',
            'hanpukai_first_delivery_date'
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
}
