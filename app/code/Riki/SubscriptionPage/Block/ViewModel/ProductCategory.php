<?php


namespace Riki\SubscriptionPage\Block\ViewModel;


use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepositoryInterface;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\GroupRepositoryInterface as GroupRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface as StoreRepositoryInterface;
use Magento\Store\Api\StoreResolverInterface as StoreResolverInterface;
use phpDocumentor\Reflection\Types\Self_;

/**
 * Class ProductCategory
 * @package Riki\SubscriptionPage\Block\ViewModel
 */
class ProductCategory extends DataObject implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var bool
     */
    private static $haveProductOutOfStockInHanpukaiCourse;
    /**
     * @var
     */
    private $_listProductsGroupByCategory;
    /**
     * @var
     */
    private static $_courseModel;
    /**
     * @var array
     */
    private static $_loadedCategories = [];
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $subscriptionCourseModelFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;
    /**
     * @var |null
     */
    private $rootCategoryOfStore = null;
    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepositoryInterface;
    /**
     * @var StoreResolverInterface
     */
    protected $storeResolverInterface;
    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepositoryInterface;
    /**
     * @var string
     */
    private $currentStoreId;
    /**
     * @var
     */
    private $defaultGroupId;
    /**
     * @var \Magento\Store\Api\Data\GroupInterface
     */
    private $rootCategoryCollection;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    private $stdTimezone;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var
     */
    private $loadedCategories;
    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    private $imageFactory;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    private $stockRegistry;
    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course
     */
    private $subscriptionCourseResourceModel;
    /**
     * @var \Riki\Subscription\Helper\Profile\CampaignHelper
     */
    private $campaignHelper;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;
    /**
     * @var \Magento\Catalog\Helper\Output
     */
    private $attributeOutputHelper;
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    private $cart;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var \Riki\ProductStockStatus\Helper\StockData
     */
    private $stockDataHelper;
    /**
     * @var FilterProvider
     */
    private $filterProvider;

    /**
     * @var \Amasty\Promo\Helper\Item
     */
    protected $_promoItemHelper;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * ProductCategory constructor.
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param StoreRepositoryInterface $storeRepositoryInterface
     * @param StoreResolverInterface $storeResolverInterface
     * @param GroupRepositoryInterface $groupRepositoryInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Helper\ImageFactory $imageHelperFactory
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        StoreRepositoryInterface $storeRepositoryInterface,
        StoreResolverInterface $storeResolverInterface,
        GroupRepositoryInterface $groupRepositoryInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Helper\ImageFactory $imageHelperFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $courseResourceModel,
        \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Escaper $escaper,
        \Magento\Catalog\Helper\Output $output,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Riki\ProductStockStatus\Helper\StockData $stockDataHelper,
        FilterProvider $filterProvider,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        array $data = []
    )
    {
        $this->subscriptionCourseModelFactory = $courseFactory;
        $this->coreRegistry = $coreRegistry;
        $this->categoryFactory = $categoryFactory;
        $this->storeRepositoryInterface = $storeRepositoryInterface;
        $this->storeResolverInterface = $storeResolverInterface;
        $this->groupRepositoryInterface = $groupRepositoryInterface;
        $this->storeManager = $storeManager;
        $this->stdTimezone = $stdTimezone;
        $this->scopeConfig = $scopeConfig;
        $this->imageFactory = $imageHelperFactory;
        $this->stockRegistry = $stockRegistry;
        $this->subscriptionCourseResourceModel = $courseResourceModel;
        $this->campaignHelper = $campaignHelper;
        $this->request = $request;
        $this->escaper = $escaper;
        $this->cart = $cart;
        $this->attributeOutputHelper = $output;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->stockDataHelper = $stockDataHelper;
        $this->filterProvider = $filterProvider;
        $this->_promoItemHelper = $promoItemHelper;
        $this->functionCache = $functionCache;

        parent::__construct($data);

        // Not load product from default category
        $this->currentStoreId = $this->storeResolverInterface->getCurrentStoreId();
        $this->defaultGroupId = $this->storeRepositoryInterface->getById($this->currentStoreId)->getData('group_id');
        $this->rootCategoryCollection = $this->groupRepositoryInterface->get($this->defaultGroupId);
        if ($this->rootCategoryCollection) {
            $this->rootCategoryOfStore = $this->rootCategoryCollection->getData('root_category_id');
        }
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws \Exception
     */
    public function getListOfProductGroupByCategory()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }
        $product = [];
        $loadedCategories = [];

        if ($courseModel = $this->getSubscriptionCourseModel()) {
            $arrCategoryIds = $courseModel->getData('category_ids');
            if ($arrCategoryIds) {
                $loadedCategories = $this->loadCategoriesByIds($arrCategoryIds);
            }
        } else if ($campaignModel = $this->getCampaignModel()) {
            $arrCategoryIds = $campaignModel->getData('category_ids');
            if ($arrCategoryIds) {
                $loadedCategories = $this->loadCategoriesByIds($arrCategoryIds);
            }
        } else if ($catId = $this->request->getParam("id")) {
            $collection = $this->categoryFactory->create()->getCollection();
            $collection->addAttributeToSelect('entity_id')
                ->addAttributeToFilter('url_key', $catId);
            if ($collection->getSize()) {
                $arrCategoryIds = [$collection->getFirstItem()->getData('entity_id')];
                if ($arrCategoryIds) {
                    $this->setIsMultipleCategory(true);
                    $loadedCategories = $this->loadCategoriesByIds($arrCategoryIds, true);
                }
            } else {
                return [];
            }
        } else {
            throw new \Exception(__('Could not find category id'));
        }

        foreach ($loadedCategories as $loadedCategoryId => $loadedCategory) {
            $product[$loadedCategoryId] = $this->getProductCollectionByCategory($loadedCategory);
        }

        $result = ['product' => $product, 'category' => $loadedCategories];
        $this->functionCache->store($result);
        return $result;
    }

    protected static $_campaignModel = null;

    /**
     * Get current campaign model
     *
     * @return \Riki\Subscription\Model\Multiple\Category\Campaign
     */
    public function getCampaignModel()
    {
        if (self::$_campaignModel === null) {
            if (null === $this->getCampaignId()) {
                self::$_campaignModel = $this->coreRegistry->registry('campaign') ?: null;
            } else {
                self::$_campaignModel = $this->coreRegistry->registry('campaign') ?: $this->campaignHelper->loadCampaign($this->getCampaignId());
            }
        }
        return self::$_campaignModel;
    }

    /**
     * Get current campaign id
     *
     * @return int|null
     */
    public function getCampaignId()
    {
        return $this->coreRegistry->registry('campaign_id');
    }

    /**
     * @return \Riki\SubscriptionCourse\Model\Course
     */
    public function getSubscriptionCourseModel()
    {
        if (self::$_courseModel === null) {
            if (null === $this->getSubscriptionCourseId()) {
                self::$_courseModel = $this->coreRegistry->registry('subscription-course') ?: null;
            } else {
                self::$_courseModel = $this->coreRegistry->registry('subscription-course') ?: $this->subscriptionCourseModelFactory
                    ->create()
                    ->load($this->getSubscriptionCourseId());
            }
        }

        return self::$_courseModel;
    }

    /**
     * Get current course id
     *
     * @return string|null
     */
    public function getSubscriptionCourseId()
    {
        return $this->coreRegistry->registry('subscription-course-id');
    }

    /**
     * @param array $categoriesId
     * @return $this
     * @throws LocalizedException
     */
    public function loadCategoriesByIds(array $categoriesId, $includeChild = false)
    {
        if (isset(self::$_loadedCategories[join("", $categoriesId)])) {
            return self::$_loadedCategories[join("", $categoriesId)];
        }

        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->categoryFactory->create()->getCollection();
        $collection->addAttributeToFilter('entity_id', ['in' => $categoriesId])
            ->addAttributeToSelect('position')
            ->addAttributeToSelect('image')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('description')
            ->addAttributeToSelect('is_active')
            ->addAttributeToSort('position');
        if (!$includeChild) {
            foreach ($collection as $category) {
                self::$_loadedCategories[join("", $categoriesId)][$category->getId()] = $category;
            }
        } else {
            foreach ($collection as $category) {
                if ($category->getChildren()) {
                    $arrayChildren = explode(',', $category->getChildren());

                    /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $childrenCollection */
                    $childrenCollection = $this->categoryFactory->create()->getCollection();
                    $childrenCollection->addAttributeToFilter('entity_id', ['in' => $arrayChildren])
                        ->addAttributeToSelect('position')
                        ->addAttributeToSelect('multiple_products')
                        ->addAttributeToSelect('name')
                        ->addAttributeToSelect('description')
                        ->addAttributeToSelect('is_active')
                        ->addAttributeToSort('position');

                    foreach ($childrenCollection as $childCat) {
                        self::$_loadedCategories[join("", $categoriesId)][$childCat->getId()] = $childCat;
                    }

                } else {
                    self::$_loadedCategories[join("", $categoriesId)][$category->getId()] = $category;
                }
            }
        }

        return self::$_loadedCategories[join("", $categoriesId)];
    }

    /**
     * @var array
     */
    private static $_productByCat = [];

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @return array
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductCollectionByCategory(\Magento\Catalog\Model\Category $category)
    {
        $categoryId = $category->getId();

        $haveProductOutOfStockInHanpukaiCourse = false;

        if (!isset(self::$_productByCat[$categoryId])) {

            $result = [];
            if ($this->rootCategoryOfStore !== null && $this->rootCategoryOfStore === $categoryId) {
                return [];
            }

            if ($category->getIsActive()) {
                /** @var \Magento\Catalog\Model\Product $product */
                foreach ($this->_processProductCollection($category->getProductCollection()) as $product) {
                    if ($this->checkProductAvailableForShow($haveProductOutOfStockInHanpukaiCourse, $product)) {
                        $result[] = $product;
                    }
                }
            }

            self::$_productByCat[$categoryId] = $result;
        }

        return self::$_productByCat[$categoryId];
    }

    /**
     * @param $ids
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getProductCollectionByIds($ids)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addFieldToFilter("entity_id", ['in' => $ids]);
        $result = [];
        foreach ($this->_processProductCollection($productCollection) as $product) {
            $result[$product->getId()] = $product;
        }

        return $result;
    }

    protected function _processProductCollection(\Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection)
    {
        $productCollection = $productCollection
            ->addAttributeToSelect([
                'desc_explanation',
                'desc_ingredient',
                'desc_allergen_mandatory',
                'desc_explanation_recom',
                'desc_content',
                'desc_nutrition',
                'desc_supplemental_info',
                'delivery_type',
                'gift_wrapping',
                'special_price'
            ])
            ->setOrder('position', 'ASC');

        if ($this->getIsMultipleCategory() !== true) {
            $productCollection->addFieldToFilter('available_subscription', 1);
        }

        $productCollection = $this->addStockInfoToProductCollection($productCollection);

        return $productCollection;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws LocalizedException
     */
    public function addStockInfoToProductCollection(\Magento\Catalog\Model\ResourceModel\Product\Collection $collection)
    {
        $collection->getSelect()
            ->joinLeft(["ci_stock_item" => 'cataloginventory_stock_item'],
                'e.entity_id=ci_stock_item.product_id',
                [
                    'managed_stock' => new \Zend_Db_Expr("
                        IF(
                            use_config_manage_stock=1,
                            " . (int)$this->getStockConfigByPath('manage_stock') . ",
                            ci_stock_item.manage_stock 
                        )
                     "),
                    'min_sale_qty' => new \Zend_Db_Expr("
                        IF(
                            use_config_min_sale_qty=1,
                            " . (int)$this->getStockConfigByPath('min_sale_qty') . ",
                            ci_stock_item.min_sale_qty
                        )
                    "),
                    'max_sale_qty' => new \Zend_Db_Expr("
                        IF(
                            use_config_max_sale_qty=1,
                            " . (int)$this->getStockConfigByPath('max_sale_qty') . ",
                            ci_stock_item.max_sale_qty
                         )
                    "),
                    'is_in_stock_org' => 'ci_stock_item.is_in_stock',
                    'quantity_in_stock' => 'ci_stock_item.qty'
                ],
                null,
                'left'
            )
            ->where(
                'ci_stock_item.website_id IN(' . implode(',', [0, $this->getWebsiteId()]) . ')'
            );

        $joinedAttributes = [
            'spot_allow_subscription',
            'available_subscription',
            'allow_spot_order',
            'status',
            'visibility',
            'name',
            'stock_display_type',
            'price',
            'case_display',
            'unit_qty',
            'tax_class_id',
            'image',
            'small_image',
            'thumbnail',
            'swatch_image',
            'price_type'

        ];

        foreach ($joinedAttributes as $joinedAttribute) {
            if ((strpos((string)$collection->getSelectSql(), 'AS `at_' . $joinedAttribute . '`') === false)) {
                $collection->joinAttribute(
                    $joinedAttribute,
                    'catalog_product/' . $joinedAttribute,
                    'entity_id',
                    null,
                    'left',
                    $this->getStoreId() ? $this->getStoreId() : null
                );
            }
        }

        return $collection;
    }

    /**
     * @param $haveProductOutOfStockInHanpukaiCourse
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $isHanpukai
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function checkProductAvailableForShow(&$haveProductOutOfStockInHanpukaiCourse, \Magento\Catalog\Model\Product $product, $isHanpukai = false)
    {
        $haveProductOutOfStockInHanpukaiCourse = false;
        $storeIdsOfProduct = $product->getStoreIds();
        $currentStoreId = $this->getStoreId();
        $productIsActiveInStore = false;
        if (in_array($currentStoreId, $storeIdsOfProduct)) {
            $productIsActiveInStore = true;
        }
        if ($isHanpukai == true && !$product->getIsSalable()) {
            $haveProductOutOfStockInHanpukaiCourse = true;
        }

        return $product->getStatus() == 1
        && $product->getVisibility() != \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE
        && $productIsActiveInStore ? true : false;
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getWebsiteId()
    {
        return $this->storeManager->getStore()->getWebsiteId();
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @return bool
     */
    public function isHanpukaiAvailableBetweenLaunchAndCloseTime()
    {
        $model = $this->getSubscriptionCourseModel();
        $launchTime = $model->getData('launch_date');
        $closeDate = $model->getData('close_date');
        $currentDate = $this->stdTimezone->date();
        $convertCurrentDate = $currentDate->format('Y-m-d H:s:i');

        if ($launchTime) {
            $intLaunchDate = strtotime($launchTime);
        } else {
            $intLaunchDate = 0;
        }

        if ($closeDate) {
            $intCloseDate = strtotime($closeDate);
        } else {
            $intCloseDate = 0;
        }

        if ($convertCurrentDate) {
            $intCurrentDate = strtotime($convertCurrentDate);
        } else {
            $intCurrentDate = 0;
        }

        if ($intLaunchDate <= $intCurrentDate) {
            if ($intCloseDate == 0) {
                return true;
            } else {
                if ($intCurrentDate <= $intCloseDate) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * @param $configName
     * @return mixed
     */
    public function getStockConfigByPath($configName)
    {
        return $this->scopeConfig->getValue('cataloginventory/item_options/' . $configName);
    }

    /**
     * @param $categoryId
     * @return \Magento\Catalog\Model\Category
     */
    public function getCategoryById($categoryId)
    {
        if (!isset($this->loadedCategories[$categoryId])) {
            $this->loadedCategories[$categoryId] = $this->categoryFactory->create()->load($categoryId);
        }

        return $this->loadedCategories[$categoryId];
    }

    /**
     * @var array
     */
    private static $_productDetailJS = [];

    /**
     * @param $product
     * @param $categoryId
     * @return false|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductDetailJsData(\Magento\Catalog\Model\Product $product, $categoryId, $isHanpukai, $stockMessage = null)
    {
        if (!isset(self::$_productDetailJS[$product->getData("entity_id")])) {
            $desAttrs = ["desc_explanation", "desc_ingredient", "desc_allergen_mandatory", "desc_explanation_recom", "desc_content", "desc_nutrition"];
            $des = [];
            foreach ($desAttrs as $desAttr) {
                $des[$desAttr] = $this->attributeOutputHelper->productAttribute($product, $product->getData($desAttr), $desAttr) ?: null;
            }

            $data = [
                $product->getData("entity_id") => [
                    "id" => $product->getId(),
                    "catId" => $categoryId,
                    "name" => $product->getData("name"),
                    "isSalable" => $product->getIsSalable(),
                    "stockText" => (!empty($stockMessage)) ? $stockMessage : $this->getStockStatusMessage($product),
                    "price" => $product->getPriceInfo()->getPrice('final_price')->getValue(),
                    "thumbnail_image_url" => $this->imageFactory->create()->init($product, 'bundled_product_customization_page')->getUrl(),
                    "descriptions" => $des,
                    "delivery_type" => $product->getData('delivery_type'),
                    "gift_wrapping" => $product->getData('gift_wrapping')
                ],
                "isHanpukai" => $isHanpukai
            ];

            self::$_productDetailJS[$product->getData("entity_id")] = $this->escaper->escapeHtmlAttr(json_encode(
                [
                    "Riki_SubscriptionPage/js/view/product-detail" => $data
                ]
            ));
        }

        return self::$_productDetailJS[$product->getData("entity_id")];
    }

    private static $_minimalQty = [];

    /**
     * Gets minimal sales quantity for subscription page
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return int|null
     */
    public function getMinimalQty($product)
    {
        if (isset(self::$_minimalQty[$product->getId()])) {
            return self::$_minimalQty[$product->getId()];
        }

        $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
        $minSaleQty = $stockItem->getMinSaleQty();
        $result = $minSaleQty > 0 ? $minSaleQty : null;

        self::$_minimalQty[$product->getId()] = $result;

        return $result;
    }

    private static $_maximumQty = [];

    /**
     * Gets maximum sales quantity for subscription page
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return int|null
     */
    public function getMaximumQty($product)
    {
        if (isset(self::$_maximumQty[$product->getId()])) {
            return self::$_maximumQty[$product->getId()];
        }

        $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
        $maxSaleQty = $stockItem->getMaxSaleQty();
        $result = $maxSaleQty > 0 ? $maxSaleQty : null;

        self::$_maximumQty[$product->getId()] = $result;
        return $result;
    }

    /**
     * Get Unit Display
     *
     * @param $product
     *
     * @return array
     */
    public function getUnitDisplay($product)
    {
        if (\Magento\Catalog\Model\Product\Type::TYPE_BUNDLE == $product->getTypeId()) {
            return ['ea' => __('EA')];
        }

        if ($product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_PIECE_ONLY) {
            return ['ea' => __('EA')];
        }

        if ($product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
            return ['cs' => __('CS') . '(' . $this->getUnitQty($product) . ' ' . __('EA') . ')'];
        }

        if ($product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_PIECE_AND_CASE) {
            return ['ea' => __('EA'), 'cs' => __('CS') . '(' . $this->getUnitQty($product) . ' ' . __('EA') . ')'];
        }

        return ['ea' => __('EA')];
    }

    /**
     * Get Unit Qty
     *
     * @param $product
     *
     * @return int
     */
    public function getUnitQty($product)
    {
        if ($product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
            return $product->getUnitQty() ?: 1;
        }
        return 1;
    }

    /**
     * Get unit_qty
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return int
     */
    public function getUnitCaseQty(\Magento\Catalog\Model\Product $product)
    {
        if ($this->isHanpukai()) {
            $data = $this->getHanpukaiProductIdAndQtyPieceCase($this->getHanpukaiType());
            return $data[$product->getId()]['unit_qty'] ?? 1;
        }

        return $this->getUnitQty($product);
    }

    /**
     * Get subscription type
     *
     * @return mixed
     */
    public function getSubscriptionType()
    {
        return $this->getSubscriptionCourseModel()->getData('subscription_type');
    }

    /**
     * Is hanpukai subscription
     *
     * @return bool
     */
    public function isHanpukai()
    {
        return (bool)$this->getSubscriptionCourseModel() && $this->getSubscriptionType() == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI;
    }

    /**
     * Get qty if product is case
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return float|int
     */
    public function getQtyCase(\Magento\Catalog\Model\Product $product)
    {
        return $this->getQty($product) / $this->getUnitCaseQty($product);
    }

    /**
     * Get default qty
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return int
     */
    public function getQty(\Magento\Catalog\Model\Product $product)
    {
        if ($this->isHanpukai()) {
            $data = $this->getHanpukaiProductIdAndQtyPieceCase($this->getHanpukaiType());
            return $data[$product->getId()]['qty'] ?? 1;
        }

        return 0;
    }

    public function getHanpukaiType()
    {
        return $this->getSubscriptionCourseModel()->getData('hanpukai_type');
    }

    /**
     * GetHanpukaiProductIdAndQtyPieceCase
     *
     * @param $hanpukaiType
     * @return array
     */
    protected function getHanpukaiProductIdAndQtyPieceCase($hanpukaiType)
    {
        $cacheKey = "hanpukaiProductIdAndQtyPieceCaseCache" . $hanpukaiType;

        if ($this->hasData($cacheKey)) {
            return $this->getData($cacheKey);
        }

        $result = [];
        if ($hanpukaiType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_FIXED) {
            $arrProduct = $this->subscriptionCourseResourceModel->getHanpukaiFixedProductsDataPieCase(
                $this->getSubscriptionCourseModel()
            );
            foreach ($arrProduct as $key => $value) {
                $result[$key] = $value;
            }
        }

        if ($hanpukaiType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_SEQUENCE) {
            $arrProduct = $this->subscriptionCourseResourceModel->getHanpukaiSequenceProductsData(
                $this->getSubscriptionCourseModel()
            );
            $firstDelivery = $this->getFirstDeliveryNumber($arrProduct);
            foreach ($arrProduct as $key => $value) {
                if ($value['delivery_number'] == $firstDelivery) {
                    $result[$key] = $value;
                }
            }
        }

        $this->setData($cacheKey, $result);

        return $result;
    }

    protected function getFirstDeliveryNumber($arrProduct)
    {
        $deliveryNumberArr = [];
        foreach ($arrProduct as $key => $value) {
            if (isset($value['delivery_number'])) {
                $deliveryNumberArr[] = $value['delivery_number'];
            }
        }
        $deliveryNumberArr = $this->sort($deliveryNumberArr, count($deliveryNumberArr));

        return count($deliveryNumberArr) > 0 ? $deliveryNumberArr[0] : 0;
    }

    protected function sort($arr, $length)
    {
        for ($i = 0; $i < $length - 1; $i++) {
            for ($j = $i + 1; $j < $length; $j++) {
                if ((int)$arr[$j] < (int)$arr[$i]) {
                    $tmp = $arr[$i];
                    $arr[$i] = $arr[$j];
                    $arr[$j] = $tmp;
                }
            }
        }
        return $arr;
    }

    /**
     * Get case_display (ea, cs)
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return mixed|string
     */
    public function getCaseDisplay(\Magento\Catalog\Model\Product $product)
    {
        if ($this->isHanpukai()) {
            $data = $this->getHanpukaiProductIdAndQtyPieceCase($this->getHanpukaiType());
            return isset($data[$product->getId()]['unit_case'])
                ? strtolower($data[$product->getId()]['unit_case'])
                : 'ea';
        }

        return key($this->getUnitDisplay($product));
    }

    /**
     * Get label of case display
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getCaseDisplayLabel(\Magento\Catalog\Model\Product $product)
    {
        switch ($this->getCaseDisplay($product)) {
            case 'ea':
                return ('EA');

            case 'cs':
                return ('CS');
        }

        return '';
    }

    public function getCurrentQuoteItems($getByJson = true)
    {
        $data = [];
        foreach ($this->cart->getQuote()->getAllVisibleItems() as $item) {
            $product = $item->getProduct();

            // check free_item
            if ($this->_promoItemHelper->isPromoItem($item)
                || (($item->getData('is_riki_machine') && $item->getData('price') == 0))
                || $item->getPrizeId()
            ) {
                $item['free_item'] = true;
            } else {
                $item['free_item'] = false;
            }
            $itemData =  $item->getData();
            unset($itemData['gift_wrapping']);
            if ($product) {
                /** @var \Magento\Quote\Model\Quote\Item $item */
                $data[$item->getItemId()] = array_merge([
                    "name" => $item->getData("name"),
                    "price" => (int)$item->getData("price_incl_tax"),
                    "id" => (int)($item->getData("product_id")),
                    "currentQty" => (int)($item->getData("qty")),
                    "type" => $item->getData('is_riki_machine') === 1 ? 'machine' : 'main',
                    "imageUrl" => $this->imageFactory->create()->init($product, 'bundled_product_customization_page')->getUrl(),
                    "unit_qty" => $this->getUnitCaseQty($product),
                    "case_display" => $this->getCaseDisplay($product),
                    "qty_case" => $this->getQtyCase($product),
                    "maxQty" => $this->getMaximumQty($product) > 99 ? 99 : $this->getMaximumQty($product),
                    "minQty" => $this->getMinimalQty($product),
                    "delivery_type" => $product->getData('delivery_type'),
                    "gift_wrapping" => $product->getData('gift_wrapping')
                ], $itemData);
            }
        }
        return $getByJson ? json_encode($data) : $data;
    }

    protected static $stockText = [];

    public function getStockStatusMessage(\Magento\Catalog\Model\Product $product)
    {

        if (!isset(self::$stockText[$product->getId()])) {
            $stockMessageArr = $this->stockDataHelper->getStockStatusMessage($product);
            $textMessage = array_key_exists('message', $stockMessageArr) ? $stockMessageArr['message'] : '';
            $isInStock = $product->getIsSalable();
            if ($isInStock == false) {
                $textMessage = $this->stockDataHelper->getOutStockMessageByProduct($product);
            }

            self::$stockText[$product->getId()] = __('Stock:') . ' ' . $textMessage;
        }

        return self::$stockText[$product->getId()];
    }

    protected static $_quoteProductCollection;

    /**
     * @return array|bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuoteProductCollection($isSpotOrder = false, $isJsonData = false)
    {
        if (self::$_quoteProductCollection === null) {
            self::$_quoteProductCollection = [];
            $this->setIsMultipleCategory($isSpotOrder);
            $ids = [];
            foreach ($this->cart->getQuote()->getAllVisibleItems() as $item) {
                $ids[$item->getProduct()->getId()] = array_merge($item->getData(), ['currentQty' => $item->getQty()]);
            }
            if (count($ids) > 0) {
                $products = $this->getProductCollectionByIds(array_keys($ids));
                if (count($products) > 0) {
                    self::$_quoteProductCollection = array_map(static function ($product) use ($ids) {
                        if (isset($ids[$product->getId()])) {
                            $product->addData($ids[$product->getId()]);
                        }

                        return $product;
                    }, $products);
                }
            }
        }

        return $isJsonData ? json_encode(array_map(static function ($product) {
            return $product->getData();
        }, self::$_quoteProductCollection)) : self::$_quoteProductCollection;
    }

    public function filterText($text)
    {
        return $this->filterProvider->getBlockFilter()->filter($text);
    }
}