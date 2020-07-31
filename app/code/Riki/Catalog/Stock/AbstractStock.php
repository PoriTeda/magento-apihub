<?php

namespace Riki\Catalog\Stock;

class AbstractStock
{
    /**
     * @var \Magento\CatalogInventory\Model\StockRegistry
     */
    protected $stockRegistry;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Riki\AdvancedInventory\Model\StockFactory
     */
    protected $stockFactory;

    /**
     * AbstractStock constructor.
     *
     * @param \Magento\CatalogInventory\Model\StockRegistry $stockRegistry
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\AdvancedInventory\Model\StockFactory $stockFactory
     */
    public function __construct(
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\AdvancedInventory\Model\StockFactory $stockFactory
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->productRepository = $productRepository;
        $this->stockFactory = $stockFactory;
    }

    /**
     * Product is salable
     *
     * @param $product
     * @param array $placeIds
     * @return bool
     */
    public function isSalable($product, $placeIds = [])
    {
        /*product is disabled add to cart*/
        if ($product->getDisableAddToCart()) {
            return false;
        }

        /*product is inactive*/
        if ($product->getStatus() == 2) {
            return false;
        }

        return $this->isInStock($product, $placeIds);
    }

    /**
     * Get product stock status
     *
     * @param $product
     * @param array $placeIds
     * @return bool {product->getIsSalable() = @return}
     */
    public function isInStock($product, $placeIds = [])
    {
        /*get product stock data*/
        $productStock = $this->getStockSettingsByPlaceIdsAndStoreId(
            $product->getId(),
            $placeIds,
            $product->getStoreId()
        );

        if ($productStock->getStockStatus()) {
            return true;
        }

        return false;
    }

    /**
     * Check quantity of product which a customer can buy
     *
     * @param $product
     * @param $qty
     * @param array $placeIds
     * @return bool {true : can add $qty item to cart, false: can not add $qty items to cart}
     */
    public function canAssigned($product, $qty, $placeIds = [])
    {
        $productStock = $this->getStockSettingsByPlaceIdsAndStoreId(
            $product->getId(),
            $placeIds,
            $product->getStoreId()
        );

        if (!$productStock->getStockStatus()) {
            return false;
        }

        if ($productStock->getQuantityInStock() > $qty) {
            return true;
        }

        /*product is allowed back order*/
        if ($productStock->getData('backorderable_at_product_level')) {
            if ($productStock->getData('backorder_limit_in_stock') > 0) {
                /*product stock (quantity in stock + back order limit)*/
                $currentStock = (int)$productStock->getQuantityInStock()
                    + (int)$productStock->getData('backorder_limit_in_stock');

                if ($currentStock > $qty) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get product stock settings
     *      for special list of warehouse
     *
     * @param $productId
     * @param $placeIds
     * @param $storeId
     * @return \Magento\Framework\DataObject|mixed
     */
    public function getStockSettingsByPlaceIdsAndStoreId($productId, $placeIds, $storeId)
    {
        /** @var \Riki\AdvancedInventory\Model\Stock $stockModel */
        $stockModel = $this->stockFactory->create();

        if ($placeIds) {
            return $stockModel->getStockSettingsByPlaceIdsAndStoreId($productId, $placeIds, $storeId);
        } else {
            return $stockModel->getStockSettingsByStoreId($productId, $storeId);
        }
    }

    /**
     * Get product stock settings
     *      for special warehouse
     *
     * @param $productId
     * @param $placeId
     * @param $storeId
     * @return \Magento\Framework\DataObject|mixed
     */
    public function getStockSettingsByPlaceIdAndStoreId($productId, $placeId, $storeId)
    {
        /** @var \Riki\AdvancedInventory\Model\Stock $stockModel */
        $stockModel = $this->stockFactory->create();

        return $stockModel->getStockSettingsByPlaceIdAndStoreId($productId, $placeId, [], false, $storeId);
    }

    /**
     * Get product by id
     *
     * @param $productId
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProductById($productId)
    {
        try {
            return $this->productRepository->getById($productId);
        } catch (\Exception $e) {
            return false;
        }
    }
}
