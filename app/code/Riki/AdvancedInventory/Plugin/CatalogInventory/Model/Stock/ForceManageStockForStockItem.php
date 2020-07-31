<?php

namespace Riki\AdvancedInventory\Plugin\CatalogInventory\Model\Stock;

class ForceManageStockForStockItem
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\CatalogInventory\Api\StockItemRepositoryInterface
     */
    protected $resourceStockItem;

    /**
     * @var \Magento\Framework\Debug
     */
    protected $debugObject;

    /**
     * @var \Riki\AdvancedInventory\Logger\LoggerImportProductSapApi
     */
    protected $loggerImportSap;

    /**
     * ForceManageStockForStockItem constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\CatalogInventory\Model\ResourceModel\Stock\Item $resourceStockItem
     * @param \Magento\Framework\Debug $debugObject
     * @param \Riki\AdvancedInventory\Logger\LoggerImportProductSapApi $loggerImportSap
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\Item $resourceStockItem,
        \Magento\Framework\Debug $debugObject,
        \Riki\AdvancedInventory\Logger\LoggerImportProductSapApi $loggerImportSap
    ) {
        $this->registry = $registry;
        $this->resourceStockItem = $resourceStockItem;
        $this->debugObject = $debugObject;
        $this->loggerImportSap = $loggerImportSap;
    }

    /**
     * Extend save()
     *
     * @param \Magento\CatalogInventory\Model\Stock\StockItemRepository $subject
     * @param \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
     * @return void
     */
    public function beforeSave(
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $subject,
        \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
    ) {
        // Log data for case manage_stock = 0 when update product by admin catalog, import SAP API ...
        if (!is_null($stockItem->getData('manage_stock')) && $stockItem->getData('manage_stock') == 0) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/NED-1616.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);

            $logData = [
                'product_id' => $stockItem->getData('product_id'),
                'manage_stock' => $stockItem->getData('manage_stock'),
                'use_config_manage_stock' => $stockItem->getData('use_config_manage_stock')
            ];

            $exception = new \Exception(json_encode($logData));
            $logger->info($exception->getMessage() . "\n" . $exception->getTraceAsString());
        }
    }

    /**
     * Extend save()
     *
     * @param \Magento\CatalogInventory\Model\Stock\StockItemRepository $subject
     * @param \Magento\CatalogInventory\Api\Data\StockItemInterface $result
     *
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    public function afterSave(
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $subject,
        \Magento\CatalogInventory\Api\Data\StockItemInterface $result
    ) {
        // Only do for action import product by SAP API
        $createProductSapApi = $this->registry->registry(
            \Riki\Catalog\Model\SapProductRepository::CREATE_PRODUCT_SAP_API
        );

        if (!$createProductSapApi) {
            return $result;
        }

        if (isset($createProductSapApi['is_new'])
            && !is_null($result->getData('use_config_manage_stock'))
            && !is_null($result->getData('manage_stock'))
        ) {
            if ($result->getData('use_config_manage_stock') && $result->getData('manage_stock')) {
                return $result;
            } else {
                $productSku = isset($createProductSapApi['sku']) ? $createProductSapApi['sku'] : $result->getProductId();
                // if use_config_manage_stock = 0, update value to 1
                if ($result->getData('use_config_manage_stock') == 0) {
                    // Add log
                    $this->loggerImportSap->info(sprintf(
                        'Product Sku #%s has field [use_config_manage_stock = 0]',
                        $productSku
                    ));

                    $result->setUseConfigManageStock(1);
                }

                // Get global config manage_stock
                $manage_stock = $result->getManageStock();
                $result->setManageStock($manage_stock);

                try {
                    // Update value for manage_stock and use_config_manage_stock
                    // follow the global configuration of the website.
                    $this->resourceStockItem->save($result);
                    $this->loggerImportSap->info(sprintf(
                        'Product Sku #%s has been updated the field [manage_stock] with global config value is %s',
                        $productSku,
                        $manage_stock
                    ));
                } catch (\Exception $e) {
                    $this->loggerImportSap->info(sprintf(
                        'Product Sku #%s can not update the field [manage_stock]',
                        $productSku
                    ));
                    $this->loggerImportSap->critical($e);
                }
            }
        }

        return $result;
    }
}
