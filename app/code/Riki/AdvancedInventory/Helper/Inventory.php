<?php

namespace Riki\AdvancedInventory\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Inventory extends AbstractHelper
{
    /**
     * @var \Magento\Bundle\Model\Product\Type
     */
    protected $typeBundle;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\Catalog\Model\Product\Bundle\Type $typeBundle,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry

    ) {
        parent::__construct($context);
        $this->typeBundle = $typeBundle;
        $this->stockRegistry = $stockRegistry;
    }


    /**
     * Check Bundle item available in same warehouse
     *
     * @param $product
     * @param $qty
     *
     * @return bool
     */
    public function checkWarehouseBundle($product, $qty, $type = 0)
    {
        $bundleQty = $this->typeBundle->checkWarehouseBundle($product, $qty);
        if ($type) {
            return $bundleQty;
        } else {
            return $bundleQty['error'];
        }

    }

    /**
     * Check piece case for warehouse
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param $qty
     * @return bool
     */
    public function checkWarehousePieceCase(\Magento\Catalog\Model\Product $product, $qty)
    {
        if ($product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
            $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
            if ($stockItem->getBackorders() == \Magento\CatalogInventory\Model\Stock::BACKORDERS_NO) {
                return $this->typeBundle->checkWarehousePieceCase($product, $stockItem, $qty);
            }
        }

        return true;
    }

}