<?php

/**
 * @api
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Api;

interface StockRepositeryInterface
{

    /**
     *
     * @api
     * @return string
     */
    public function getAllPointOfSaleAndWarehouse();

    /**
     *
     * @api
     * @param  int storesId
     * @return string
     */
    public function getPointOfSaleAndWarehouseByStoreId($storeId);

    /**
     *
     * @api
     * @param int productId
     * @return string
     */
    public function getStockByProductId($productId);

    /**
     *
     * @api
     * @param int $productId
     * @param int[] $placeIds
     * @return string
     */
    public function getStockByProductIdAndPlaceIds(
        $productId,
        $placeIds
    );

    /**
     *
     * @param int $productId
     * @param int[] $storeIds
     * @return string
     */
    public function getStockByProductIdAndStoreIds(
        $productId,
        $storeIds
    );

    /**
     *
     * @api
     * @param int $productId
     * @param int $multistockEnabled
     * @param int $placeId
     * @param bool $manageStock
     * @param int $quantityInStock
     * @param int $backorderAllowed
     * @param bool $useConfigSettingForBackorders
     * @return boolean
     */
    public function updateStock(
        $productId,
        $multistockEnabled = 1,
        $placeId = false,
        $manageStock = true,
        $quantityInStock = 0,
        $backorderAllowed = 0,
        $useConfigSettingForBackorders = 1
    );

    /**
     *
     * @api
     * @param int $productId
     * @param bool $stockStatus
     * @return boolean
     */
    public function updateInventory(
        $productId,
        $stockStatus = true
    );
}
