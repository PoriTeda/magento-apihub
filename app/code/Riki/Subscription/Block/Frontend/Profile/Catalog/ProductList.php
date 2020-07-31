<?php
/**
 * @author 2018 Nestle Japan
 */

namespace Riki\Subscription\Block\Frontend\Profile\Catalog;

class ProductList
    extends \Magento\Framework\View\Element\Template
{

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_registry = null;
    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course $_subCourseResourceModel
     */
    protected $_subCourseResourceModel;
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface
     */
    protected $stockRegistryInterface;
    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder $_imageBuilder
     */
    protected $_imageBuilder;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */
    protected $_categoryFactory;
    /**
     * @var \Magento\Store\Api\StoreResolverInterface $storeResolverInterface
     */
    protected $storeResolverInterface;
    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface $storeRepositoryInterface
     */
    protected $storeRepositoryInterface;
    /**
     * @var \Magento\Store\Api\GroupRepositoryInterface $groupRepositoryInterface
     */
    protected $groupRepositoryInterface;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Magento\Catalog\Model\ProductRepository $_productRepository
     */
    protected $_productRepository;
    /**
     * @var \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator
     */
    protected $adjustmentCalculator;
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    protected $priceCurrency;
    /**
     * @var \Magento\Framework\Locale\FormatInterface $localeFormat
     */
    protected $localeFormat;
    /**
     * @var \Magento\Swatches\Block\Product\Renderer\Configurable $renderConfigurable
     */
    protected $renderConfigurable;
    /**
     * @var \Riki\Subscription\Helper\Data $_subHelperData
     */
    protected $_subHelperData;
    /**
     * @var \Riki\SubscriptionCourse\Model\Course $_subCourseModel
     */
    protected $_subCourseModel;
    /**
     * @var \Riki\CatalogRule\Helper\Data $catalogRuleHelper
     */
    protected $catalogRuleHelper;

    public function __construct(
        \Riki\SubscriptionCourse\Model\Course $_subCourseModel,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $_subCourseResourceModel,
        \Riki\Subscription\Helper\Data $_subHelperData,
        \Riki\CatalogRule\Helper\Data $catalogRuleHelper,
        \Magento\Swatches\Block\Product\Renderer\Configurable $renderConfigurable,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator,
        \Magento\Catalog\Model\CategoryFactory $_categoryFactory,
        \Magento\Store\Api\StoreResolverInterface $storeResolverInterface,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepositoryInterface,
        \Magento\Store\Api\GroupRepositoryInterface $groupRepositoryInterface,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface,
        \Magento\Catalog\Block\Product\ImageBuilder $_imageBuilder,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data=[]
    )
    {
        $this->_subHelperData = $_subHelperData;
        $this->_subCourseResourceModel = $_subCourseResourceModel;
        $this->_registry=$registry;
        $this->renderConfigurable = $renderConfigurable;
        $this->_categoryFactory = $_categoryFactory;
        $this->stockRegistryInterface = $stockRegistryInterface;
        $this->_imageBuilder = $_imageBuilder;
        $this->_subCourseModel = $_subCourseModel;
        $this->storeResolverInterface = $storeResolverInterface;
        $this->storeRepositoryInterface = $storeRepositoryInterface;
        $this->groupRepositoryInterface = $groupRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_productRepository = $productRepository;
        $this->catalogRuleHelper = $catalogRuleHelper;
        $this->adjustmentCalculator = $adjustmentCalculator;
        $this->priceCurrency = $priceCurrency;
        $this->localeFormat = $localeFormat;
        parent::__construct($context, $data);
        /** @var \Riki\Subscription\Model\Profile\Profile $currentProfile */
        $currentProfile = $registry->registry('subscription_profile');
        /* get website_id */
        $websiteId = 1; // EC site
        try {
            /** @var int $websiteId */
            $websiteId = $context->getStoreManager()->getStore()->getWebsiteId();
        }
        catch (\Exception $exception){ // Unknown Exception Case
            $websiteId = 1; // Assumed EC site
        }
        catch (\Error $error){ // Fatal Error Case: NULL Object Case
            $websiteId = 1; // Assumed EC site
        }
        /** reference to: Riki/SubscriptionPage/Controller/View/Index.php:219 */
        /** @var int $courseId */
        $courseId = $currentProfile->getCourseId();
        $currentFrequency = "FREQUENCY_" . $currentProfile->getFrequencyUnit() . "_" . $currentProfile->getFrequencyInterval();

        /* Cache Key: website_id + course_id + customer_group_id + frequency_id */
        $cacheKey = self::class . '_' .  $websiteId . '_' . $courseId . '_' . $currentFrequency;

        $this->addData(array(
            'cache_lifetime' => 86400, // Cache TTL: 1 Day
            'cache_key'      => $cacheKey,
        ));
    }

    /**
     * @return mixed
     */
    public function getCurrentProfile()
    {
        /**
         * product_cart
         * course_data
         */
        return $this->_registry->registry('subscription_profile');
    }


    /**
     * get cache tags
     *
     * @return array
     */
    public function getCacheTags()
    {
        $tags = parent::getCacheTags();

        $tags[] = \Magento\Framework\App\Cache\Type\Block::TYPE_IDENTIFIER;

        /** @var \Riki\Subscription\Block\Frontend\Profile\Edit $parentBlock */
        $parentBlock = $this->getParentBlock();

        $courseProduct = $parentBlock->getListProductOfCourse();

        if (!empty($courseProduct)) {
            foreach ($courseProduct as $productList) {
                /** @var \Magento\Catalog\Model\Product $product */
                foreach ($productList as $product) {
                    $tags[] = \Magento\Catalog\Model\Product::CACHE_TAG . '_' . $product->getId();
                }
            }
        }

        return $tags;
    }
}