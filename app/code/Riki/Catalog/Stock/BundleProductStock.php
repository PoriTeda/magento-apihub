<?php

namespace Riki\Catalog\Stock;

class BundleProductStock extends AbstractStock
{
    /**
     * @var \Riki\Catalog\Model\Product\Bundle\Type
     */
    protected $bundleType;

    /**
     * BundleProductStock constructor.
     *
     * @param \Magento\CatalogInventory\Model\StockRegistry $stockRegistry
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\AdvancedInventory\Model\StockFactory $stockFactory
     * @param \Riki\Catalog\Model\Product\Bundle\Type $bundleType
     */
    public function __construct(
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\AdvancedInventory\Model\StockFactory $stockFactory,
        \Riki\Catalog\Model\Product\Bundle\Type $bundleType
    ) {
        parent::__construct($stockRegistry, $productRepository, $stockFactory);
        $this->bundleType = $bundleType;
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
        if (!$this->stockRegistry->getStockItem($product->getId(), "product_id")->getIsInStock()) {
            return false;
        }

        return $this->bundleType->isSalable($product);
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
        /*bundle option*/
        $optionCollection = $this->bundleType->getOptionsCollection($product);

        if (!$optionCollection->getItems()) {
            return false;
        }

        /*bundle option data*/
        $selectionCollection = $this->bundleType->getSelectionsCollection(
            $optionCollection->getAllIds(),
            $product
        );

        if (!$selectionCollection->getItems()) {
            return false;
        }

        if (!$placeIds) {
            return false;
        }

        foreach ($placeIds as $placeId) {
            $canAssigned = $this->canAssignedForSpecificPlaceId($selectionCollection, $placeId, $qty);
            /*all child products are in stock for any place id*/
            if ($canAssigned) {
                return true;
            }
        }

        return false;
    }

    /**
     * Can assigned for specific place id
     *      Logic: all child items are in stock at one warehouse
     *
     * @param $bundleChildrenData
     * @param $placeId
     * @param $qty
     * @return bool
     */
    protected function canAssignedForSpecificPlaceId($bundleChildrenData, $placeId, $qty)
    {
        foreach ($bundleChildrenData as $selection) {
            /*child item is not salable*/
            if (!$selection->isSalable()) {
                return false;
            }

            /*product stock data*/
            $stock = $this->getStockSettingsByPlaceIdAndStoreId(
                $selection->getId(),
                $placeId,
                [],
                false,
                $selection->getStoreId()
            );

            /*total qty which child product will be add to cart*/
            $needQty = $selection->getSelectionQty() * $qty;

            /*Stock for sub items*/
            $stockItem = $this->bundleType->getStockRegistry()->getStockItem(
                $selection->getId(),
                $selection->getStore()->getWebsiteId()
            );

            /*child item is pre-order product - wrong config*/
            if ($stockItem->getBackorders() == \Riki\Preorder\Helper\Data::BACKORDERS_PREORDER_OPTION) {
                return false;
            }

            /*quantity in stock is not enough to assign*/
            if ($stock->getQuantityInStock() < $needQty) {
                /*not allowed back order for this warehouse*/
                if (!$stock->getBackorderableAtStockLevel()) {
                    return false;
                }

                /*warehouse quantity - include back order limit*/
                $backOrderQuantity = (int) $stock->getQuantityInStock() + (int) $stock->getBackorderLimitInStock();

                /*quantity (include back order limit - is not enough)*/
                if ($backOrderQuantity < $needQty) {
                    return false;
                }
            }
        }

        return true;
    }
}
