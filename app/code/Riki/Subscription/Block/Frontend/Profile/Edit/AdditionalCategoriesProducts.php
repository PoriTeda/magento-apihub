<?php

namespace Riki\Subscription\Block\Frontend\Profile\Edit;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Category;
use Magento\Framework\View\Element\Template;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;
use Riki\Subscription\Model\Profile\Profile;

class AdditionalCategoriesProducts extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
     */
    protected $courseRepository;

    /**
     * @var \Riki\CatalogRule\Helper\Data
     */
    protected $catalogRuleHelper;

    /**
     * @var Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    protected $imageBuilder;

    /**
     * @var \Riki\ProductStockStatus\Helper\StockData
     */
    protected $stockStatusHelper;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileHelper;

    /**
     * @var array
     */
    protected $loadedCategoriesProducts;

    /**
     * @var array
     */
    protected $productIds = [];

    /**
     * @var array
     */
    protected $productCategoryIds = [];

    /**
     * AdditionalCategoriesProducts constructor.
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
     * @param \Riki\CatalogRule\Helper\Data $catalogRuleHelper
     * @param Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     * @param \Riki\ProductStockStatus\Helper\StockData $stockStatusHelper
     * @param \Riki\Subscription\Helper\Profile\Data $profileHelper
     * @param \Magento\Framework\Registry $registry
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository,
        \Riki\CatalogRule\Helper\Data $catalogRuleHelper,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Riki\ProductStockStatus\Helper\StockData $stockStatusHelper,
        \Riki\Subscription\Helper\Profile\Data $profileHelper,
        \Magento\Framework\Registry $registry,
        Template\Context $context,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->courseRepository = $courseRepository;
        $this->catalogRuleHelper = $catalogRuleHelper;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->imageBuilder = $imageBuilder;
        $this->stockStatusHelper = $stockStatusHelper;
        $this->profileHelper = $profileHelper;

        parent::__construct($context, $data);
    }

    /**
     * @return Profile
     */
    public function getProfile()
    {
        return $this->registry->registry('current_profile');
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getListOfProductGroupByAdditionalCategory()
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/subscription_additional_categories.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $logger->info("========== START ==========");
        $resultLog = [];
        if (!$this->loadedCategoriesProducts) {
            $result = [];
            /** @var \Riki\Subscription\Model\Profile\Profile $profile */
            $profile = $this->getProfile();

            if ($profile) {
                try {
                    $course = $this->courseRepository->get($profile->getCourseId());
                    $logger->info('Course ID: ' . $course->getId());
                    $logger->info('Course Code: ' . $course->getCode());
                    $logger->info('Profile ID: ' . $profile->getProfileId());
                } catch (\Exception $e) {
                    return $result;
                }

                $categoriesId = $course->getAdditionalCategoryIds();
                $logger->info("Categories Id: " . \Zend_Json::encode($categoriesId));
                if ($categoriesId) {
                    $categories = $this->loadCategoriesByIds($categoriesId);
                    $logger->info("Categories size : " . $categories->getSize());
                    /** @var \Magento\Catalog\Model\Category $category */
                    foreach ($categories as $category) {
                        $products = $this->getProductCollectionByCategory($category);
                        if (!empty($products)) {
                            $this->catalogRuleHelper->registerPreLoadedProductIds(array_keys($products));
                            $result[$category->getId()] = [
                                'category' => $category,
                                'products' => $products
                            ];

                            foreach ($products as $product) {
                                $this->productIds[] = $product->getId();
                                $this->productCategoryIds[] = $product->getId() . '_' . $category->getId();
                            }

                            $resultLog[$category->getId()] = [
                                'products' => $this->productIds
                            ];
                        }
                    }
                }
            }

            $this->loadedCategoriesProducts = $result;
        }

        $logger->info("Result: " . \Zend_Json::encode($resultLog));
        $logger->info("========== END ==========");
        return $this->loadedCategoriesProducts;
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @return \Magento\Framework\DataObject[]
     */
    public function getProductCollectionByCategory(\Magento\Catalog\Model\Category $category)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect([
            'name',
            'price',
            'image',
            'swatch_image',
            'small_image',
            'price_type',
            'has_options',
            'required_option',
            'delivery_type',
            'gift_wrapping',
            'special_price',
            'future_price',
            'future_gps_price',
            'gps_price',
            'gps_price_ec',
            'future_gps_price_ec',
            'tax_class_id',
            'unit_qty',
            'case_display',
            'credit_card_only',
            'is_free_shipping',
            'future_price_from',
            'future_gps_price_from',
            'allow_seasonal_skip',
            'allow_skip_from',
            'allow_skip_to',
            'allow_spot_order',
            'stock_display_type',
            'seasonal_skip_optional'
        ])
            ->addStoreFilter()
            ->addCategoryFilter($category)
            ->addAttributeToFilter('status', 1)
            ->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE])
            ->addAttributeToFilter('available_subscription', 1);

        $minSaleQty = $this->_scopeConfig->getValue('cataloginventory/item_options/min_sale_qty');
        $maxSaleQty = $this->_scopeConfig->getValue('cataloginventory/item_options/max_sale_qty');

        $collection->getSelect()->joinLeft(
            ['s' => 'cataloginventory_stock_item'],
            'e.entity_id=s.product_id',
            [
                'min_sale_qty' => new \Zend_Db_Expr(
                    'IF(use_config_min_sale_qty=1, ' . $minSaleQty . ',min_sale_qty)'
                ),
                'max_sale_qty' => new \Zend_Db_Expr(
                    'IF(use_config_max_sale_qty=1, ' . $maxSaleQty . ',max_sale_qty)'
                )
            ],
            null,
            'left'
        );

        $items = $collection->getItems();

        array_walk($items, function (\Magento\Catalog\Model\Product &$product) {
            $stockStatusData = $this->stockStatusHelper->getStockStatusMessage($product);

            $product->setData('stock_status_class', isset($stockStatusData['class']) ? $stockStatusData['class'] : null);
            $product->setData(
                'stock_status_message',
                isset($stockStatusData['message']) ? $stockStatusData['message'] : null
            );

            $isSaleable = $product->isSaleable();

            $product->setIsSaleable($isSaleable);

            if (!$isSaleable) {
                $product->setData(
                    'stock_status_message',
                    $this->stockStatusHelper->getOutStockMessageByProduct($product)
                );
            }

            $product->setData('unit_qty_type_options', $this->profileHelper->getUnitQtyTypeOptions(
                $this->getProfile()->getProfileId(),
                $product
            ));

            $product->setData(
                'unit_qty',
                $product->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY ? max(1, intval($product->getUnitQty())) : 1
            );
        });

        return $items;
    }

    /**
     * @param array $categoriesId
     * @return Category\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadCategoriesByIds(array $categoriesId)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->categoryCollectionFactory->create();
        $collection->setStoreId($this->_storeManager->getStore())
            ->addAttributeToFilter('entity_id', ['in'    => $categoriesId])
            ->addAttributeToFilter('level', ['gt'   => 1])
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToSelect('name')
            ->addAttributeToSort('position');

        return $collection;
    }

    /**
     * Retrieve product image
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $imageId
     * @param array $attributes
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getImage($product, $imageId, $attributes = [])
    {
        return $this->imageBuilder->setProduct($product)
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create();
    }

    /**
     * @return array
     */
    public function getProductIds()
    {
        return $this->productIds;
    }

    /**
     * @return array
     */
    public function getProductCategoryIds()
    {
        return $this->productCategoryIds;
    }

    /**
     * @return array
     */
    public function getCacheKeyInfo()
    {
        return [
            'SUBSCRIPTION_PROFILE_ADDITIONAL_CATEGORIES_PRODUCTS',
            $this->_storeManager->getStore()->getId(),
            $this->getProfile()->getId()
        ];
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCacheTags()
    {
        $tags = parent::getCacheTags();

        $tags[] = \Magento\Framework\App\Cache\Type\Block::TYPE_IDENTIFIER;

        foreach ($this->getListOfProductGroupByAdditionalCategory() as $categoryId => $categoryData) {
            $tags[] = Product::CACHE_PRODUCT_CATEGORY_TAG . '_' . $categoryId;

            /** @var \Magento\Catalog\Model\Product $product */
            foreach ($categoryData['products'] as $product) {
                $tags = array_merge($tags, $product->getIdentities());
            }
        }

        return $tags;
    }

    /**
     * @return bool|int
     */
    public function getCacheLifetime()
    {
        return false;
    }

    /**
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toHtml()
    {
        $data = $this->getListOfProductGroupByAdditionalCategory();

        if (empty($data)) {
            return null;
        }
        return parent::toHtml();
    }
}
