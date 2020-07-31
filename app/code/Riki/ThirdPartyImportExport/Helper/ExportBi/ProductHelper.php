<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

class ProductHelper extends \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ExportHelper
{
    const CONFIG_DEBUG_LOG = 'di_data_export_setup/data_cron_product/debug_log';

    //define attribute product get value
    const DEFINE_ARRAY = ['stock_display_type','status','visibility','case_display','unit_sap','limit_user_unit'];

    const DEFAULT_PH_DESCRIPTION = ['ph1_description','ph2_description','ph3_description','ph4_description','ph5_description'];
    const ATTRIBUTE_PH_CODE = 'ph_code';
    const ATTRIBUTE_BH_SAP = 'bh_sap';
    const ATTRIBUTE_AVAILABLE_SUBSCRIPTION = 'available_subscription';
    const ATTRIBUTE_DELIVERY_TYPE = "delivery_type";
    const ATTRIBUTE_TAX_CLASS_ID = "tax_class_id";

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_productModel;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;
    /**
     * @var \Magento\CatalogInventory\Model\Stock\Item
     */
    protected $_stockItem;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory
     */
    protected $_eavAttributeCollectionFactory;

    /*array of all stock columns which value is ''*/
    protected $_stockItemEmptyValue = [];

    /**
     * ProductHelper constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Product $productModel
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\CatalogInventory\Model\Stock\Item $stockItem
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $eavAttributeCollectionFactory
     * @param GlobalHelper\SftpExportHelper $sftpHelper
     * @param GlobalHelper\FileExportHelper $fileHelper
     * @param GlobalHelper\ConfigExportHelper $configHelper
     * @param GlobalHelper\EmailExportHelper $emailHelper
     * @param GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\CatalogInventory\Model\Stock\Item $stockItem,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $eavAttributeCollectionFactory,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\SftpExportHelper $sftpHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper $configHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\EmailExportHelper $emailHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper
    ) {
        parent::__construct($context, $dateTime, $timezone, $resourceConfig, $sftpHelper, $fileHelper, $configHelper, $emailHelper, $dateTimeColumnsHelper, $connectionHelper);
        $this->_storeManager = $storeManager;
        $this->_productModel= $productModel;
        $this->_productFactory = $productFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_stockItem = $stockItem;
        $this->_eavAttributeCollectionFactory = $eavAttributeCollectionFactory;
    }

    /**
     * Export process
     */
    public function exportProcess()
    {
        $this->export();

        /* set last time to run */
        $this->setLastRunToCron();

        /*move export folder to ftp*/
        $this->_sftpHelper->moveFileToFtp($this->_pathTmp, $this->_path, $this->getSFTPPathExport(), $this->getReportPathExport());

        /*send email notify*/
        $this->sentNotificationEmail();
    }

    /**
     * @return bool
     */
    public function export()
    {
        /*export data*/
        $arrayExport = [];

        /*push header columns to export data*/
        array_push($arrayExport, $this->getProductExportColumns());

        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');

        $productInventory = $this->getCatalogInventoryData();

        /** @var \Magento\Catalog\Model\Product $item */
        foreach ($collection as $item) {

            $data = [];

            $data = $this->getProductAttributeData($item, $data);

            $data = $this->getAdditionalData($item, $data);

            if (isset($productInventory[$item->getId()])) {
                $data = array_merge($data,$productInventory[$item->getId()]);
            } else {
                $data = array_merge($data, $this->_stockItemEmptyValue);
            }

            /*push product data to export array*/
            array_push($arrayExport, $data);
        }

        /*export date*/
        $exportDate = $this->_timezone->date()->format('YmdHis');

        /*export file name*/
        $exportFileName = 'products-'.$exportDate.'.csv';

        $this->createLocalFile([
            $exportFileName => $arrayExport
        ]);
    }

    /**
     * get product export columns for header data
     *
     * @return array
     */
    public function getProductExportColumns()
    {
        $rs = [];

        /*push product attribute to header*/
        foreach ($this->_productModel->getAttributes() as $attribute) {

            /*add attribute code to header with prefix is 'product.' */
            array_push($rs, 'product.'.$attribute->getAttributeCode());

            /*additional columns after bh_sap and ph_code*/
            if ($attribute->getAttributeCode() == self::ATTRIBUTE_BH_SAP
                || $attribute->getAttributeCode() == self::ATTRIBUTE_PH_CODE
            ) {
                array_push($rs, 'product.'.$attribute->getAttributeCode().'_description');
            }

            /*additional columns after list ph_description columns*/
            if (in_array($attribute->getAttributeCode(),self::DEFAULT_PH_DESCRIPTION)) {
                array_push($rs, 'product.' .substr($attribute->getAttributeCode(),0,4).'code');
            }
        }

        /*additional columns product.url, product.sale_flg, product.related_skus, product.crossell_skus, product.upsell_skus*/
        array_push($rs, 'product.url');
        array_push($rs, 'product.sale_flg');
        array_push($rs, 'product.related_skus');
        array_push($rs, 'product.crossell_skus');
        array_push($rs, 'product.upsell_skus');

        /*get all columns of stock item table*/
        $stockItemColumns = $this->getStockItemColumns();

        /*push stock item columns to header*/
        foreach ($stockItemColumns as $key => $column) {

            /*push stock item column to header with prefix is 'product.inventory_'*/
            array_push($rs, 'product.inventory_'. $key);

            /*push an empty value for stock empty value array*/
            array_push($this->_stockItemEmptyValue, '');
        }

        return $rs;
    }

    /**
     * Get product attribute data
     *
     * @param $item
     * @param $data
     * @return array
     */
    public function getProductAttributeData($item, $data)
    {
        /*ec store id*/
        $ecStoreId = $this->_storeManager->getDefaultStoreView()->getId();

        /** @var \Magento\Catalog\Model\Product $productModel */
        $productModel = $this->_productFactory->create();

        /*product data fo ec view*/
        $itemEcStore = $productModel->setStoreId($ecStoreId)->load($item->getId());

        foreach ($this->_productModel->getAttributes() as $attributeProduct) {

            /*attribute id*/
            $attributeId = $attributeProduct->getId();

            /*attribute code*/
            $attributeCode = $attributeProduct->getAttributeCode();

            /*attribute value - admin view*/
            $attributeValue = $item->getData($attributeCode);

            /**
             * $optionId, $optionEcId
             * only use for product attribute which input type is drop down or multi select
             */

            /* option id use to get product attribute value - admin view */
            $optionId = $attributeValue;

            /*option id use to get product attribute value - ec view -default is same with admin view*/
            $optionEcId = $attributeValue;

            /*this attribute has value for ec store*/
            if ($itemEcStore->getExistsStoreValueFlag($attributeCode)) {

                if (!empty($itemEcStore->getData($attributeCode))) {
                    $optionEcId = $itemEcStore->getData($attributeCode);
                }
            }

            /*use attribute value from Ec view for all store view if all store view value is null*/
            if (!empty($optionEcId) && empty($optionId)) {
                $optionId = $optionEcId;
            }

            /*if this attribute is null,it will be set default is 0*/
            if ($attributeCode == self::ATTRIBUTE_AVAILABLE_SUBSCRIPTION) {
                if (!$attributeValue) {
                    array_push($data, 0);
                } else {
                    array_push($data, $attributeValue);
                }
                continue;
            }

            if (is_array($attributeValue)) {
                $dataArray = $this->getValueFromArray($attributeValue);
                if (is_array($dataArray)) {
                    array_push($data, implode(',', $dataArray));
                } else {
                    array_push($data, '');
                }
                continue;
            }
            // format to config default timezone
            if ($attributeProduct->getBackendType() == \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME) {
                array_push(
                    $data,
                    $this->_dateTime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($attributeValue, 2, 2))
                );
                continue;
            }

            if (in_array($attributeCode, self::DEFAULT_PH_DESCRIPTION)) {

                /*option label for all store*/
                $optionLabelForAllStore = $this->getAttributeOptionValue( $attributeId, $optionId, 0);

                /*option label for Ec view*/
                $optionLabelForEcView = $this->getAttributeOptionValue( $attributeId, $optionEcId, $ecStoreId);

                if (empty($optionLabelForAllStore) && !empty($optionLabelForEcView)) {
                    $optionLabelForAllStore = $this->getAttributeOptionValue( $attributeId, $optionEcId, 0);
                }

                /*ec view*/
                array_push( $data, $optionLabelForEcView);
                /*admin*/
                array_push( $data, $optionLabelForAllStore);
                continue;
            }

            if ($attributeCode == self::ATTRIBUTE_BH_SAP) {

                /*option label for all store*/
                $optionLabelForAllStore = $this->getAttributeOptionValue( $attributeId, $optionId, 0);

                /*option label for Ec view*/
                $optionLabelForEcView = $this->getAttributeOptionValue( $attributeId, $optionEcId, $ecStoreId);

                if (empty($optionLabelForAllStore) && !empty($optionLabelForEcView)) {
                    $optionLabelForAllStore = $this->getAttributeOptionValue( $attributeId, $optionEcId, 0);
                }
                /*get value in Admin*/
                array_push( $data, $optionLabelForAllStore);
                /*get value in Ec view*/
                array_push( $data, $optionLabelForEcView);
                continue;
            }

            if ($attributeCode == self::ATTRIBUTE_PH_CODE) {

                /*option label for all store*/
                $optionLabelForAllStore = $this->getAttributeOptionValue( $attributeId, $optionId, 0);

                /*option label for Ec view*/
                $optionLabelForEcView = $this->getAttributeOptionValue( $attributeId, $optionEcId, $ecStoreId);

                if (empty($optionLabelForAllStore) && !empty($optionLabelForEcView)) {
                    $optionLabelForAllStore = $this->getAttributeOptionValue( $attributeId, $optionEcId, 0);
                }
                /*get value in Admin*/
                array_push( $data, $optionLabelForAllStore);
                /*get value in Ec view*/
                array_push( $data, $optionLabelForEcView);
                continue;
            }

            if ($attributeCode == self::ATTRIBUTE_DELIVERY_TYPE) {
                array_push($data, $attributeValue);
                continue;
            }

            if ($attributeCode == self::ATTRIBUTE_TAX_CLASS_ID) {
                array_push($data, $attributeValue);
                continue;
            }
            /*if attribute is select or multi select get label*/
            if ($attributeProduct->usesSource() && !in_array($attributeCode,self::DEFINE_ARRAY)) {
                array_push( $data, $this->getAttributeOptionValue( $attributeId, $optionId, 0));
            } else {
                array_push($data, $attributeValue);
            }
        }

        return $data;
    }

    /**
     * Get additional exported data for product
     *
     * @param $item
     * @param $data
     * @return mixed
     */
    public function getAdditionalData($item, $data)
    {
        /*product.url data*/
        array_push($data, $item->getUrlInStore(['_scope' => $this->getStoreIdFirst()]));

        /*product.sale_flg data*/
        $saleFlg = $item->isSalable() ? 1 : 0;
        array_push($data, $saleFlg);

        /*product.related_skus data*/
        $groupSkuRelated = [];

        if ($item->getRelatedProducts()) {
            foreach ($item->getRelatedProductCollection()->setPositionOrder() as $product) {
                array_push($groupSkuRelated, $product->getSku());
            }
        }

        array_push($data, implode(',' , $groupSkuRelated));

        /*product.related_skus data*/
        $groupSkuCrossSell = [];

        if ($item->getCrossSellProducts()) {
            foreach ($item->getCrossSellProductCollection()->setPositionOrder() as $product) {
                array_push($groupSkuCrossSell, $product->getSku());
            }
        }

        array_push($data, implode(',' , $groupSkuCrossSell));

        /*product.upsell_skus data*/
        $groupSkuUpSell = [];

        if ($item->getUpSellProducts()) {
            foreach ($item->getUpSellProductCollection()->setPositionOrder() as $product) {
                array_push($groupSkuUpSell, $product->getSku());
            }
        }

        array_push($data, implode(',' , $groupSkuUpSell));

        return $data;
    }


    /**
     * @param array $array
     * @return array|void
     */
    public function getValueFromArray($array = [])
    {
        $data = [];
        if (!is_array($array)) {
            return;
        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                return $this->getValueFromArray($value);
            } else {
                $data[] = $value;
            }
        }
        return $data;
    }
    /**
     * function getStoreIdFirst
     * @return int
     */
    public function getStoreIdFirst()
    {
        foreach ($this->_storeManager->getStores() as $store) {
            return $store->getId();
        }
    }

    /**
     * get all columns of stock item table
     *
     * @return array
     */
    public function getStockItemColumns()
    {
        $resource = $this->_stockItem->getResource();
        $connection = $resource->getConnection();
        return $connection->describeTable($resource->getMainTable());
    }

    /**
     * Get catalog inventory data
     *
     * @return array
     */
    public function getCatalogInventoryData()
    {
        $inventoryData = [];

        $resource = $this->_stockItem->getResource();

        $connection = $resource->getConnection();

        $select = $connection->select();

        $select->from(
            $resource->getMainTable(),'*'
        );

        $productInventory = $connection->fetchAll($select);

        if (!empty($productInventory)) {
            foreach ($productInventory as $row) {
                $inventoryData[$row['product_id']] = $row;
            };
        }

        return $inventoryData;
    }

    /**
     * get attribute option value
     *
     * @param $attributeId
     * @param $valueId
     * @param int $storeId
     * @return string
     */
    public function getAttributeOptionValue($attributeId, $valueId, $storeId = 0)
    {
        $label = [];

        $valueIds = explode(',', $valueId);

        $options = $this->_eavAttributeCollectionFactory->create()
            ->setPositionOrder('asc')
            ->setAttributeFilter($attributeId)
            ->addFieldToFilter('main_table.option_id', ['in' => $valueIds])
            ->setStoreFilter($storeId,false)
            ->load()
            ->toOptionArray();
        if ($options && isset($options[0]['label'])) {
            foreach ($options as $option) {
                array_push($label, $option['label']);
            }
        }

        return implode(',',$label);
    }

    /**
     * Debug log to tracking why this export file is empty
     *
     * @param $exportData
     * @param $exportHeader
     */
    public function debugLog($exportData, $exportHeader)
    {
        if ($this->getDebugLog()) {
            $this->_logger->info('====Begin Debug Log====');
            $this->_logger->info('Export data: '. sizeof($exportData));
            $this->_logger->info('Export Header: '. sizeof($exportHeader));
            $this->_logger->info('====End Debug Log====');
        }
    }

    /**
     * Get debug flag
     *
     * @return mixed
     */
    public function getDebugLog()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::CONFIG_DEBUG_LOG,$storeScope);
    }
}