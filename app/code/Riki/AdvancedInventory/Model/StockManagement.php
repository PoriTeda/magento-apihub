<?php

namespace Riki\AdvancedInventory\Model;

use Wyomind\AdvancedInventory\Helper\Journal;

class StockManagement implements \Riki\AdvancedInventory\Api\StockManagementInterface
{
    /**
     * @var \Riki\AdvancedInventory\Api\PosManagementInterface
     */
    protected $posManagement;
    /**
     * @var \Riki\AdvancedInventory\Model\Stock
     */
    protected $stockModel;

    /**
     * @var \Riki\AdvancedInventory\Model\ResourceModel\StockFactory
     */
    protected $stockResourceModel;

    /**
     * @var Journal
     */
    protected $journalHelper;

    /**
     * @var \Magento\CatalogInventory\Model\StockRegistry
     */
    protected $stockRegistry;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * StockManagement constructor.
     * @param \Riki\AdvancedInventory\Api\PosManagementInterface $posManagement
     * @param Stock $stockModel
     * @param ResourceModel\StockFactory $stockResourceFactory
     * @param Journal $journalHelper
     * @param \Magento\CatalogInventory\Model\StockRegistry $stockRegistry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Riki\AdvancedInventory\Api\PosManagementInterface $posManagement,
        \Riki\AdvancedInventory\Model\Stock $stockModel,
        \Riki\AdvancedInventory\Model\ResourceModel\StockFactory $stockResourceFactory,
        \Wyomind\AdvancedInventory\Helper\Journal $journalHelper,
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->posManagement = $posManagement;
        $this->stockModel = $stockModel;
        $this->stockResourceModel = $stockResourceFactory;
        $this->journalHelper = $journalHelper;
        $this->stockRegistry = $stockRegistry;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param array $productIds
     * @param array $placeIds
     * @return array
     */
    public function lockProductsStocks(array $productIds, $placeIds = [])
    {
        /** @var ResourceModel\Stock $stockResourceModel */
        $stockResourceModel = $this->stockResourceModel->create();
        return $stockResourceModel->lockProductsStocks($productIds, $placeIds);
    }

    /**
     * Get product stock settings for specific warehouse
     *
     * @param $productId
     * @param array $placeIds
     * @param bool $storeId
     * @return \Magento\Framework\DataObject|mixed
     */
    public function getStockSettingsByPlaceIdsAndStoreId($productId, $placeIds = [], $storeId = false)
    {
        if (!$storeId) {
            return $this->stockModel->getStockSettings($productId, false, $placeIds, false);
        }

        /*list of place is which is assigned for store id*/
        $posForStoreId = $this->posManagement->getPlaceIdsByStoreId($storeId);

        if (!empty($posForStoreId)) {
            if (!empty($placeIds)) {
                $placeIds = array_intersect($placeIds, $posForStoreId);
            } else {
                $placeIds = $posForStoreId;
            }

            if (!empty($placeIds)) {
                return $this->stockModel->getStockSettings($productId, false, $placeIds, false);
            }
        }

        return new \Magento\Framework\DataObject();
    }

    /**
     * @param $productId
     * @param null|string $context
     * @param null $orderId
     * @return $this
     */
    public function updateCatalogInventoryStock($productId, $context = Journal::SOURCE_API, $orderId = null)
    {
        $inventory = $this->stockRegistry->getStockItem($productId);
        $stock = $this->stockModel->getStockSettings($productId);

        if ($stock->getMultistockEnabled()) {
            $inventory->setUseConfigBackorders(false);
            $inventory->setBackorders($stock->getBackorderableAtStockLevel());

            $reference = $orderId? "O#$orderId,P#$productId" : "P#$productId";

            // Update qty
            if ($inventory->getQty() != $stock->getQuantityInStock()) {
                $this->journalHelper->insertRow(
                    $context,
                    Journal::ACTION_QTY,
                    $reference,
                    ['from' => $inventory->getQty(), 'to' => $stock->getQuantityInStock()]
                );
                $inventory->setQty($stock->getQuantityInStock());
            }
            // Update is in stock status
            if ($this->scopeConfig->getValue('advancedinventory/settings/auto_update_stock_status')) {
                $stockStatus = $stock->getStockStatus();
                if ($stockStatus != $inventory->getIsInStock()) {
                    $to = ($stockStatus) ? 'In stock' : 'Out of stock';
                    $from = ($inventory->getIsInStock()) ? 'In stock' : 'Out of stock';
                    $this->journalHelper->insertRow(
                        $context,
                        Journal::ACTION_IS_IN_STOCK,
                        $reference,
                        ['from' => $from, 'to' => $to]
                    );
                    $inventory->setIsInStock($stockStatus);
                }
            }

            $inventory->save();
        }

        return $this;
    }
}
