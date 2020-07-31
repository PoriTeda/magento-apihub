<?php

namespace Riki\Catalog\Stock;

class SimpleProductStock extends AbstractStock
{
    /**
     * @var \Riki\AdvancedInventory\Model\Assignation
     */
    protected $assignation;

    /**
     * SimpleProductStock constructor.
     *
     * @param \Magento\CatalogInventory\Model\StockRegistry $stockRegistry
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\AdvancedInventory\Model\StockFactory $stockFactory
     * @param \Riki\AdvancedInventory\Model\Assignation $assignation
     */
    public function __construct(
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\AdvancedInventory\Model\StockFactory $stockFactory,
        \Riki\AdvancedInventory\Model\Assignation $assignation
    ) {
        parent::__construct($stockRegistry, $productRepository, $stockFactory);
        $this->assignation = $assignation;
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
        if (!$product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
            return parent::canAssigned($product, $qty, $placeIds);
        }

        foreach ($placeIds as $placeId) {
            $stockStatus = $this->assignation->checkAvailability($product->getId(), $placeId, $qty, null);

            $qty = $stockStatus['remaining_qty_to_assign'];

            if ($stockStatus['status']
                >= \Riki\AdvancedInventory\Model\Assignation::STOCK_STATUS_AVAILABLE_BACK_ORDER
            ) {
                return true;
            }
        }

        return false;
    }
}
