<?php
namespace Riki\Catalog\Block\Multiple;
use Magento\Store\Api\StoreResolverInterface as StoreResolverInterface;
use Magento\Store\Api\GroupRepositoryInterface as GroupRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface as StoreRepositoryInterface;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use \Riki\BackOrder\Helper\Data as BackOrderHelper;
class View extends \Magento\Framework\View\Element\Template
{

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */

    protected $_coreRegistry;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $_helperPrice;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;

    /**
     * @var \Riki\Subscription\Helper\Data
     */
    protected $_helperData;
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $_helperImage;


    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    protected $_productFactory;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $_formKey;
    /**
     * @var \Magento\Catalog\Helper\Category
     */
    protected $_categoryHelper;
    /**
     * @var \Magento\Catalog\Model\Indexer\Category\Flat\State
     */
    protected $categoryFlatConfig;
    /**
     * @var
     */
    protected $_category;
    /**
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $_categoryRepository;
    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    protected $_imageBuilder;
    /**
     * @var \Riki\ProductStockStatus\Helper\StockData
     */
    protected $_stockData;
    /**
     * @var \Magento\Swatches\Block\Product\Renderer\Configurable
     */
    protected $_configurable;
    /**
     * @var \Magento\Catalog\Block\Product\View
     */
    protected $_productView;
    /**
     * @var StoreResolverInterface
     */
    protected $storeResolverInterface;
    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepositoryInterface;
    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepositoryInterface;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $_categoryCollectionFactory;
    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_productVisibility;
    /**
     * @var \Magento\CatalogInventory\Model\Stock\StockItemRepository
     */
    protected $stock;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @var \Wyomind\Core\Helper\Data
     */
    protected $_helperCore;
    /**
     * @var \Riki\AdvancedInventory\Helper\Inventory
     */
    protected $helperInventory;
    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;
    /* @var \Riki\BackOrder\Helper\Data */
    protected $backOrderHelper;

    /**
     * View constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Pricing\Helper\Data $helperPrice
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Magento\Catalog\Helper\Image $helperImage
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Catalog\Helper\Category $categoryHelper
     * @param \Magento\Catalog\Model\Indexer\Category\Flat\State $categoryFlatState
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     * @param \Riki\ProductStockStatus\Helper\StockData $stockData
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface
     * @param \Magento\Swatches\Block\Product\Renderer\Configurable $configurable
     * @param \Magento\Catalog\Block\Product\View $productView
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param StoreRepositoryInterface $storeRepositoryInterface
     * @param StoreResolverInterface $storeResolverInterface
     * @param GroupRepositoryInterface $groupRepositoryInterface
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
     * @param \Wyomind\Core\Helper\Data $helperCore
     * @param \Wyomind\AdvancedInventory\Helper\Data $helperData
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Pricing\Helper\Data $helperPrice,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Magento\Catalog\Helper\Image $helperImage,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\Indexer\Category\Flat\State $categoryFlatState,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Riki\ProductStockStatus\Helper\StockData $stockData,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface,
        \Magento\Swatches\Block\Product\Renderer\Configurable $configurable,
        \Magento\Catalog\Block\Product\View $productView,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        StoreRepositoryInterface $storeRepositoryInterface,
        StoreResolverInterface $storeResolverInterface,
        GroupRepositoryInterface $groupRepositoryInterface,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository,
        \Wyomind\Core\Helper\Data $helperCore,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Magento\Framework\App\ResourceConnection $resource,
        \Riki\AdvancedInventory\Helper\Inventory $helperInventory,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Riki\BackOrder\Helper\Data $backOrderHelper,
        array $data = []
    )
    {
        $this->stock =$stockItemRepository;
        $this->_coreRegistry = $registry;
        $this->_helperPrice = $helperPrice;
        $this->_dateTime = $dateTime;
        $this->_helperImage = $helperImage;
        $this->_timezone = $context->getLocaleDate();
        $this->_dateTime = $dateTime;
        $this->_productFactory = $productFactory;
        $this->_categoryFactory = $categoryFactory;
        $this->_categoryHelper = $categoryHelper;
        $this->categoryFlatConfig = $categoryFlatState;
        $this->_categoryRepository = $categoryRepository;
        $this->_imageBuilder = $imageBuilder;
        $this->_stockData = $stockData;
        $this->_formKey = $formKey;
        $this->stockRegistry = $stockRegistryInterface;
        $this->_configurable = $configurable;
        $this->_productView = $productView;
        $this->storeRepositoryInterface = $storeRepositoryInterface;
        $this->storeResolverInterface = $storeResolverInterface;
        $this->groupRepositoryInterface = $groupRepositoryInterface;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_productVisibility = $productVisibility;
        $this->_helperCore = $helperCore;
        $this->_helperData = $helperData;
        $this->_resource = $resource;
        $this->helperInventory = $helperInventory;
        $this->functionCache = $functionCache;
        $this->backOrderHelper = $backOrderHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        $idCategory = $this->_request->getParam('id');
        $this->pageConfig->getTitle()->set(__('The selection of goods'));
        return parent::_prepareLayout();
    }

    /**
     * Check product saleable in category
     * @param $productsList
     * @return bool
     */
    public function checkSaleable($productsList){
        if($productsList->getSize() ){
            foreach ($productsList as $product) {
                if($product->isSaleable()) return true;
            }
        }

        return false;

    }
    /**
     * Retrieve current store categories
     *
     * @param bool|string $sorted
     * @param bool $asCollection
     * @param bool $toLoad
     * @return \Magento\Framework\Data\Tree\Node\Collection|\Magento\Catalog\Model\Resource\Category\Collection|array
     */
    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        return $this->_categoryHelper->getStoreCategories($sorted = false, $asCollection = false, $toLoad = true);
    }
    /**
     * Get category collection
     *
     * @param bool $isActive
     * @param bool|int $level
     * @param bool|string $sortBy
     * @param bool|int $pageSize
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection or array
     */
    public function getCategoryMultiple($isActive = true, $level = 2, $sortBy = false, $pageSize = false)
    {
        // Get root category ID 
        $currentStoreId = $this->storeResolverInterface->getCurrentStoreId();
        $defaultGroupId = $this->storeRepositoryInterface->getById($currentStoreId)->getData('group_id');
        $rootCategoryCollection = $this->groupRepositoryInterface->get($defaultGroupId);
        if ($rootCategoryCollection) {
            $rootCategoryOfStore = $rootCategoryCollection->getData('root_category_id');
        }else{
            $rootCategoryOfStore = 1;
        }
        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect('*');

        // select only active categories
        if ($isActive) {
            $collection->addIsActiveFilter();
        }

         //select categories of certain level
        if ($level) {
            $collection->addLevelFilter($level);
        }

        // sort categories by some value
        if ($sortBy) {
            $collection->addOrderField($sortBy);
        }
        // Filter multiple
        $collection->addAttributeToFilter('multiple_products', 1);
        // Filter root category
        $collection->addFilter('parent_id',$rootCategoryOfStore);
        // select certain number of categories
        if ($pageSize) {
            $collection->setPageSize($pageSize);
        }

        return $collection;
    }

    public function getCategory($categoryId)
    {
        $category = $this->_categoryFactory->create();
        $category->load($categoryId);
        return $category;
    }

    /**
     * @param $categoryId
     * @param $idsProductToFilter
     * @return array
     */
    public function getCategoryProducts($categoryId, $idsProductToFilter = null)
    {
        $rootCategoryOfStore = null;
        // Not load product from default category
        $currentStoreId = $this->storeResolverInterface->getCurrentStoreId();
        $defaultGroupId = $this->storeRepositoryInterface->getById($currentStoreId)->getData('group_id');
        $rootCategoryCollection = $this->groupRepositoryInterface->get($defaultGroupId);
        if ($rootCategoryCollection) {
            $rootCategoryOfStore = $rootCategoryCollection->getData('root_category_id');
        }
        if ($rootCategoryOfStore != null && $rootCategoryOfStore == $categoryId) {
            return array();
        }
        try {
            $category = $this->_categoryRepository->get($categoryId);
            if($category->getIsActive()) {
                $productCollections = $category->getProductCollection()->addAttributeToSelect('*');
                return  $productCollections;
            }
        }
        catch(\Exception $e){
            return false;
        }

    }

    /**
     * Retrieve child store categories
     *
     */
    public function getChildCategories($category)
    {
        if ($this->categoryFlatConfig->isFlatEnabled() && $category->getUseFlatResource()) {
            $subcategories = (array)$category->getChildrenNodes();
        } else {
            $subcategories = $category->getChildren();
        }
        return $subcategories;
    }
    /**
     * Return categories helper
     */
    public function getCategoryHelper()
    {
        return $this->_categoryHelper;
    }
    /**
     * Get category object
     * Using $_categoryRepository
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function getCategoryById($categoryId)
    {
        return $this->_categoryRepository->get($categoryId);
    }

    /**
     * Get parent category object
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function getParentCategory($categoryId = false)
    {
        if ($this->_category) {
            return $this->_category->getParentCategory();
        } else {
            return $this->getCategory($categoryId)->getParentCategory();
        }
    }

    /**
     * Get parent category identifier
     *
     * @return int
     */
    public function getParentId($categoryId = false)
    {
        if ($this->_category) {
            return $this->_category->getParentId();
        } else {
            return $this->getCategory($categoryId)->getParentId();
        }
    }

    /**
     * Get all parent categories ids
     *
     * @return array
     */
    public function getParentIds($categoryId = false)
    {
        if ($this->_category) {
            return $this->_category->getParentIds();
        } else {
            return $this->getCategory($categoryId)->getParentIds();
        }
    }

    /**
     * Get all children categories IDs
     *
     * @param boolean $asArray return result as array instead of comma-separated list of IDs
     * @return array|string
     */
    public function getAllChildren($asArray = false, $categoryId = false)
    {
        if ($this->_category) {
            return $this->_category->getAllChildren($asArray);
        } else {
            return $this->getCategory($categoryId)->getAllChildren($asArray);
        }
    }

    /**
     * Retrieve children ids comma separated
     *
     * @return string
     */
    public function getChildren($categoryId = false)
    {
        if ($this->_category) {
            return $this->_category->getChildren();
        } else {
            return $this->getCategory($categoryId)->getChildren();
        }
    }
    public function getImage($product, $imageId, $attributes = [])
    {
        return $this->_imageBuilder->setProduct($product)
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create()
	          ->setTemplate('Riki_SubscriptionPage::product/image_with_borders_lazy_load.phtml');
    }

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function getProductUrlByStore($product) {
        return $product->getUrlInStore(array("_store" => $this->getStoreId()));
    }

    /**
     * @param $product
     * @return array
     */
    public function getNoticeMessage($product){
        $noticeProduct = array();
        $stockMessageArr = $this->_stockData->getStockStatusMessage($product);

        if (array_key_exists('class', $stockMessageArr)
            && array_key_exists('message', $stockMessageArr)) {
            $noticeProduct['classMessage'] = $stockMessageArr['class'];
            $noticeProduct['textMessage'] = $stockMessageArr['message'];
        } else{
            $noticeProduct['classMessage'] = '';
            $noticeProduct['textMessage'] = '';
        }
        return $noticeProduct;
    }

    public function getAddToCartFormKey()
    {
        return $this->_formKey->getFormKey();
    }

    /**
     * Get Unit Qty
     *
     * @param $product
     *
     * @return int
     */
    public function getUnitQty($product){
        if($product->getUnitQty()){
            return $product->getUnitQty();
        }
        else{
            return 1;
        }
    }
    /**
     * Gets minimal sales quantity for subscription page
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return int|null
     */
    public function getMinimalQty($product)
    {
        $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
        $minSaleQty = $stockItem->getMinSaleQty();
        return $minSaleQty > 0 ? $minSaleQty : null;
    }

    /**
     * Gets maximum sales quantity for subscription page
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return int|null
     */
    public function getMaximumQty($product)
    {
        $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
        $maxSaleQty = $stockItem->getMaxSaleQty();
        return $maxSaleQty > 0 ? $maxSaleQty : null;
    }

    //\Magento\Swatches\Block\Product\Renderer\Configurable
    public function getJsonConfig($configurableProduct)
    {
        return $this->_configurable->setProduct($configurableProduct);
    }


    // \Riki\SubscriptionPage\Block\Configurable\Render\Prices;
    public function getJsonPriceConfig($configurableProduct)
    {
        $this->_coreRegistry->register('product', $configurableProduct);
        $configurableBlock = $this->_productView;
        return $configurableBlock;
    }
    public function getProductType($product)
    {
        return $product->getTypeId();
    }
    public function deleteRegister($key)
    {
//        \Magento\Framework\Registry
        $this->_coreRegistry->unregister($key);
    }

    /**
     * Get Unit Display
     *
     * @param $product
     *
     * @return array
     */
    public function getUnitDisplay($product){

        if('bundle' == $product->getTypeId()){
            return array();
        }

        if($product->getCaseDisplay() == 1){
            return array('ea' => __('EA'));
        }
        else
            if($product->getCaseDisplay() == 2){
                return array('cs' => __('CS').'('.$this->getUnitQty($product).' '.__('EA').')');
            }
            else
                if($product->getCaseDisplay() == 3){
                    return array('ea' => __('EA'),'cs' => __('CS').'('.$this->getUnitQty($product).' '.__('EA').')');
                }
                else{
                    return array('ea' => __('EA'));
                }
    }

    /**
     * @return mixed
     */
    public function getIdCat(){
        return $this->_request->getParam('id');
    }

    /**
     * @return mixed|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public  function getDescription() {
        $urlCategory = $this->_request->getParam('id');
        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect('description')
            ->addAttributeToFilter('url_key',$urlCategory);
        if($collection->getSize()){
            return $collection->getFirstItem()->getData('description');
        }
        return '';
    }
    /**
     * @return array|bool
     */
    public function  getMultipleProduct(){
        $listCategory = [];
        $urlCategory = $this->_request->getParam('id');
        //get Id by url
        $categoryCollection = $this->getCategoryCollection($urlCategory);
        $idCategory = $categoryCollection;
        
        $rootCategoryOfStore = null;
        // Not load product from default category
        $currentStoreId = $this->storeResolverInterface->getCurrentStoreId();
        $defaultGroupId = $this->storeRepositoryInterface->getById($currentStoreId)->getData('group_id');
        $rootCategoryCollection = $this->groupRepositoryInterface->get($defaultGroupId);
        if ($rootCategoryCollection) {
            $rootCategoryOfStore = $rootCategoryCollection->getData('root_category_id');
        }
        if ($rootCategoryOfStore != null && $rootCategoryOfStore == $idCategory) {
            return array();
        }
        try {
            if(intval($idCategory)){
                // Category level 1
                $category = $this->_categoryRepository->get($idCategory);
                if($category->getIsActive()) {
                    if($category->getChildren()){
                        $arrayChildren = explode(',',$category->getChildren());

                        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $childrenCollection */
                        $childrenCollection = $this->_categoryCollectionFactory->create();
                        $childrenCollection->addAttributeToFilter('entity_id', ['in'    =>  $arrayChildren])
                            ->addAttributeToSelect('position')
                            ->addAttributeToSelect('multiple_products')
                            ->addAttributeToSelect('name')
                            ->addAttributeToSelect('is_active')
                            ->addAttributeToSort('position');

                        $listCategory = $childrenCollection->getItems();

                    }else{
                        $listCategory[] = $category;
                    }
                }
            }else{
                return $listCategory;
            }

        }
        catch(\Exception $e){
            return false;
        }

        return $listCategory;
    }

    /**
     * get entity_id catolog by url
     *
     * @param $urlKey
     * @return mixed|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCategoryCollection($urlKey){
        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect('entity_id')
            ->addAttributeToFilter('url_key',$urlKey);
        if($collection->getSize()){
            return $collection->getFirstItem()->getData('entity_id');
        }
        return '';
    }
    /**
     * Get backlink
     * 
     *@return string
     */
    public function getBackUrl()
     {
         $refererUrl = $this->_request->getServer('HTTP_REFERER');
         
        if ($refererUrl) {
            if(strpos($refererUrl, 'catalog/multiple/index') === false)
                return $refererUrl;
        }
       return $this->getUrl('catalog/multiple/index', ['_secure' => false]);
     }


    public function isHanpukai()
    {
        return false;
    }

    public function render($template, $vars = [])
    {
        foreach ($vars as $key => $var) {
            $this->assign($key, $var);
        }
        return $this->fetchView($this->getTemplateFile($template));
    }
}