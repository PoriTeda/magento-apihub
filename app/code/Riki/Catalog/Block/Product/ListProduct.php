<?php

namespace Riki\Catalog\Block\Product;

use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class ListProduct extends \Magento\Catalog\Block\Product\ListProduct
{
    protected $_joinedStock = false;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    protected $catalogRuleHelper;

    /**
     * ListProduct constructor.
     *
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param array $data
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Riki\CatalogRule\Helper\Data $catalogRuleHelper,
        array $data = []
    )
    {
        $this->functionCache = $functionCache;
        $this->registry = $context->getRegistry();
        $this->catalogRuleHelper = $catalogRuleHelper;
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper, $data);
    }

    /**
     * Retrieve loaded category collection
     *
     * @return AbstractCollection
     */
    protected function _getProductCollection()
    {
        $this->_productCollection = parent::_getProductCollection();

        if (!$this->_joinedStock) {
            $this->_productCollection->getSelect()
                                     ->joinLeft(
                                         ["ci_stock_item" => 'cataloginventory_stock_item'],
                                         'e.entity_id=ci_stock_item.product_id',
                                         [
                                             'managed_stock'   => new \Zend_Db_Expr("IF(use_config_manage_stock=1," . (int)$this->getStockConfigByPath('manage_stock') . ",ci_stock_item.manage_stock )"),
                                             'min_sale_qty'    => new \Zend_Db_Expr("IF(use_config_min_sale_qty=1," . (int)$this->getStockConfigByPath('min_sale_qty') . ",ci_stock_item.min_sale_qty)"),
                                             'max_sale_qty'    => new \Zend_Db_Expr("IF(use_config_max_sale_qty=1," . (int)$this->getStockConfigByPath('max_sale_qty') . ",ci_stock_item.max_sale_qty)"),
                                             'backorders'      => new \Zend_Db_Expr("IF(use_config_backorders=1," . (int)$this->getStockConfigByPath('backorders') . ",ci_stock_item.backorders)"),
                                             'is_in_stock_org' => 'ci_stock_item.is_in_stock'
                                         ],
                                         null,
                                         'left'
                                     )
                                     ->where('ci_stock_item.website_id IN(' . implode(',', [0, $this->_storeManager->getStore()->getWebsiteId()]) . ')');

            $this->_productCollection->getSelect()
                                     ->joinLeft(
                                         ["ai_stock_item" => 'advancedinventory_item'],
                                         'e.entity_id=ai_stock_item.product_id',
                                         [
                                             'is_multiple_stock' => 'ai_stock_item.multistock_enabled'
                                         ],
                                         null,
                                         'left'
                                     );


            $catalogEntityTypeId = $this->_catalogConfig->getEntityType(\Magento\Catalog\Model\Product::ENTITY)->getEntityTypeId();

            $this->_productCollection->getSelect()
                                     ->joinLeft(
                                         ["attribute_set_tbl" => 'eav_attribute_set'],
                                         'e.attribute_set_id=attribute_set_tbl.attribute_set_id',
                                         [
                                             'attribute_set_name' => 'attribute_set_tbl.attribute_set_name'
                                         ],
                                         null,
                                         'left'
                                     )->where('attribute_set_tbl.entity_type_id=' . $catalogEntityTypeId);

            $this->_joinedStock = true;
        }

        return $this->_productCollection;
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

        if (is_null($minSaleQty)) {
            $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
            $minSaleQty = $stockItem->getMinSaleQty();
        }

        return $minSaleQty > 0 ? $minSaleQty : null;
    }

    /**
     * @param $configName
     * @return mixed
     */
    public function getStockConfigByPath($configName)
    {
        return $this->_scopeConfig->getValue('cataloginventory/item_options/' . $configName);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function toHtml()
    {
        $this->prepareCatalogRulePrice();
        $this->prepareTierPrice();

        return parent::toHtml();
    }

    /**
     * Get product ids
     *
     * @return array
     */
    public function getProductIds()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }

        $result = $this->getLoadedProductCollection()->getAllIds();
        $this->functionCache->store($result);

        return $result;
    }

    /**
     * Prepare catalog rule price
     *
     * @return void
     */
    public function prepareCatalogRulePrice()
    {
        $productIds = $this->getProductIds();
        if (!$productIds) {
            return;
        }
        $this->catalogRuleHelper->registerPreLoadedProductIds($productIds);
    }

    /**
     * Prepare tier price
     *
     * @return void
     */
    public function prepareTierPrice()
    {
        $productIds = $this->getProductIds();

        $preloadCatalogRuleIds = $this->registry->registry(\Riki\Catalog\Model\ResourceModel\Product\Attribute\Backend\Tierprice::PRELOAD_PRODUCT_IDS_KEY);
        if (!$preloadCatalogRuleIds) {
            $preloadCatalogRuleIds = [];
        }

        if (is_array($productIds) && count($productIds) > 0) {
            array_push($preloadCatalogRuleIds, ...$productIds);
        }
        $this->registry->unregister(\Riki\Catalog\Model\ResourceModel\Product\Attribute\Backend\Tierprice::PRELOAD_PRODUCT_IDS_KEY);
        $this->registry->register(\Riki\Catalog\Model\ResourceModel\Product\Attribute\Backend\Tierprice::PRELOAD_PRODUCT_IDS_KEY, $productIds);
    }
}
