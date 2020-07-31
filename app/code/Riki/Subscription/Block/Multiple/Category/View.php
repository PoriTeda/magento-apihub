<?php

namespace Riki\Subscription\Block\Multiple\Category;

class View extends \Magento\Framework\View\Element\Template implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * @var \Riki\ProductStockStatus\Helper\StockData
     */
    protected $stockDataHelper;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Riki\Customer\Helper\SsoUrl
     */
    protected $ssoUrl;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $localeFormat;

    /**
     * @var \Magento\Catalog\Block\Product\View
     */
    protected $blockProductView;

    /**
     * @var \Magento\Swatches\Block\Product\Renderer\Configurable
     */
    protected $renderConfigurable;

    /**
     * @var \Magento\Store\Api\StoreResolverInterface
     */
    protected $storeResolverInterface;

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    protected $storeRepositoryInterface;

    /**
     * @var \Magento\Store\Api\GroupRepositoryInterface
     */
    protected $groupRepositoryInterface;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    protected $imageBuilder;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \Riki\Subscription\Model\Profile\Profile
     */
    protected $profileModel;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    /**
     * @var \Riki\Subscription\Helper\Profile\CampaignHelper
     */
    protected $campaignHelper;

    protected $rootCategoryOfStore;
    protected $loadedCategories = [];
    protected $cacheTags = [];
    protected $identities = [];
    protected $hasProduct = false;

    /**
     * View constructor.
     *
     * @param \Riki\ProductStockStatus\Helper\StockData $stockDataHelper
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Riki\Customer\Helper\SsoUrl $ssoUrl
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Catalog\Block\Product\View $blockProductView
     * @param \Magento\Swatches\Block\Product\Renderer\Configurable $renderConfigurable
     * @param \Magento\Store\Api\StoreResolverInterface $storeResolverInterface
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepositoryInterface
     * @param \Magento\Store\Api\GroupRepositoryInterface $groupRepositoryInterface
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface
     * @param \Riki\Subscription\Model\Profile\Profile $profileModel
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\ProductStockStatus\Helper\StockData $stockDataHelper,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Riki\Customer\Helper\SsoUrl $ssoUrl,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Catalog\Block\Product\View $blockProductView,
        \Magento\Swatches\Block\Product\Renderer\Configurable $renderConfigurable,
        \Magento\Store\Api\StoreResolverInterface $storeResolverInterface,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepositoryInterface,
        \Magento\Store\Api\GroupRepositoryInterface $groupRepositoryInterface,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface,
        \Riki\Subscription\Model\Profile\Profile $profileModel,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->stockDataHelper = $stockDataHelper;
        $this->functionCache = $functionCache;
        $this->ssoUrl = $ssoUrl;
        $this->customerSession = $customerSession;
        $this->localeFormat = $localeFormat;
        $this->blockProductView = $blockProductView;
        $this->renderConfigurable = $renderConfigurable;
        $this->storeResolverInterface = $storeResolverInterface;
        $this->storeRepositoryInterface = $storeRepositoryInterface;
        $this->groupRepositoryInterface = $groupRepositoryInterface;
        $this->imageBuilder = $imageBuilder;
        $this->registry = $registry;
        $this->categoryFactory = $categoryFactory;
        $this->formKey = $formKey;
        $this->stockRegistry = $stockRegistryInterface;
        $this->profileModel = $profileModel;
        $this->helperProfile = $helperProfile;
        $this->campaignHelper = $campaignHelper;

        // Not load product from default category
        $this->rootCategoryOfStore = null;
        $currentStoreId = $this->storeResolverInterface->getCurrentStoreId();
        $defaultGroupId = $this->storeRepositoryInterface->getById($currentStoreId)->getData('group_id');
        $rootCategoryCollection = $this->groupRepositoryInterface->get($defaultGroupId);
        if ($rootCategoryCollection) {
            $this->rootCategoryOfStore = $rootCategoryCollection->getData('root_category_id');
        }

        $customerGroupID = 0;

        if ($customerSession->isLoggedIn()) {
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

        /* Get website_id */
        $websiteId = 1; // Default EC site
        try {
            /** @var int $websiteId */
            $websiteId = $context->getStoreManager()->getStore()->getWebsiteId();
        } catch (\Exception $exception) { // Unknown Exception Case
            $websiteId = 1; // Assumed EC site
        } catch (\Error $error) { // Fatal Error Case: NULL Object Case
            $websiteId = 1; // Assumed EC site
        }

        /** reference to: Riki/Subscription/Controller/Multiple/Category/View.php:73 */
        $campaignId = $registry->registry('campaign_id');

        /* Cache Key: website_id + campaignId + customer_group_id */
        $cacheKey = \Riki\Subscription\Block\Multiple\Category\View::class
            . '_' . $websiteId
            . '_' . $campaignId
            . '_' . $customerGroupID;

        parent::__construct($context, $data);

        if (!empty($campaignId)) {
            $this->addData([
                'cache_lifetime' => 86400, // Cache TTL: 1 Day
                'cache_key'      => $cacheKey,
                'cache_tags'     => $this->getCacheTags()
            ]);
        }
    }

    /**
     * Set page title
     *
     * @return mixed
     */
    public function _prepareLayout()
    {
        $pageTitle = $this->getCampaignModel()->getData('name');
        if ($pageTitle == null) {
            $pageTitle = "Multiple category view page";
        }
        $this->pageConfig->getTitle()->set(__($pageTitle));
        return parent::_prepareLayout();
    }

    /**
     * Get form key
     *
     * @return string
     */
    public function getAddToCartFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Get current campaign model
     *
     * @return \Riki\Subscription\Model\Multiple\Category\Campaign
     */
    public function getCampaignModel()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }

        if ($campaignModel = $this->registry->registry('campaign')) {
            $result = $campaignModel;
        } else {
            $result = $this->campaignHelper->loadCampaign($this->getCampaignId());
        }

        $this->functionCache->store($result);

        return $result;
    }

    /**
     * Get current campaign id
     *
     * @return int|null
     */
    public function getCampaignId()
    {
        return $this->registry->registry('campaign_id');
    }

    /**
     * Get list of product by category of summer page
     *
     * @return array
     */
    public function getListOfProductByCategoryOfSummerPage()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }

        $products = [];
        /** @var  \Riki\Subscription\Model\Multiple\Category\Campaign $campaignModel */
        $campaignModel = $this->getCampaignModel();
        $arrCategoryIds = $campaignModel->getData('category_ids');

        if ($arrCategoryIds) {
            $this->loadCategoriesByIds($arrCategoryIds);

            foreach ($this->loadedCategories as $loadedCategoryId => $loadedCategory) {
                if (in_array($loadedCategoryId, $arrCategoryIds)) {
                    $products[$loadedCategoryId] = $this->getProductsByCategory($loadedCategory);
                }
            }
        }

        $this->functionCache->store($products);

        return $products;
    }

    /**
     * Load category by ids
     *
     * @param array $categoriesId
     * @return $this
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

        return $this;
    }

    /**
     * @param $categoryId
     * @return mixed
     */
    public function getCategoryById($categoryId)
    {
        if (!isset($this->loadedCategories[$categoryId])) {
            $this->loadedCategories[$categoryId] = $this->categoryFactory->create()->load($categoryId);
        }

        return $this->loadedCategories[$categoryId];
    }

    /**
     * Get products by category
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return array
     */
    public function getProductsByCategory(\Magento\Catalog\Model\Category $category)
    {
        $categoryId = $category->getId();

        $result = [];
        if ($this->rootCategoryOfStore != null && $this->rootCategoryOfStore == $categoryId) {
            return [];
        }

        try {
            if ($category->getIsActive()) {
                /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollections */
                $productCollections = $category->getProductCollection()
                    ->addAttributeToSelect('*')
                    ->addFieldToFilter('spot_allow_subscription', 1)
                    ->setOrder('position', 'ASC');

                /** @var \Magento\Catalog\Model\Product $product */
                foreach ($productCollections as $product) {
                    if ($this->checkProductAvailableForShow($product)) {
                        $result[] = $product;
                    }
                }
            }
        } catch (\Exception $e) {
            return [];
        }

        if (!empty($result) && !$this->hasProduct) {
            $this->hasProduct = true;
        }

        return $result;
    }

    /**
     * Check product available for show
     *
     * @param $product
     * @return bool
     */
    public function checkProductAvailableForShow($product)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $storeIdsOfProduct = $product->getStoreIds();
        $currentStoreId = $this->getStoreId();
        $productIsActiveInStore = false;
        if (in_array($currentStoreId, $storeIdsOfProduct)) {
            $productIsActiveInStore = true;
        }

        if ($product->getStatus() == 1
            && $product->getVisibility() != \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE
            && $productIsActiveInStore
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get product type
     *
     * @param $product
     * @return int
     */
    public function getProductType($product)
    {
        return $product->getTypeId();
    }

    /**
     * Get Json config
     *
     * @param $configurableProduct
     * @return \Magento\Swatches\Block\Product\Renderer\Configurable
     */
    public function getJsonConfig($configurableProduct)
    {
        return $this->renderConfigurable->setProduct($configurableProduct);
    }

    /**
     * Get Json price config
     *
     * @param $configurableProduct
     * @return \Magento\Catalog\Block\Product\View
     */
    public function getJsonPriceConfig($configurableProduct)
    {
        $this->registry->register('product', $configurableProduct);
        $configurableBlock = $this->blockProductView;
        return $configurableBlock;
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

    /**
     * Get store id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
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

    /**
     * Render a template
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
     * Get Unit Qty
     *
     * @param $product
     *
     * @return int
     */
    public function getUnitQty($product)
    {
        if ($product->getUnitQty()) {
            return $product->getUnitQty();
        } else {
            return 1;
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
        if ('bundle' == $product->getTypeId()) {
            return [];
        }

        if ($product->getCaseDisplay() == 1) {
            return ['ea' => __('EA')];
        } elseif ($product->getCaseDisplay() == 2) {
            return ['cs' => __('CS').'('.$this->getUnitQty($product).' '.__('EA').')'];
        } elseif ($product->getCaseDisplay() == 3) {
            return ['ea' => __('EA'),'cs' => __('CS').'('.$this->getUnitQty($product).' '.__('EA').')'];
        } else {
            return ['ea' => __('EA')];
        }
    }

    /**
     * Get data json to render on template
     *
     * @return string
     */
    public function getConfig()
    {
        $result = [];

        $result['campaign_id'] = $this->getCampaignId();
        $result['price_format'] = $this->localeFormat->getPriceFormat(null, 'JPY');
        $result['confirm_url'] = '';
        $result['base_url'] = $this->getBaseUrl();
        $result['login_url'] = $this->getLoginKssUrl();

        return json_encode($result);
    }

    /**
     * Checking customer login status
     *
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Return KSS login Link
     *
     * @return mixed|string
     */
    public function getLoginKssUrl()
    {
        return $this->ssoUrl->getLoginUrl($this->_urlBuilder->getCurrentUrl());
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        $productList = $this->getListOfProductByCategoryOfSummerPage();

        $this->identities[] = \Riki\Subscription\Model\Multiple\Category\Campaign::CACHE_TAG . '_' . $this->getCampaignId();

        if ($productList) {
            foreach ($productList as $categoryId => $products) {
                $this->identities[] = \Magento\Catalog\Model\Category::CACHE_TAG . '_' . $categoryId;

                /** @var \Magento\Catalog\Model\Product $product */
                foreach ($products as $product) {
                    $this->identities[] = \Magento\Catalog\Model\Product::CACHE_TAG . '_' . $product->getId();
                }
            }
        }

        return $this->identities;
    }

    /**
     * Get cache tags
     *
     * @return array
     */
    public function getCacheTags()
    {
        $tags = parent::getCacheTags();

        $tags = array_unique(array_merge($tags, [
            \Magento\Catalog\Model\Product::CACHE_TAG,
            \Magento\Catalog\Model\Category::CACHE_TAG,
            \Riki\Subscription\Model\Multiple\Category\Campaign::CACHE_TAG
        ]));

        return $tags;
    }

    /**
     * Is has product
     *
     * @return boolean
     */
    public function isHasProduct()
    {
        return $this->hasProduct;
    }

    public function isHanpukai(){
        return false;
    }
}
