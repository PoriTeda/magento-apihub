<?php

namespace Riki\Catalog\Model;

class StockState
{
    /**
     * @var \Riki\Catalog\Stock\Pool
     */
    protected $stockPool;

    /**
     * @var \Riki\PointOfSale\Model\PointOfSaleManagement
     */
    protected $pointOfSaleManagement;

    /**
     * StockState constructor.
     *
     * @param \Riki\Catalog\Stock\Pool $stockPool
     * @param \Riki\PointOfSale\Model\PointOfSaleManagement $pointOfSaleManagement
     */
    public function __construct(
        \Riki\Catalog\Stock\Pool $stockPool,
        \Riki\PointOfSale\Model\PointOfSaleManagement $pointOfSaleManagement
    ) {
        $this->stockPool = $stockPool;
        $this->pointOfSaleManagement = $pointOfSaleManagement;
    }

    /**
     * get list of place id
     *
     * @return array
     */
    public function getPlaceIds()
    {
        return $this->pointOfSaleManagement->getPlaceIds();
    }

    /**
     * product is salable
     *
     * @param $product
     * @param $placeIds
     * @return mixed
     */
    public function isSalable($product, $placeIds = [])
    {
        $productType = $product->getTypeId();

        /** @var \Riki\Catalog\Stock\AbstractStock $stock */
        $stock = $this->stockPool->get($productType);

        return $stock->isSalable($product, $placeIds);
    }

    /**
     * Check quantity of product which a customer can buy
     *
     * @param $product
     * @param $qty
     * @param array $placeIds
     * @return bool
     */
    public function canAssigned($product, $qty, $placeIds = [])
    {
        $productType = $product->getTypeId();

        /** @var \Riki\Catalog\Stock\AbstractStock $stock */
        $stock = $this->stockPool->get($productType);

        return $stock->canAssigned($product, $qty, $placeIds);
    }
}