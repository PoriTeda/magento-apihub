<?php

namespace Riki\Catalog\Block\Product\ProductList;

class Upsell extends \Magento\Catalog\Block\Product\ProductList\Upsell
{
    /**
     * @return $this
     */
    protected function _prepareData()
    {
        $product = $this->_coreRegistry->registry('product');
        /* @var $product \Magento\Catalog\Model\Product */
        $this->_itemCollection = $product->getUpSellProductCollection()->setPositionOrder()->addStoreFilter();
        if ($this->moduleManager->isEnabled('Magento_Checkout')) {
            $this->_addProductAttributesAndPrices($this->_itemCollection);
        }
        $this->_itemCollection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());

        // add stock info to collection
        $this->_itemCollection->getSelect()
            ->joinLeft(
                ["ci_stock_item" => 'cataloginventory_stock_item'],
                'e.entity_id=ci_stock_item.product_id',
                [
                    'managed_stock' => new \Zend_Db_Expr("IF(use_config_manage_stock=1," . (int)$this->getStockConfigByPath('manage_stock') . ",ci_stock_item.manage_stock )"),
                    'min_sale_qty' => new \Zend_Db_Expr("IF(use_config_min_sale_qty=1," . (int)$this->getStockConfigByPath('min_sale_qty') . ",ci_stock_item.min_sale_qty)"),
                    'max_sale_qty' => new \Zend_Db_Expr("IF(use_config_max_sale_qty=1," . (int)$this->getStockConfigByPath('max_sale_qty') . ",ci_stock_item.max_sale_qty)"),
                    'is_in_stock_org'   =>  'ci_stock_item.is_in_stock'
                ],
                null,
                'left'
            )
            ->where('ci_stock_item.website_id IN(' . implode(',', [0, $this->_storeManager->getStore()->getWebsiteId()]) . ')');


        $this->_itemCollection->getSelect()
            ->joinLeft(
                ["ai_stock_item" => 'advancedinventory_item'],
                'e.entity_id=ai_stock_item.product_id',
                [
                    'is_multiple_stock'   =>  'ai_stock_item.multistock_enabled'
                ],
                null,
                'left'
            );
        //

        $this->_itemCollection->load();
        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }

    /**
     * Gets minimal sales quantity
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return int|null
     */
    public function getMinimalQty($product)
    {
        $minSaleQty = $product->getMinSaleQty();

        if(is_null($minSaleQty)){
            $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
            $minSaleQty = $stockItem->getMinSaleQty();
        }

        return $minSaleQty > 0 ? $minSaleQty : null;
    }

    /**
     * @param $configName
     * @return mixed
     */
    public function getStockConfigByPath($configName){
        return $this->_scopeConfig->getValue('cataloginventory/item_options/' . $configName);
    }
}
