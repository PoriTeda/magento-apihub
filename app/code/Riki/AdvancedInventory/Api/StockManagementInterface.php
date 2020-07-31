<?php

namespace Riki\AdvancedInventory\Api;
use Wyomind\AdvancedInventory\Helper\Journal;

/**
 * @api
 */
interface StockManagementInterface
{
    /**
     * @param array $productIds
     * @param array $placeIds
     * @return mixed
     */
    public function lockProductsStocks(array $productIds, $placeIds = []);

    /**
     * @param $productId
     * @param array $placeIds
     * @param $storeId
     * @return mixed
     */
    public function getStockSettingsByPlaceIdsAndStoreId($productId, $placeIds = [], $storeId = false);

    /**
     * @param $productId
     * @param string $context
     * @param null $orderId
     * @return mixed
     */
    public function updateCatalogInventoryStock($productId, $context = Journal::SOURCE_API, $orderId = null);
}
