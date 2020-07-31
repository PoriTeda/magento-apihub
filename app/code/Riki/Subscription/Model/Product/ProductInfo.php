<?php


namespace Riki\Subscription\Model\Product;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\StoreResolverInterface;
use Magento\Store\Model\App\Emulation;
use Riki\CreateProductAttributes\Model\Product\Available;
use Riki\Subscription\Api\ProductInfoInterface;
use Riki\Subscription\Helper\Profile\CampaignHelper;
use Riki\Subscription\Model\Landing\Page;
use Riki\Subscription\Model\Landing\PageFactory;
use Riki\SubscriptionCourse\Api\CourseRepositoryInterface;
use Riki\SubscriptionCourse\Helper\Data;
use Riki\SubscriptionPage\Model\PriceBox;
use Zend_Validate;
use Zend_Validate_Exception;

/**
 * Class ProductInfo
 * @package Riki\Subscription\Model\Product
 */
class ProductInfo implements ProductInfoInterface
{

    /**
     * @var CourseRepositoryInterface $courseRepo
     */
    public $courseRepo;

    /**
     * @var Data $courseHelper
     */
    public $courseHelper;

    /**
     * Core registry
     *
     * @var Registry $coreRegistry
     */
    protected $coreRegistry = null;

    /**
     * @var ProductRepositoryInterface $productRepository
     */
    protected $productRepository;

    /**
     * @var PriceBox $priceBox
     */
    protected $priceBox;

    /**
     * @var Emulation $appEmulation
     */
    protected $appEmulation;

    /**
     * @var ImageFactory $productImageHelper
     */
    protected $productImageHelper;

    /**
     * @var ExtensibleDataObjectConverter
     */
    private $extensibleDataObjectConverter;

    /**
     *  List of execluding product attributes
     * @var array $_excludingProductAttributes
     */
    private $_excludingProductAttributes =
        ['category_ids',
            'category_links',
            'product_links',
            'attribute_set_id',
            'price',
            'gift_wrapping',
            'visibility',
            'type_id',
            'weight',
            'product_links',
            'options',
            'media_gallery_entries',
            'image',
            'swatch_image',
            'small_image',
            'url_key',
            'material_type',
            'thumbnail',
            'backfront_visibility',
            'msrp_display_actual_price_type',
            'point_currency',
            'allow_seasonal_skip',
            'seasonal_skip_optional',
            'ph_code',
            'filter_part_applicable',
            'chirashi',
            'priority',
            'website_ids',
            'is_returnable'
        ];

    /**
     * @var CampaignHelper
     */
    protected $campaignHelper;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var array
     */
    protected $loadedCategories;

    protected $rootCategoryOfStore;

    /**
     * @var bool
     */
    protected $hasProduct = false;

    /**
     * @var StoreResolverInterface
     */
    protected $storeResolverInterface;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepositoryInterface;

    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepositoryInterface;

    /**
     * @var array
     */
    protected $productIds;

    /**
     * @var PageFactory
     */
    protected $landingPageModelFactory;

    /**
     * @var Page
     */
    protected $landingPageModel;

    /**
     * @var \Riki\Subscription\Api\Data\ProductInfoResultInterfaceFactory
     */
    protected $productInfoInterfaceFactory;

    /**
     * ProductInfo constructor.
     * @param CourseRepositoryInterface $courseRepo
     * @param Registry $coreRegistry
     * @param Data $courseHelper
     * @param PriceBox $priceBox
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param Emulation $appEmulation
     * @param ImageFactory $productImageHelper
     * @param ProductRepositoryInterface $productRepository
     * @param CampaignHelper $campaignHelper
     * @param CategoryFactory $categoryFactory
     * @param StoreResolverInterface $storeResolverInterface
     * @param StoreRepositoryInterface $storeRepositoryInterface
     * @param GroupRepositoryInterface $groupRepositoryInterface
     * @param PageFactory $landingPageModelFactory
     * @param Page $landingPageModel
     * @throws NoSuchEntityException
     */
    public function __construct(
        CourseRepositoryInterface $courseRepo,
        Registry $coreRegistry,
        Data $courseHelper,
        PriceBox $priceBox,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        Emulation $appEmulation,
        ImageFactory $productImageHelper,
        ProductRepositoryInterface $productRepository,
        CampaignHelper $campaignHelper,
        CategoryFactory $categoryFactory,
        StoreResolverInterface $storeResolverInterface,
        StoreRepositoryInterface $storeRepositoryInterface,
        GroupRepositoryInterface $groupRepositoryInterface,
        PageFactory $landingPageModelFactory,
        Page $landingPageModel,
        \Riki\Subscription\Api\Data\ProductInfoResultInterfaceFactory $productInfoInterfaceFactory
    ) {
        $this->courseRepo = $courseRepo;
        $this->coreRegistry = $coreRegistry;
        $this->courseHelper = $courseHelper;
        $this->priceBox = $priceBox;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->appEmulation = $appEmulation;
        $this->productImageHelper = $productImageHelper;
        $this->productRepository = $productRepository;
        $this->campaignHelper = $campaignHelper;
        $this->categoryFactory = $categoryFactory;
        $this->storeResolverInterface = $storeResolverInterface;
        $this->storeRepositoryInterface = $storeRepositoryInterface;
        $this->groupRepositoryInterface = $groupRepositoryInterface;
        $this->landingPageModelFactory = $landingPageModelFactory;
        $this->landingPageModel = $landingPageModel;
        $this->productInfoInterfaceFactory = $productInfoInterfaceFactory;

        // Not load product from default category
        $this->rootCategoryOfStore = null;
        $currentStoreId = $this->storeResolverInterface->getCurrentStoreId();
        $defaultGroupId = $this->storeRepositoryInterface->getById($currentStoreId)->getData('group_id');
        $rootCategoryCollection = $this->groupRepositoryInterface->get($defaultGroupId);
        if ($rootCategoryCollection) {
            $this->rootCategoryOfStore = $rootCategoryCollection->getData('root_category_id');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getProducts(int $id)
    {
        try {
            $this->isExistedLandingPage($id);
        } catch (NoSuchEntityException $e) { // do not handle
            throw $e;
        } catch (Zend_Validate_Exception $e) {
            throw $e;
        }
        $resultInterface = $this->productInfoInterfaceFactory->create();
        $this->getListOfProductIdsOfLandingPage($id);
        if (!Zend_Validate::is($this->productIds, 'NotEmpty')) {
            $responseResult = [];
        } else {
            $responseResult = array_map(function ($productId) {
                return $this->phaseProductData($productId);
            }, $this->productIds);
        }
        $resultInterface->setProductInformation($responseResult);

        return $resultInterface;
    }

    /**
     * @param int $landingPageId
     * @throws LocalizedException
     */
    public function getListOfProductIdsOfLandingPage($landingPageId)
    {
        /** @var  Page $campaignModel */
        $landingPageModel = $this->landingPageModelFactory->create()->load($landingPageId);
        $arrCategoryIds = $landingPageModel->getData('category_ids');

        if ($arrCategoryIds) {
            $this->loadedCategories = [];
            $this->productIds = [];
            $this->loadCategoriesByIds($arrCategoryIds);

            foreach ($this->loadedCategories as $loadedCategoryId => $loadedCategory) {
                if (in_array($loadedCategoryId, $arrCategoryIds)) {
                    $this->getProductsByCategory($loadedCategory);
                }
            }
        }
    }


    /**
     * @param array $categoriesId
     * @throws LocalizedException
     */
    public function loadCategoriesByIds(array $categoriesId)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->categoryFactory->create()->getCollection();
        $collection->addAttributeToFilter('entity_id', ['in' => $categoriesId])
            ->addAttributeToSelect('position')
            ->addAttributeToSelect('image')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('is_active')
            ->addAttributeToSort('position');

        foreach ($collection as $category) {
            $this->loadedCategories[$category->getId()] = $category;
        }
    }

    /**
     * @param Category $category
     * @return array
     */
    public function getProductsByCategory(Category $category)
    {
        $categoryId = $category->getId();

        if ($this->rootCategoryOfStore != null && $this->rootCategoryOfStore == $categoryId) {
            return [];
        }

        try {
            if ($category->getIsActive()) {
                /** @var Collection $productCollections */
                $productCollections = $category->getProductCollection()
                    ->addAttributeToSelect('*')
                    ->setOrder('position', 'ASC');

                /** @var Product $product */
                foreach ($productCollections as $product) {
                    if ($this->checkProductAvailableForShow($product) && $this->checkProductIsApplicable($product)) {
                        $this->productIds[$product->getId()] = $product->getId();
                    }
                }
            }
        } catch (Exception $e) {
            return [];
        }

        if (!empty($result) && !$this->hasProduct) {
            $this->hasProduct = true;
        }
    }

    /**
     *  This function to be used to check the landing page is exist or not
     *
     * @param int $landingPageId
     * @return bool
     * @throws NoSuchEntityException
     * @throws Zend_Validate_Exception
     */
    protected function isExistedLandingPage($landingPageId)
    {
        if (!Zend_Validate::is($landingPageId, 'NotEmpty')) {
            throw new NoSuchEntityException(__("Input landing page id is empty"));
        }
        try {
            /** @var  Page $campaignModel */
            $landingPageModel = $this->landingPageModelFactory->create()->load($landingPageId);
            if ($landingPageModel && !empty($landingPageModel->getLandingPageId())) {
                return true;
            }
        } catch (NoSuchEntityException $exception) { // handle not found exception
            throw $exception;
        } catch (Exception $exception) { // global exception case, do not handle
            throw $exception;
        }

        throw new NoSuchEntityException(
            __(
                'No landing page with %fieldName = %fieldValue',
                ['fieldName' => 'id', 'fieldValue' => $landingPageId]
            )
        );
    }

    /**
     * this function to be used privately by this model due to business constraints
     * DO NOT RE-USE this function in anywhere
     * @param int|string $identity
     * @param bool $loadBySku
     * @return array
     * @throws Exception
     */
    protected function phaseProductData($identity, $loadBySku = false)
    {
        $responseResult = [];

        /** @var Product $product */
        $product = null;
        try {
            // if the input param is an integer, that's a product ID
            if (!$loadBySku) {
                /** @var Product $product */
                $product = $this->productRepository->getById($identity, false, 1, true);
            } else {
                /** @var Product $product */
                $product = $this->productRepository->get($identity, false, 1, true);
            }
        } catch (NoSuchEntityException $exception) { // handle not found exception
            return $responseResult;
        } catch (Exception $exception) { // global exception case, do not handle
            throw $exception;
        }
        // check if product is available or not
        if ($this->checkProductAvailableForShow($product)) {
            $responseResult = $this->phaseAsArray($product);
            return $responseResult;
        } else {
            return $responseResult;
        }
    }

    /**
     * This function is used for checking if product is available for showing on the frontend or not
     * @param Product $product
     * @return boolean
     */
    protected function checkProductAvailableForShow($product)
    {
        /** @var Product */
        $storeIdsOfProduct = $product->getStoreIds();
        $currentStoreId = 1; // always EC site
        $productIsActiveInStore = false;
        if (in_array($currentStoreId, $storeIdsOfProduct)) {
            $productIsActiveInStore = true;
        }

        if ($product->getStatus() == 1
            && $product->getVisibility() != Visibility::VISIBILITY_NOT_VISIBLE
            && $productIsActiveInStore
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function phaseAsArray($product)
    {
        $fullPrice = $this->getProductPrice($product);

        $mediaGallery = array_map(function ($item) {
            if (isset($item["url"]) &&
                !empty($item["url"]) &&
                $item["disabled"] != 1 &&
                $item["media_type"] == "image") {
                return [
                    "label" => $item["label"],
                    "url" => $item["url"]
                ];
            } else {
                return null;
            }
        }, $product->getMediaGalleryImages()->toArray()["items"]);

        return array_diff_key(array_merge($this->extensibleDataObjectConverter->toNestedArray(
            $product,
            [],
            ProductInterface::class
        ), [
            "gallery" => $mediaGallery,
            "final_price" => (int)$fullPrice,
            "frontend_image_url" => $this->getImageUrl($product, 'product_thumbnail_image')
        ]), array_flip($this->_excludingProductAttributes));
    }

    /**
     * @param Product $product
     * @return string
     */
    public function getProductPrice($product)
    {
        if ($product->getTypeId() != 'bundle') {
            $finalPrice = $product->getPriceInfo()->getPrice(\Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE);
            $finalPrice = $finalPrice->getAmount()->getValue() ?: 0;
            return intval($finalPrice);
        } else {
            return intval($product->getPriceInfo()->getPrice('final_price')->getValue());
        }
    }

    /**
     * Helper function that provides full cache image url
     * @param Product
     * @param string|null $imageType
     * @return string
     */
    protected function getImageUrl($product, string $imageType = null)
    {
        $storeId = 1; // This API only support for EC store
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
        $imageUrl = $this->productImageHelper->create()->init($product, $imageType)->getUrl();
        $this->appEmulation->stopEnvironmentEmulation();
        return $imageUrl;
    }

    /**
     * @param Product $product
     * @return bool
     */
    protected function checkProductIsApplicable($product)
    {
        /** @var Product */
        $availableSubscription = $product->getCustomAttribute('available_subscription');
        if (isset($availableSubscription) && $availableSubscription->getValue() == Available::AVAILABLE) {
            return true;
        }
        return false;
    }
}
