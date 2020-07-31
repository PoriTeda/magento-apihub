<?php

namespace Riki\SubscriptionPage\Block;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\StoreResolverInterface as StoreResolverInterface;
use Magento\Store\Api\GroupRepositoryInterface as GroupRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface as StoreRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepositoryInterface;
use Magento\Swatches\Block\Product\Renderer\Configurable as RenderConfigurable;
use Magento\Catalog\Block\Product\View as ProductView;
use Magento\TestFramework\Event\Magento;
use Riki\Subscription\Model\Constant;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use \Riki\BackOrder\Helper\Data as BackOrderHelper;
use Magento\Cms\Model\Template\FilterProvider;
use \Riki\CreateProductAttributes\Model\Product\CaseDisplay;

class SubscriptionView extends \Magento\Framework\View\Element\Template implements \Magento\Framework\DataObject\IdentityInterface
{

    const CATEGORY_ID_FOR_PRODUCT_ADD_FROM_COURSE_TAB = 0;

    public static $hanpukaiCart = [];

    protected $categoryIdsToObjects = [];

    protected $coreRegistry;
    protected $categoryFactory;
    protected $subscriptionCourseModel;
    protected $imageBuilder;
    protected $formKey;
    protected $subscriptionCourseResourceModel;
    protected $dateTime;
    protected $stdTimezone;
    protected static $haveProductOutOfStockInHanpukaiCourse;
    protected $sortOrder;

    protected $loadedCategories = [];

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;
    protected $storeResolverInterface;
    protected $groupRepositoryInterface;
    protected $storeRepositoryInterface;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $productCollection;

    /**
     * @var \Magento\Swatches\Block\Product\Renderer\Configurable
     */
    protected $renderConfigurable;

    /**
     * @var \Magento\Catalog\Block\Product\View
     */
    protected $blockProductView;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $subCourseHelper;

    /**
     * @var \Riki\SubscriptionPage\Model\PriceBox
     */
    protected $priceBox;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $quote;

    /**
     * @var \Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface
     */
    protected $stockRegistryProviderInterface;

    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $stockStateRepository;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $localeFormat;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Wyomind\AdvancedInventory\Model\StockRepositery
     */
    protected $stockWyomindRepository;

    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subscriptionPageHelper;

    /**
     * Catalog data
     *
     * @var \Magento\Catalog\Helper\Data
     */
    protected $catalogData = null;

    /**
     * @var \Riki\Customer\Model\SsoConfig
     */
    protected $ssoConfig;

    /**
     * @var \Riki\Customer\Helper\SsoUrl
     */
    protected $ssoUrl;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Riki\ProductStockStatus\Helper\StockData
     */
    protected $stockDataHelper;

    /**
     * @var \Riki\BackOrder\Helper\Data
     */
    protected $backOrderHelper;

    protected $currentStoreId;

    protected $rootCategoryCollection;

    protected $defaultGroupId;

    protected $rootCategoryOfStore;

    /**
     * @var FilterProvider
     */
    protected $filterProvider;

    protected $cacheTags = [];

    protected $identities = [];

    protected $httpContext;
    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    private $imageFactory;

    protected $cart;

    /**
     * SubscriptionView constructor.
     *
     * @param BackOrderHelper $backOrderHelper
     * @param CategoryRepositoryInterface $categoryRepositoryInterface
     * @param \Riki\ProductStockStatus\Helper\StockData $stockDataHelper
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param SortOrder $sortOrder
     * @param \Riki\Customer\Helper\SsoUrl $ssoUrl
     * @param \Riki\Customer\Model\SsoConfig $ssoConfig
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelperData
     * @param \Wyomind\AdvancedInventory\Model\StockRepositery $wyomindStockRepositery
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param StockStateInterface $stockStateRepository
     * @param StockRegistryProviderInterface $stockRegistryProviderInterface
     * @param \Magento\Checkout\Model\Session $quote
     * @param ProductView $productView
     * @param RenderConfigurable $renderConfigurable
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param StoreRepositoryInterface $storeRepositoryInterface
     * @param StoreResolverInterface $storeResolverInterface
     * @param GroupRepositoryInterface $groupRepositoryInterface
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Riki\SubscriptionCourse\Model\Course $model
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     * @param \Riki\SubscriptionCourse\Model\ResourceModel\Course $courseResourceModel
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\SubscriptionCourse\Helper\Data $subCourseHelper
     * @param \Riki\SubscriptionPage\Model\PriceBox $priceBox
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection
     * @param FilterProvider $filterProvider
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Catalog\Helper\ImageFactory $imageHelperFactory
     */
    public function __construct(
        \Riki\BackOrder\Helper\Data $backOrderHelper,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepositoryInterface,
        \Riki\ProductStockStatus\Helper\StockData $stockDataHelper,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\Api\SortOrder $sortOrder,
        \Riki\Customer\Helper\SsoUrl $ssoUrl,
        \Riki\Customer\Model\SsoConfig $ssoConfig,
        \Magento\Catalog\Helper\Data $catalogData,
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelperData,
        \Wyomind\AdvancedInventory\Model\StockRepositery $wyomindStockRepositery,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        StockStateInterface $stockStateRepository,
        StockRegistryProviderInterface $stockRegistryProviderInterface,
        \Magento\Checkout\Model\Session $quote,
        ProductView $productView,
        RenderConfigurable $renderConfigurable,
        ProductRepositoryInterface $productRepositoryInterface,
        StoreRepositoryInterface $storeRepositoryInterface,
        StoreResolverInterface $storeResolverInterface,
        GroupRepositoryInterface $groupRepositoryInterface,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Riki\SubscriptionCourse\Model\Course $model,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $courseResourceModel,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\SubscriptionCourse\Helper\Data $subCourseHelper,
        \Riki\SubscriptionPage\Model\PriceBox $priceBox,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        FilterProvider $filterProvider,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Catalog\Helper\ImageFactory $imageHelperFactory,
        array $data = []
    )
    {
        $this->backOrderHelper = $backOrderHelper;
        $this->categoryRepository = $categoryRepositoryInterface;
        $this->stockDataHelper = $stockDataHelper;
        $this->functionCache = $functionCache;
        $this->sortOrder = $sortOrder;
        $this->ssoUrl = $ssoUrl;
        $this->ssoConfig = $ssoConfig;
        $this->catalogData = $catalogData;
        $this->subscriptionPageHelper = $subscriptionPageHelperData;
        $this->stockWyomindRepository = $wyomindStockRepositery;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepositoryInterface;
        $this->scopeConfig = $context->getScopeConfig();
        $this->localeFormat = $localeFormat;
        $this->stockStateRepository = $stockStateRepository;
        $this->stockRegistryProviderInterface = $stockRegistryProviderInterface;
        $this->quote = $quote->getQuote();
        $this->cart = $cart;
        $this->priceBox = $priceBox;
        $this->blockProductView = $productView;
        $this->renderConfigurable = $renderConfigurable;
        $this->productRepository = $productRepositoryInterface;
        $this->storeRepositoryInterface = $storeRepositoryInterface;
        $this->storeResolverInterface = $storeResolverInterface;
        $this->groupRepositoryInterface = $groupRepositoryInterface;
        self::$haveProductOutOfStockInHanpukaiCourse = false;
        $this->dateTime = $dateTime;
        $this->stdTimezone = $stdTimezone;
        $this->subscriptionCourseResourceModel = $courseResourceModel;
        $this->imageBuilder = $imageBuilder;
        $this->subscriptionCourseModel = $model;
        $this->coreRegistry = $coreRegistry;
        $this->categoryFactory = $categoryFactory;
        $this->formKey = $formKey;
        $this->stockRegistry = $stockRegistryInterface;
        $this->subCourseHelper = $subCourseHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productCollection = $productCollection;
        $this->filterProvider = $filterProvider;
        $this->httpContext = $httpContext;
        $this->imageFactory = $imageHelperFactory;

        $this->rootCategoryOfStore = null;
        // Not load product from default category
        $this->currentStoreId = $this->storeResolverInterface->getCurrentStoreId();
        $this->defaultGroupId = $this->storeRepositoryInterface->getById($this->currentStoreId)->getData('group_id');
        $this->rootCategoryCollection = $this->groupRepositoryInterface->get($this->defaultGroupId);
        if ($this->rootCategoryCollection) {
            $this->rootCategoryOfStore = $this->rootCategoryCollection->getData('root_category_id');
        }

        /* adding more cache definition here */

        /* hot-fix for ticket #REM-187  */
        /* get selected frequency */
        $selectedFrequency = $this->coreRegistry->registry('subscription-frequency-id');
        if (empty($selectedFrequency)) {
            $selectedFrequency = 0; // Refer to: \Riki\SubscriptionPage\Controller\View\frequency
        }

        $customerGroupID = 0;

        if ($this->checkCustomerIsLogged()) {
            try {
                $customerGroupID = $this->customerSession->getCustomerGroupId(); // Exception Flow Handler
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) { // Issue with Customer Load Flow
                $customerGroupID = 0; // Assumed Guest User
            } catch (\Magento\Framework\Exception\LocalizedException $exception) {
                $customerGroupID = 0; // Assumed Guest User
            } catch (\Exception $exception) { // Unknown Exception Case
                throw $exception;
            }
        }

        /*
         * membership of currently customer - use to generate cache key
         * value
         *      0: subscription page do not need validate customer membership
         *      other: subscription page accept for this membership list
         */
        $flagMembership = $this->coreRegistry->registry('currently_customer_membership');

        /* get website_id */
        $websiteId = 1; // EC site
        try {
            /** @var int $websiteId */
            $websiteId = $context->getStoreManager()->getStore()->getWebsiteId();
        } catch (\Exception $exception) { // Unknown Exception Case
            $websiteId = 1; // Assumed EC site
        } catch (\Error $error) { // Fatal Error Case: NULL Object Case
            $websiteId = 1; // Assumed EC site
        }
        /** reference to: Riki/SubscriptionPage/Controller/View/Index.php:219 */
        /** @var int $courseId */
        $courseId = $coreRegistry->registry('subscription-course-id');

        /* Cache Key: website_id + course_id + customer_group_id + frequency_id */
        $cacheKey = \Riki\SubscriptionPage\Block\SubscriptionView::class
            . '_' . $websiteId
            . '_' . $courseId
            . '_' . $customerGroupID
            . '_' . $flagMembership
            . '_' . $selectedFrequency;

        parent::__construct($context, $data);

        /* Do not Consumed Extra Memory Anymore */
        /* Do not Cache the Block if it Cannot be identified by the course-id */
        /* Prevent the FPC Fallback Case */
        if (!empty($courseId)) {
            $this->addData([
                'cache_lifetime' => 86400, // Cache TTL: 1 Day
                'cache_key' => $cacheKey,
                'cache_tags' => $this->getCacheTags()
            ]);
        }
    }

    public function _prepareLayout()
    {
        $pageTitle = $this->getSubscriptionCourseModel()->getData('meta_title');
        if ($pageTitle == null) {
            $pageTitle = $this->getSubscriptionCourseModel()->getData('course_name');
        }
        $this->pageConfig->getTitle()->set(__($pageTitle));
        return parent::_prepareLayout();
    }

    /**
     * @return array
     */
    public function getFrequency()
    {
        $result = [];
        $model = $this->getSubscriptionCourseModel();
        $selectedFrequency = $model->getData('frequency_ids');
        $selectedFrequencyArr = [];
        foreach ($selectedFrequency as $frequencySelectedItem) {
            $selectedFrequencyArr[] = $frequencySelectedItem;
        }

        $allFrequency = $model->getFrequencyValuesForForm();
        $countAllFre = count($allFrequency);
        for ($i = 0; $i < $countAllFre; $i++) {
            if (in_array($allFrequency[$i]['value'], $selectedFrequencyArr)) {
                $result[$allFrequency[$i]['value']] = $allFrequency[$i]['label'];
            }
        }

        return $result;
    }

    /**
     * Get product add by course
     *
     * @return array
     */
    public function getProductAddByCourse()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }
        $haveProductOutOfStockInHanpukaiCourse = false;
        $result = [];
        $model = $this->getSubscriptionCourseModel();
        $arrProductIds = $model->getData('product_ids');
        $isHanpukai = false;
        if ($this->getSubscriptionType() == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
            $arrProductIds = $this->getProductIdHanpukai();
            $isHanpukai = true;
        }

        if (empty($arrProductIds)) {
            return $result;
        }
        $productIds = array_map(function ($item) {
            return $item['product_id'];
        }, $arrProductIds);

        if (!empty($productIds)) {
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
            $productCollection = $this->productCollection->create();
            $productCollection->addFieldToFilter('entity_id', ['in' => $productIds]);
            $productCollection = $this->addStockInfoToProductCollection($productCollection);

            foreach ($productCollection as $product) {
                if ($this->checkProductAvailableForShow($haveProductOutOfStockInHanpukaiCourse, $product, $isHanpukai)) {
                    self::$haveProductOutOfStockInHanpukaiCourse = $haveProductOutOfStockInHanpukaiCourse;
                    $result[] = $product;
                }
            }
        }

        $this->functionCache->store($result);

        return $result;
    }

    /**
     * get Product hanpukai
     * @return array
     */
    public function getProductIdHanpukai()
    {
        $arrProduct = [];
        if ($this->getHanpukaiType() == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_FIXED) {
            $listProduct = $this->getProductHanpukaiFixed();
            foreach ($listProduct as $key => $value) {
                $arrProduct[]['product_id'] = $key;
            }
        }

        if ($this->getHanpukaiType() == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_SEQUENCE) {
            $arrProduct = $this->getProductHanpukaiSequency();
        }

        return $arrProduct;
    }

    public function getProductHanpukaiFixed()
    {
        $arrProduct = $this->subscriptionCourseResourceModel->getHanpukaiFixedProductsData(
            $this->getSubscriptionCourseModel()
        );
        return $arrProduct;
    }

    public function getHanpukaiProductIdAndQty($hanpukaiType)
    {
        $result = [];
        if ($hanpukaiType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_FIXED) {
            $arrProduct = $this->subscriptionCourseResourceModel->getHanpukaiFixedProductsData(
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
                    $result[$key] = $value['qty'];
                }
            }
        }

        return $result;
    }

    /**
     * GetHanpukaiProductIdAndQtyPieceCase
     *
     * @param $hanpukaiType
     * @return array
     */
    public function getHanpukaiProductIdAndQtyPieceCase($hanpukaiType)
    {
        if ($this->functionCache->has($hanpukaiType)) {
            return $this->functionCache->load($hanpukaiType);
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

        $this->functionCache->store($result, $hanpukaiType);

        return $result;
    }

    public function getProductHanpukaiSequency()
    {
        $result = [];
        // Login get first delivery number
        $arrProduct = $this->subscriptionCourseResourceModel->getHanpukaiSequenceProductsData(
            $this->getSubscriptionCourseModel()
        );
        $firstDelivery = $this->getFirstDeliveryNumber($arrProduct);
        foreach ($arrProduct as $key => $value) {
            if ($value['delivery_number'] == $firstDelivery) {
                $result[]['product_id'] = $key;
            }
        }
        return $result;
    }

    public function getFirstDeliveryNumber($arrProduct)
    {
        $deliveryNumberArr = [];
        foreach ($arrProduct as $key => $value) {
            if (isset($value['delivery_number'])) {
                $deliveryNumberArr[] = $value['delivery_number'];
            }
        }
        $deliveryNumberArr = $this->sort($deliveryNumberArr, count($deliveryNumberArr));
        if (count($deliveryNumberArr) > 0) {
            return $deliveryNumberArr[0];
        } else {
            return 0;
        }
    }

    public function sort($arr, $length)
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

    public function getCurrentDate()
    {
        $dateTimeNow = $this->stdTimezone->date();
        $coverDate = $dateTimeNow->format('Y/m');
        return $coverDate;
    }

    /**
     * @return string
     */
    public function getAddToCartFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Get product model
     *
     * @param $productId
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function loadProductById($productId)
    {
        if ($this->functionCache->has($productId)) {
            return $this->functionCache->load($productId);
        }

        $result = $this->productRepository->getById($productId);

        $this->functionCache->store($result, $productId);
        return $result;
    }

    /**
     * Get additional products
     *
     *
     * @return array
     */
    public function getListOfProductGroupByAdditionalCategory()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }

        $product = [];
        $model = $this->getSubscriptionCourseModel();

        $arrCategoryId = $model->getData('additional_category_ids');
        if ($arrCategoryId) {
            $this->loadedCategories = $this->loadCategoriesByIds($arrCategoryId);

            foreach ($this->loadedCategories as $loadedCategoryId => $loadedCategory) {
                if (in_array($loadedCategoryId, $arrCategoryId)) {
                    $aProductCategories = $this->getProductCollectionByCategory(
                        $this->loadedCategories[$loadedCategoryId]
                    );
                    if (!empty($aProductCategories)) {
                        $product[$loadedCategoryId] = $aProductCategories;
                    }
                }
            }
        }

        $result = ['product' => $product, 'category' => $this->loadedCategories];
        $this->functionCache->store($result);
        return $result;
    }

    /**
     * @return array
     */
    public function getListOfProductGroupByCategory()
    {
        return $this->getViewModel()->getListOfProductGroupByCategory();
    }

    /**
     * @param array $categoriesId
     * @return $this
     * @throws LocalizedException
     */
    public function loadCategoriesByIds(array $categoriesId)
    {
        return $this->getViewModel()->loadCategoriesByIds($categoriesId);
    }

    /**
     *
     */
    public function sortCategoryByPosition($arrCategory)
    {
        $arrResult = [];
        foreach ($arrCategory as $categoryId) {
            $categoryObj = $this->getCategoryById($categoryId);
            $arrResult[$categoryObj->getPosition()] = $categoryId;
        }
        ksort($arrResult);
        return array_values($arrResult);
    }

    public function getProductCollectionByCategory(\Magento\Catalog\Model\Category $category)
    {
        return $this->getViewModel()->getProductCollectionByCategory($category);
    }

    /**
     * @param $haveProductOutOfStockInHanpukaiCourse
     * @param $product
     * @param bool $isHanpukai
     * @return mixed
     */
    public function checkProductAvailableForShow(&$haveProductOutOfStockInHanpukaiCourse, $product, $isHanpukai = false)
    {
        return $this->getViewModel()->checkProductAvailableForShow($haveProductOutOfStockInHanpukaiCourse, $product, $isHanpukai);
    }

    /**
     * Get current course
     *
     * @return \Riki\SubscriptionCourse\Model\Course
     */
    public function getSubscriptionCourseModel()
    {
        return $this->getViewModel()->getSubscriptionCourseModel();
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
     * Get current select frequency id
     *
     * @return string|null
     */
    public function getSubscriptionFrequencyId()
    {
        return $this->coreRegistry->registry('subscription-frequency-id');
    }

    public function getProductType($product)
    {
        return $product->getTypeId();
    }

    public function getJsonConfig($configurableProduct)
    {
        return $this->renderConfigurable->setProduct($configurableProduct);
    }

    public function getJsonPriceConfig($configurableProduct)
    {
        $this->coreRegistry->register('product', $configurableProduct);
        $configurableBlock = $this->blockProductView;
        return $configurableBlock;
    }

    public function deleteRegister($key)
    {
        $this->coreRegistry->unregister($key);
    }

    /**
     * Get image
     *
     * @param $product
     * @param $imageId
     * @param array $attributes
     *
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getImage($product, $imageId, $attributes = [])
    {
        if ($this->functionCache->has([$product->getId(), $imageId])) {
            return $this->functionCache->load([$product->getId(), $imageId]);
        }

        $result = $this->imageBuilder->setProduct($product)
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create();
	      $result->setTemplate('Riki_SubscriptionPage::product/image_with_borders_lazy_load.phtml');
        $this->functionCache->store($result, [$product->getId(), $imageId]);
        return $result;
    }

    public function getAccessSubscriptionPage()
    {
        return $this->coreRegistry->registry('customer_access_subscription_page');
    }

    public function isCourseActive()
    {
        $courseModel = $this->getSubscriptionCourseModel();
        $isEnable = $courseModel->getData('is_enable');
        if ($isEnable == \Riki\SubscriptionCourse\Model\Course::STATUS_ENABLED) {
            return true;
        }
        return false;
    }

    public function isCourseAvailableInCurrentWebsite()
    {
        $currentWebsiteId = $this->getWebsiteId();
        $courseModel = $this->getSubscriptionCourseModel();
        $arrWebsiteIds = $courseModel->getData('website_ids');
        if (in_array($currentWebsiteId, $arrWebsiteIds)) {
            return true;
        }
        return false;
    }

    public function isCourseVisibility()
    {
        $courseModel = $this->getSubscriptionCourseModel();
        $visibility = $courseModel->getData('visibility');
        if ($visibility == \Riki\SubscriptionCourse\Model\Course::VISIBILITY_ALL
            || $visibility == \Riki\SubscriptionCourse\Model\Course::VISIBILITY_FRONTEND
        ) {
            return true;
        }
        return false;
    }

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function getWebsiteId()
    {
        return $this->_storeManager->getStore()->getWebsiteId();
    }

    public function getProductUrlByStore($product)
    {
        return $product->getUrlInStore(["_store" => $this->getStoreId()]);
    }

    public function getCourseDescription()
    {
        $model = $this->getSubscriptionCourseModel();
        $html = $this->filterProvider->getBlockFilter()->filter($model->getData('description'));
        return $html;
    }

    /**
     * GetAdditionalCourseDescription
     *
     * @return mixed
     */
    public function getAdditionalCourseDescription()
    {
        $model = $this->getSubscriptionCourseModel();
        $htmlContent = $model->getData('additional_category_description');
        return $this->catalogData->getPageTemplateProcessor()->filter($htmlContent);
    }

    /**
     * @return string
     */
    public function getMetaDescription()
    {
        $model = $this->getSubscriptionCourseModel();
        $htmlContent = $model->getData('meta_description');
        return $this->catalogData->getPageTemplateProcessor()->filter($htmlContent);
    }

    /**
     * @return bool
     */
    public function isNespresso()
    {
        $model = $this->getSubscriptionCourseModel();
        return $model->getData('design') == \Riki\SubscriptionCourse\Model\Course::DESIGN_BLACK;
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

    public function getHanpukaiType()
    {
        return $this->getSubscriptionCourseModel()->getData('hanpukai_type');
    }

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

        if ($product->getCaseDisplay() == CaseDisplay::CD_PIECE_ONLY) {
            return ['ea' => __('EA')];
        } elseif ($product->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY) {
            return ['cs' => __('CS') . '(' . $this->getUnitQty($product) . ' ' . __('EA') . ')'];
        } elseif ($product->getCaseDisplay() == CaseDisplay::CD_PIECE_AND_CASE) {
            return ['ea' => __('EA'), 'cs' => __('CS') . '(' . $this->getUnitQty($product) . ' ' . __('EA') . ')'];
        } else {
            return ['ea' => __('EA')];
        }
    }

    /**
     * @param $product
     * @return bool
     */
    public function isOnlyCase($product)
    {
        if ('bundle' == $product->getTypeId()) {
            return false;
        }

        if ($product->getCaseDisplay() == 2) {
            return true;
        }
        return false;
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
     * Gets minimal sales quantity for subscription page
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return int|null
     */
    public function getMinimalQty($product)
    {
        if ($this->functionCache->has($product->getId())) {
            return $this->functionCache->load($product->getId());
        }

        $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
        $minSaleQty = $stockItem->getMinSaleQty();
        $result = $minSaleQty > 0 ? $minSaleQty : null;

        $this->functionCache->store($result, $product->getId());
        return $result;
    }

    /**
     * Gets maximum sales quantity for subscription page
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return int|null
     */
    public function getMaximumQty($product)
    {
        if ($this->functionCache->has($product->getId())) {
            return $this->functionCache->load($product->getId());
        }

        $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
        $maxSaleQty = $stockItem->getMaxSaleQty();
        $result = $maxSaleQty > 0 ? $maxSaleQty : null;

        $this->functionCache->store($result, $product->getId());
        return $result;
    }

    public function getHaveProductOutOfStockInHanpukai()
    {
        return self::$haveProductOutOfStockInHanpukaiCourse;
    }

    /**
     * Get Machine list of current sub course
     *
     * @return array|bool|\Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function getMachineOption()
    {
        $courseId = $this->getSubscriptionCourseId();
        return $this->subCourseHelper->getMachineOption($courseId);
    }

    public function getMachineIds()
    {
        $ids = [];
        $products = $this->getMachineOption();
        if ($products) {
            foreach ($products as $product) {
                $ids[] = $product->getId();
            }
        }
        return $ids;
    }

    public function getMachineData()
    {
        $courseId = $this->getSubscriptionCourseId();
        $frequencyId = $this->getPreFrequency();
        $this->coreRegistry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);
        $this->coreRegistry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);

        $result = [];
        $products = $this->getMachineOption();
        if ($products) {
            foreach ($products as $product) {
                $product->setQty(1);
                $fullPrice = $this->priceBox->getFinalProductPrice($product);
                if ($fullPrice) {
                    $result[] = [
                        'id' => $product->getId(),
                        'text' => $product->getName() . ', ' . $this->stripTags($fullPrice[1]),
                        'price' => (int)$fullPrice[0]
                    ];
                }
            }
        }
        return $result;
    }

    public function getMachineDataJson()
    {
        $result = [];
        $result['machineIds'] = implode(',', $this->getMachineIds());
        $result['machineData'] = $this->getMachineData();
        $result['machineSelected'] = $this->getSelectedMachineId();
        return \Zend_Json::encode($result);
    }

    public function getPriceFormat()
    {
        $result['price_format'] = $this->localeFormat->getPriceFormat(null, 'JPY');
        return \Zend_Json::encode($result);
    }

    /**
     * Get Pre Frequency
     *
     * @return int
     */
    public function getPreFrequency()
    {
        if ($this->quote->getData(Constant::RIKI_FREQUENCY_ID)) {
            return $this->quote->getData(Constant::RIKI_FREQUENCY_ID);
        }
        return null;
    }

    /**
     * Get current selected machine in cart
     *
     * @return int $selected
     */
    public function getSelectedMachineId()
    {
        $selected = 0;
        $allItems = $this->cart->getItems();
        foreach ($allItems as $item) {
            if ($item->getData('is_riki_machine') == 1) {
                $selected = $item->getProductId();
            }
        }
        return $selected;
    }

    /**
     * Get config
     *
     * @param $path
     *
     * @return mixed
     */
    public function getConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue($path, $storeScope);
    }

    /**
     * Check customer is logged
     *
     * @return int (1 login, 0 not login)
     */
    public function checkCustomerIsLogged()
    {
        return $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    /**
     * Get Url login
     */
    public function getUrlLogin()
    {
        if (!$this->ssoConfig->isEnabled()) {
            $urlLogin = $this->_urlBuilder->getUrl('customer/account/login');
        } else {
            $urlLogin = $this->_urlBuilder->getUrl($this->ssoUrl->getLoginUrl($this->_urlBuilder->getCurrentUrl()));
        }

        return $urlLogin;
    }

    public function getCurrentSubPageViewUrl()
    {
        return $this->_urlBuilder->getCurrentUrl();
    }

    /**
     * Render a template
     *
     *
     * @param $template
     * @param $vars
     *
     * @return string
     */
    public function render($template, $vars = [])
    {
        foreach ($vars as $key => $var) {
            $this->assign($key, $var);
        }
        return $this->fetchView($this->getTemplateFile($template));
    }

    /**
     * Is course product?
     *
     * @return bool|mixed|null
     */
    public function hasProduct()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }

        $result = false;
        $categoryProducts = $this->getListOfProductGroupByCategory()['product'];
        $categoryProducts[self::CATEGORY_ID_FOR_PRODUCT_ADD_FROM_COURSE_TAB]
            = $this->getProductAddByCourse();

        foreach ([$categoryProducts] as $categories) {
            foreach ($categories as $products) {
                if (!empty($products)) {
                    $result = true;
                    break;
                }
            }
        }

        $this->functionCache->store($result);
        return $result;
    }

    /**
     * Is hanpukai subscription
     *
     * @return bool
     */
    public function isHanpukai()
    {
        return $this->getSubscriptionType() == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI;
    }

    /**
     * Is course enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->isCourseActive()
            && $this->isCourseVisibility()
            && $this->isCourseAvailableInCurrentWebsite()
            && $this->getAccessSubscriptionPage()
            && $this->isHanpukaiAvailableBetweenLaunchAndCloseTime();
    }

    /**
     * Get stock status data of product
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return mixed
     */
    public function getStockStatusMessage(\Magento\Catalog\Model\Product $product)
    {
        if ($this->functionCache->has($product->getId())) {
            return $this->functionCache->load($product->getId());
        }

        $result = $this->stockDataHelper->getStockStatusMessage($product);
        $this->functionCache->store($result, $product->getId());

        return $result;
    }

    /**
     * Get out of stock message of product
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return mixed|null|string
     */
    public function getOutStockMessageByProduct(\Magento\Catalog\Model\Product $product)
    {
        if ($this->functionCache->has($product->getId())) {
            return $this->functionCache->load($product->getId());
        }

        $result = $this->stockDataHelper->getOutStockMessageByProduct($product);
        $this->functionCache->store($result, $product->getId());

        return $result;
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
            return isset($data[$product->getId()]['unit_qty'])
                ? $data[$product->getId()]['unit_qty']
                : 1;
        }

        return $this->getUnitQty($product);
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
            return isset($data[$product->getId()]['qty'])
                ? $data[$product->getId()]['qty']
                : 1;
        }

        return 0;
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
                return __('EA');

            case 'cs':
                return __('CS');
        }

        return '';
    }


    /**
     * Get hanpukai cart data
     *
     * @return int|string
     */
    public function getHanpukaiCartData()
    {
        if (!$this->isHanpukai()) {
            return 0;
        }

        $cart = [
            'customer_id' => $this->customerSession->getCustomerId(),
            Constant::RIKI_COURSE_ID => $this->getSubscriptionCourseId(),
            Constant::RIKI_FREQUENCY_ID => current(array_keys($this->getFrequency())),
            'product_info' => []
        ];
        foreach (self::$hanpukaiCart as $productId => $cartData) {
            if (!$cartData['qty']) {
                continue;
            }
            $cart['product_info'][] = [
                'qty' => ($cartData['qty'] == $cartData['qty_case'])
                    ? $cartData['qty']
                    : $cartData['qty_case'],
                'product_id' => $cartData['product_id'],
                'product_type' => $cartData['product_type'],
                'bundle_option_qty' => [],
                Constant::RIKI_COURSE_ID => $cart[Constant::RIKI_COURSE_ID],
                'frequency' => $cart[Constant::RIKI_FREQUENCY_ID],
                'hanpukai_change_set_qty' => 1,

            ];
        }

        return \Zend_Json::encode($cart);
    }

    /**
     * @param $categoryId
     * @return mixed
     */
    public function getCategoryById($categoryId)
    {
        return $this->getViewModel()->getCategoryById($categoryId);
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
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function addStockInfoToProductCollection(\Magento\Catalog\Model\ResourceModel\Product\Collection $collection)
    {
        return $this->getViewModel()->addStockInfoToProductCollection($collection);
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        if (empty($this->identities)) {
            $this->generateCacheTags();
        }

        return $this->identities;
    }

    /**
     * get cache tags
     *
     * @return array
     */
    public function getCacheTags()
    {
        $tags = parent::getCacheTags();

        if (empty($this->cacheTags)) {
            $this->generateCacheTags();
        }

        $tags = array_merge($tags, $this->cacheTags);

        return $tags;
    }

    /**
     * generate cache tags
     */
    public function generateCacheTags()
    {
        $productList = $this->getListOfProductGroupByCategory()['product'];
        if ($productList) {
            foreach ($productList as $categoryId => $products) {
                $this->identities[] = \Magento\Catalog\Model\Product::CACHE_PRODUCT_CATEGORY_TAG . '_' . $categoryId;
                $this->cacheTags[] = \Magento\Catalog\Model\Product::CACHE_PRODUCT_CATEGORY_TAG . '_' . $categoryId;

                foreach ($products as $product) {
                    $this->identities = array_merge($this->identities, $product->getIdentities());
                    $this->cacheTags = array_merge($this->cacheTags, $product->getIdentities());
                }
            }
        }
    }

    public function getCurrentQuoteItems()
    {
        $items = $this->cart->getQuote()->getAllItems();
        $data = [];
        foreach ($items as $item) {
            $data[$item->getData("product_id")] = [
                "name" => $item->getData("name"),
                "price" => intval($item->getData("price_incl_tax")),
                "id" => intval($item->getData("product_id")),
                "qty" => intval($item->getData("qty")),
                "type" => $item->getData('is_riki_machine') === 1 ? 'machine' : 'main',
                "imageUrl" => $this->getImage($item->getProduct(), 'cart_page_product_thumbnail')->getImageUrl()
            ];
        }
        return json_encode($data);
    }
}
