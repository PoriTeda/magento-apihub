<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Block\Checkout\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * TargetRule Checkout Cart Cross-Sell Products Block
 *
 * @api
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Crosssell extends \Magento\TargetRule\Block\Product\AbstractProduct
{
    /**
     * Array of product objects in cart
     *
     * @var array
     */
    protected $_products;

    /**
     * Object of just added product to cart
     *
     * @var \Magento\Catalog\Model\Product
     */
    protected $_lastAddedProduct;

    /**
     * Whether get products by last added
     *
     * @var bool
     */
    protected $_byLastAddedProduct = false;

    /**
     * @var \Magento\TargetRule\Model\Index
     */
    protected $_index;

    /**
     * @var \Magento\TargetRule\Model\IndexFactory
     */
    protected $_indexFactory;

    /**
     * @var \Magento\Catalog\Model\Product\LinkFactory
     */
    protected $_productLinkFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_visibility;

    /**
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    protected $stockHelper;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ProductTypes\ConfigInterface
     */
    protected $productTypeConfig;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\TargetRule\Model\ResourceModel\Index $index
     * @param \Magento\TargetRule\Helper\Data $targetRuleData
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\Product\Visibility $visibility
     * @param \Magento\CatalogInventory\Helper\Stock $stockHelper
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Catalog\Model\Product\LinkFactory $productLinkFactory
     * @param \Magento\TargetRule\Model\IndexFactory $indexFactory
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param ProductRepositoryInterface $productRepository
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\TargetRule\Model\ResourceModel\Index $index,
        \Magento\TargetRule\Helper\Data $targetRuleData,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        \Magento\Checkout\Model\Session $session,
        \Magento\Catalog\Model\Product\LinkFactory $productLinkFactory,
        \Magento\TargetRule\Model\IndexFactory $indexFactory,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->productTypeConfig = $productTypeConfig;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_visibility = $visibility;
        $this->stockHelper = $stockHelper;
        $this->_checkoutSession = $session;
        $this->_productLinkFactory = $productLinkFactory;
        $this->_indexFactory = $indexFactory;
        parent::__construct(
            $context,
            $index,
            $targetRuleData,
            $data
        );
        $this->_isScopePrivate = true;
        $this->productRepository = $productRepository;
    }

    /**
     * Slice items to limit
     *
     * @return $this
     * @since 100.1.0
     */
    protected function _sliceItems()
    {
        if ($this->_items !== null) {
            $this->_items = array_slice($this->_items, 0, $this->getPositionLimit(), true);
        }
        return $this;
    }

    /**
     * Retrieve Catalog Product List Type identifier
     *
     * @return int
     */
    public function getProductListType()
    {
        return \Magento\TargetRule\Model\Rule::CROSS_SELLS;
    }

    /**
     * Retrieve just added to cart product id
     *
     * @return int|false
     */
    public function getLastAddedProductId()
    {
        return $this->_checkoutSession->getLastAddedProductId(true);
    }

    /**
     * Retrieve just added to cart product object
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getLastAddedProduct()
    {
        if ($this->_lastAddedProduct === null) {
            $productId = $this->getLastAddedProductId();
            if ($productId) {
                try {
                    $this->_lastAddedProduct = $this->productRepository->getById($productId);
                } catch (NoSuchEntityException $e) {
                    $this->_lastAddedProduct = false;
                }
            } else {
                $this->_lastAddedProduct = false;
            }
        }
        return $this->_lastAddedProduct;
    }

    /**
     * Retrieve quote instance
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    /**
     * Retrieve Array of Product instances in Cart
     *
     * @return array
     */
    protected function _getCartProducts()
    {
        if ($this->_products === null) {
            $this->_products = [];
            foreach ($this->getQuote()->getAllItems() as $quoteItem) {
                /* @var $quoteItem \Magento\Quote\Model\Quote\Item */
                $product = $quoteItem->getProduct();
                $this->_products[$product->getEntityId()] = $product;
            }
        }

        return $this->_products;
    }

    /**
     * Retrieve Array of product ids in Cart
     *
     * @return array
     */
    protected function _getCartProductIds()
    {
        $products = $this->_getCartProducts();
        return array_keys($products);
    }

    /**
     * Retrieve Array of product ids which have special relation with products in Cart
     * For example simple product as part of product type that represents product set
     *
     * @return array
     */
    protected function _getCartProductIdsRel()
    {
        $productIds = [];
        foreach ($this->getQuote()->getAllItems() as $quoteItem) {
            $productTypeOpt = $quoteItem->getOptionByCode('product_type');
            if ($productTypeOpt instanceof \Magento\Quote\Model\Quote\Item\Option &&
                $this->productTypeConfig->isProductSet(
                    $productTypeOpt->getValue()
                ) && $productTypeOpt->getProductId()
            ) {
                $productIds[] = $productTypeOpt->getProductId();
            }
        }

        return $productIds;
    }

    /**
     * Retrieve Target Rule Index instance
     *
     * @return \Magento\TargetRule\Model\Index
     */
    protected function _getTargetRuleIndex()
    {
        if ($this->_index === null) {
            $this->_index = $this->_indexFactory->create();
        }
        return $this->_index;
    }

    /**
     * Retrieve Maximum Number Of Product
     *
     * @return int
     */
    public function getPositionLimit()
    {
        return $this->_targetRuleData->getMaximumNumberOfProduct(\Magento\TargetRule\Model\Rule::CROSS_SELLS);
    }

    /**
     * Retrieve Position Behavior
     *
     * @return int
     */
    public function getPositionBehavior()
    {
        return $this->_targetRuleData->getShowProducts(\Magento\TargetRule\Model\Rule::CROSS_SELLS);
    }

    /**
     * Get link collection for cross-sell
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection|null
     */
    protected function _getTargetLinkCollection()
    {
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection */
        $collection = $this->_productLinkFactory->create()
            ->useCrossSellLinks()
            ->getProductCollection()
            ->setStoreId($this->_storeManager->getStore()->getId())
            ->setPageSize($this->getPositionLimit())
            ->setGroupBy();
        $this->_addProductAttributesAndPrices($collection);
        $collection->setVisibility($this->_visibility->getVisibleInSiteIds());

        return $collection;
    }

    /**
     * Retrieve array of cross-sell products for just added product to cart
     *
     * @return array
     */
    protected function _getProductsByLastAddedProduct()
    {
        $product = $this->getLastAddedProduct();
        if (!$product) {
            return [];
        }
        $this->_byLastAddedProduct = true;
        $items = parent::getItemCollection();
        $this->_byLastAddedProduct = false;
        $this->_items = null;
        return $items;
    }

    /**
     * Retrieve Product Ids from Cross-sell rules based products index by product object
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int $count
     * @param array $excludeProductIds
     * @return array
     */
    protected function _getProductIdsFromIndexByProduct($product, $count, $excludeProductIds = [])
    {
        return $this->_getTargetRuleIndex()->setType(
            \Magento\TargetRule\Model\Rule::CROSS_SELLS
        )->setLimit(
            $count
        )->setProduct(
            $product
        )->setExcludeProductIds(
            $excludeProductIds
        )->getProductIds();
    }

    /**
     * Retrieve Product Collection by Product Ids
     *
     * @param array $productIds
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _getProductCollectionByIds($productIds)
    {
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $collection = $this->_productCollectionFactory->create();
        $collection->addFieldToFilter('entity_id', ['in' => $productIds]);
        $this->_addProductAttributesAndPrices($collection);

        $collection->setVisibility($this->_visibility->getVisibleInCatalogIds());

        return $collection;
    }

    /**
     * Retrieve Product Ids from Cross-sell rules based products index by products in shopping cart
     *
     * @param int $limit
     * @param array $excludeProductIds
     * @return array
     */
    protected function _getProductIdsFromIndexForCartProducts($limit, $excludeProductIds = [])
    {
        $resultIds = [];

        foreach ($this->_getCartProducts() as $product) {
            if ($product->getEntityId() == $this->getLastAddedProductId()) {
                continue;
            }

            $productIds = $this->_getProductIdsFromIndexByProduct(
                $product,
                $this->getPositionLimit(),
                $excludeProductIds
            );
            $resultIds = array_merge($resultIds, $productIds);
        }

        $resultIds = array_unique($resultIds);
        shuffle($resultIds);

        return array_slice($resultIds, 0, $limit);
    }

    /**
     * Get exclude product ids
     *
     * @return array
     */
    protected function _getExcludeProductIds()
    {
        $excludeProductIds = $this->_getCartProductIds();
        if ($this->_items !== null) {
            $excludeProductIds = array_merge(array_keys($this->_items), $excludeProductIds);
        }
        return $excludeProductIds;
    }

    /**
     * Get target rule based products for cross-sell
     *
     * @return array
     */
    protected function _getTargetRuleProducts()
    {
        $excludeProductIds = $this->_getExcludeProductIds();
        $limit = $this->getPositionLimit();
        $productIds = $this->_byLastAddedProduct ? $this->_getProductIdsFromIndexByProduct(
            $this->getLastAddedProduct(),
            $limit,
            $excludeProductIds
        ) : $this->_getProductIdsFromIndexForCartProducts(
            $limit,
            $excludeProductIds
        );

        $items = [];
        if ($productIds) {
            $collection = $this->_getProductCollectionByIds($productIds);
            foreach ($collection as $product) {
                $items[$product->getEntityId()] = $product;
            }
        }

        return $items;
    }

    /**
     * Get linked products
     *
     * @return array
     */
    protected function _getLinkProducts()
    {
        $items = [];
        $collection = $this->getLinkCollection();
        if ($collection) {
            if ($this->_byLastAddedProduct) {
                $collection->addProductFilter($this->getLastAddedProduct()->getEntityId());
            } else {
                $filterProductIds = array_merge($this->_getCartProductIds(), $this->_getCartProductIdsRel());
                $collection->addProductFilter($filterProductIds);
            }
            $collection->addExcludeProductFilter($this->_getExcludeProductIds());

            foreach ($collection as $product) {
                $items[$product->getEntityId()] = $product;
            }
        }
        return $items;
    }

    /**
     * Retrieve array of cross-sell products
     *
     * @return array
     */
    public function getItemCollection()
    {
        if ($this->_items === null) {
            // if has just added product to cart - load cross-sell products for it
            $productsByLastAdded = $this->_getProductsByLastAddedProduct();
            $limit = $this->getPositionLimit();
            if (count($productsByLastAdded) < $limit) {
                // reset collection
                $this->_linkCollection = null;
                parent::getItemCollection();
                // products by last added are preferable
                $this->_items = $productsByLastAdded + $this->_items;
                $this->_sliceItems();
            } else {
                $this->_items = $productsByLastAdded;
            }
            $this->_items = $this->_orderProductItems($this->_items);
            $this->_sliceItems();
        }
        return $this->_items;
    }

    /**
     * Check is has items
     *
     * @return bool
     */
    public function hasItems()
    {
        return $this->getItemsCount() > 0;
    }

    /**
     * Retrieve count of product in collection
     *
     * @return int
     */
    public function getItemsCount()
    {
        return count($this->getItemCollection());
    }
}
