<?php
namespace Riki\SubscriptionCourse\Helper;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\Subscription\Model\Constant;
use Symfony\Component\Config\Definition\Exception\Exception;
use Riki\SubscriptionCourse\Model\ResourceModel\Course\CollectionFactory as CourseCollectionFactory;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionCourseType;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Model\Product\Type as ProductType;
use Riki\AdvancedInventory\Helper\Inventory as HelperInventory;
use Riki\BackOrder\Helper\Data as BackOrderHelper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrl;

    const CONFIG_SUB_UPDATE_STATUS = 'subscriptioncourse/subscription_enable_disable/enable';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $customerCollection;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * Cache on code level
     *
     * @var array
     */
    protected $simpleLocalStorage = [];

    /**
     * @var \Magento\Framework\App\Resource
     */
    protected $resource;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    protected $courseCollectionFactory;

    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $stockStateRepository;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistryInterface;

    /**
     * @var \Riki\AdvancedInventory\Helper\Inventory
     */
    protected $helperInventory;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $subCourseModel;

    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subPageHelperData;

    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course
     */
    protected $subCourseResourceModel;

    /**
     * @var \Riki\Subscription\Model\Profile\Profile
     */
    protected $subProfileModel;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Wyomind\AdvancedInventory\Model\StockRepositery
     */
    protected $stockWyomindRepository;

    /**
     * @var \Riki\SubscriptionFrequency\Helper\Data
     */
    protected $frequencyHelper;

    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
     */
    protected $courseRepository;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * Data constructor.
     * @param \Wyomind\AdvancedInventory\Model\StockRepositery\Proxy $stockWyomindRepositery
     * @param \Riki\Subscription\Model\Profile\Profile\Proxy $subProfileModel
     * @param \Riki\SubscriptionCourse\Model\ResourceModel\Course\Proxy $subCourseResourceModel
     * @param \Riki\SubscriptionPage\Helper\Data\Proxy $subPageHelperData
     * @param \Riki\SubscriptionCourse\Model\Course\Proxy $subCourseModel
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param StockRegistryInterface $stockRegistryInterface
     * @param HelperInventory $helperInventory
     * @param StockStateInterface $stockStateRepository
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CourseCollectionFactory $courseCollectionFactory
     * @param \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper
     * @param ProductFactory $productFactory
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
     */
    public function __construct(
        \Wyomind\AdvancedInventory\Model\StockRepositery\Proxy $stockWyomindRepositery,
        \Riki\Subscription\Model\Profile\Profile\Proxy $subProfileModel,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course\Proxy $subCourseResourceModel,
        \Riki\SubscriptionPage\Helper\Data\Proxy $subPageHelperData,
        \Riki\SubscriptionCourse\Model\Course\Proxy $subCourseModel,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        StockRegistryInterface $stockRegistryInterface,
        HelperInventory $helperInventory,
        StockStateInterface $stockStateRepository,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\State $state,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        CourseCollectionFactory $courseCollectionFactory,
        \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
    ) {
        $this->stockWyomindRepository = $stockWyomindRepositery;
        $this->courseFactory = $courseFactory;
        $this->subProfileModel = $subProfileModel;
        $this->subCourseResourceModel = $subCourseResourceModel;
        $this->subPageHelperData = $subPageHelperData;
        $this->subCourseModel = $subCourseModel;
        $this->stockRegistryInterface = $stockRegistryInterface;
        $this->helperInventory = $helperInventory;
        $this->stockStateRepository = $stockStateRepository;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->backendUrl = $backendUrl;
        $this->customerCollection = $collectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->resource = $resource;
        $this->connection = $this->resource->getConnection('core_write');
        $this->state = $state;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->courseCollectionFactory = $courseCollectionFactory;
        $this->frequencyHelper = $frequencyHelper;
        $this->courseRepository = $courseRepository;
        $this->_productFactory  = $productFactory;
        parent::__construct($context);
    }

    /**
     * get products tab Url in admin
     * @return string
     */
    public function getProducts()
    {
        return $this->backendUrl->getUrl('subscription/course/products', ['_current' => true]);
    }

    public function getHanpukaiFixedProductGridUrl()
    {
        return $this->backendUrl->getUrl('subscription/hanpukai/fixedproducts', ['_current' => true]);
    }

    public function getHanpukaiSequenceProductGridUrl()
    {
        return $this->backendUrl->getUrl('subscription/hanpukai/sequenceproducts', ['_current' => true]);
    }

    public function getMachineUrl()
    {
        if ($this->_request->getParam('type') == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {

        } else {
            return $this->backendUrl->getUrl('subscription/course/machines', ['_current' => true]);
        }
    }

    public function validateDate(\Magento\Framework\DataObject $object)
    {
        $error = [];
        if ($object->hasLaunchDate() && $object->hasCloseDate()) {
            $fromDate = $object->getLaunchDate();
            $toDate = $object->getCloseDate();
        }

        if ($fromDate && $toDate) {
            $fromDate = new \DateTime($fromDate);
            $toDate = new \DateTime($toDate);

            if ($fromDate > $toDate) {
                $error[] = __('Make sure the Close date is later than or the same as the Launch date.');
            }
        }

        return !empty($error) ? $error : true;
    }

    /**
     * Return store
     *
     * @return Store
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }

    public function getStoreConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue($path, $storeScope);
    }

    /**
     * This class will get product only. Validation must check from outside
     *
     * @param $courseId
     */
    public function getAllProductIdOfCourse($courseId) // Include cat
    {
        // product_ids: [0 => ['product_id' => 92], '1' => ['product_id' => 96, 'priority' => 0]]
        // $model->getData("category_ids");,

        $objCourse = $this->loadCourse($courseId);

        if (empty($objCourse->getId())) {
            return [];
        }

        return $this->getAllProductIdOfCourseByObject($objCourse);
    }

    public function getAllMachineIdOfCourse($courseId)
    {
        $objCourse = $this->loadCourse($courseId);

        if (empty($objCourse->getId())) {
            return [];
        }

        return $objCourse->getResource()->getMachinesOfTypeByCourse($objCourse);
    }

    /**
     * @param \Riki\SubscriptionCourse\Model\Course $objCourse
     * @return mixed
     */
    public function getAllProductIdOfCourseByObject(\Riki\SubscriptionCourse\Model\Course $objCourse)
    {
        $courseId = $objCourse->getId();

        if (isset($this->simpleLocalStorage['course'][$courseId]['listProductId'])) {
            return $this->simpleLocalStorage['course'][$courseId]['listProductId'];
        }

        $result = [];

        // Get List product ids
        $arrProductIds = $objCourse->getData('product_ids');
        if(is_array($arrProductIds)) {
            $countProductIds = count($arrProductIds);
        } else {
            $countProductIds = 0;
        }
        for ($i=0; $i< $countProductIds; $i++) {
            $result[$arrProductIds[$i]['product_id']] = $arrProductIds[$i]['product_id'];
        }

        $arrCategoryId = $objCourse->getData('category_ids');

        /*additional categories*/
        if (is_array($objCourse->getData('additional_category_ids')) &&
            count($objCourse->getData('additional_category_ids')) >= 1
        ) {
            $arrCategoryId = array_merge($arrCategoryId, $objCourse->getData('additional_category_ids'));
        }

        $arrMustHave = (array)$objCourse->getData("must_select_sku");
        $arrMustHave = array_filter($arrMustHave); // category = 0, is parent of all category

        $arrCategoryId = array_merge($arrCategoryId, $arrMustHave);
        $arrProductIdOfCategory = $this->getAllProductIdBelongCategory($arrCategoryId);
        $result +=$arrProductIdOfCategory;

        $this->simpleLocalStorage['course'][$courseId]['listProductId'] = $result;

        return $this->simpleLocalStorage['course'][$courseId]['listProductId'];
    }

    /**
     * @param $courseId
     * @return mixed
     */
    public function loadCourse($courseId)
    {
        try {
            return $this->courseRepository->get($courseId);
        } catch (\Exception $e) {
            return $this->courseFactory->create();
        }
    }

    /**
     * Wrong function must fix
     *
     * @param $arrProductId
     * @return array|bool
     */
    public function getIntersectionCourseOfListProduct($arrProductId)
    {
        if (empty($arrProductId)) {
            return false;
        }

        $strCourseCategoryProduct =$this->resource->getTableName('subscription_course_product');

        // @todo Wrong here. Must also get category
        $arrListCourseProduct = $this->resource->getConnection()->fetchAll(
            "SELECT group_concat(course_id) as list_course, product_id FROM $strCourseCategoryProduct group by product_id"
        );

        // Rebuild with key is product_id
        $arrProductIdListCourse = [];
        foreach ($arrListCourseProduct as $arr) {
            $productId = $arr['product_id'];
            $strCourse = $arr['list_course'];
            $arrCourseId = explode(",", $strCourse);

            $arrProductIdListCourse[$productId] = $arrCourseId;
        }

        $arrProductIdInCourse = array_keys($arrProductIdListCourse);

        $arrIntersect = array_intersect($arrProductId, $arrProductIdInCourse);

        if (empty($arrIntersect)) {
            return 1;// have spot. All product add do not belong to current course
        }

        if (!empty(array_diff($arrIntersect, $arrProductId))) {
            // Exist an element do not have in course
            return 1; // have spot
        }

        $arrCourseCommon = $arrProductIdListCourse[$arrProductIdInCourse[0]]; // Get the first key
        foreach ($arrProductId as $productId) {
            if (!isset($arrProductIdListCourse[$productId])) {
                return 1; //$this product id do not have in all product belong to course
            }

            $arrCourseCommon = array_intersect($arrCourseCommon, $arrProductIdListCourse[$productId]);

            if (empty($arrCourseCommon)) {
                // have many course id
                return 2;
            }
        }

        return $arrCourseCommon;
    }

    /**
     * return array('product_id' => 'product_id', ...);
     *
     * @param $arrCategoryId
     * @return array
     */
    public function getAllProductIdBelongCategory($arrCategoryId)
    {
        $result = array();
        if(!is_array($arrCategoryId)){
            $arrCategoryId = array($arrCategoryId);
        }
        if (empty($arrCategoryId)) {
            return $result;
        }
        $filter = $this->searchCriteriaBuilder->addFilter('category_id', $arrCategoryId, 'in')->create();
        $productRepository = $this->productRepository->getList($filter);
        $store = $this->state->getAreaCode();
        if ($store == 'adminhtml') {
            foreach ($productRepository->getItems() as $item) {
                $result[$item->getId()] = $item->getId();
            }
        } else {
            foreach ($productRepository->getItems() as $item) {
                if ($item->getVisibility()) {
                    $result[$item->getId()] = $item->getId();
                }
            }
        }
        return $result;
    }

    /**
     * The all products added must belong to current course
     *
     * @param $arrProductIdInCart
     * @param $courseId
     * @return int
     */
    public function checkCartIsValidForCourse($arrProductIdInCart, $courseId, $nDelivery = null)
    {
        if ($this->getSubscriptionCourseType($courseId) != \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
            $machineIdOfCourse = [];
            if ($this->getSubscriptionCourseType($courseId) == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
                $machineIdOfCourse = $this->getAllMachineIdOfCourse($courseId);
            }
            $arrProductIdOfCourse = $this->getAllProductIdOfCourse($courseId);
            $arrProductIdOfCourse = $arrProductIdOfCourse + $machineIdOfCourse;
        } else {
            $subscriptionPageHelper = $this->subPageHelperData;
            $arrProductIdOfCourse = $subscriptionPageHelper->getHanpukaiProductId(
                $this->getHanpukaiType($courseId),
                $courseId,
                $nDelivery
            );
        }

        $arrProductIdInCart = $this->checkGilletteProducts($arrProductIdInCart);

        $arrIntersect = array_intersect($arrProductIdInCart, $arrProductIdOfCourse);

        if ($arrIntersect == $arrProductIdInCart) {
            return 0; // No error
        }

        return 1; // Have spot
    }

    /**
     * @param $arrProductIdInCart
     * @return array
     */
    public function checkGilletteProducts($arrProductIdInCart)
    {
        $skuGillette = $this->scopeConfig->getValue('gillette/general/gillette_sku');
        $skuBlade = $this->scopeConfig->getValue('gillette/general/blade_sku');
        $productModel = $this->_productFactory->create();
        $gilletteId = $productModel->getIdBySku($skuGillette);
        $bladeId = $productModel->getIdBySku($skuBlade);

        if (in_array($gilletteId, $arrProductIdInCart) && in_array($bladeId, $arrProductIdInCart))
        {
            $arrProductIdInCart = [];
        }

        return $arrProductIdInCart;


    }

    /**
     * @param $arrProductId
     * @param $catId
     * @return array
     */
    public function getProductMustHaveInCart($arrProductId, $catId)
    {
        if (empty($catId)) {
            return [];
        }

        $arrProductIdOfCat = $this->getAllProductIdBelongCategory([$catId]);

        return array_intersect($arrProductIdOfCat, $arrProductId);
    }

    /**
     * Valid Subscription Course Must Select Sku
     *
     * @param array $arrProductId  product Id from cart
     * @param string $catId        backend config
     * @return int
     * @throws \Exception
     */
    public function isValidMakeHaveInCart($arrProductId, $catId)
    {
        if (is_array($catId)) {
            return 0;
        }

        if (empty($catId)) {
            // Empty cat so Ok
            return 1;
        }
        $arrCategoryIdQty = explode(':', $catId);
        if (count($arrCategoryIdQty) > 1) {
            $arrProductId = $this->getProductMustHaveInCart($arrProductId, $arrCategoryIdQty[0]);
        }

        if (! empty($arrProductId)) {
            return 1;
        }

        return 0;
    }

    public function isValidMustHaveQtyInCategory($arrProductIdQtyFromCart, $catId)
    {
        if (!$catId) {
            return 1;
        }
        $arrCategoryIdQty = explode(':', $catId);
        $arrProductIdFromCart = array_keys($arrProductIdQtyFromCart);
        if (count($arrCategoryIdQty) > 1) {
            $qtyOfCategoryConfigInCart = 0;
            $arrProductIdMerge = $this->getProductMustHaveInCart($arrProductIdFromCart, $arrCategoryIdQty[0]);
            foreach ($arrProductIdMerge as $productId) {
                $qtyOfCategoryConfigInCart = $qtyOfCategoryConfigInCart + $arrProductIdQtyFromCart[$productId];
            }
            if ($arrCategoryIdQty[1] <= $qtyOfCategoryConfigInCart) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 1;
        }
    }

    /**
     * get frequency by id
     *
     * @param $courseId
     * @return array
     */
    public function getFrequenciesByCourse($courseId)
    {
        $objCourse = $this->loadCourse($courseId);
        $result = [];
        $selectedFrequency = $objCourse->getData('frequency_ids');
        $selectedFrequencyArr = [];
        foreach ($selectedFrequency as $frequencySelectedItem) {
            $selectedFrequencyArr[] = $frequencySelectedItem;
        }

        $allFrequency = $this->subCourseResourceModel->getAllFrequencies();

        if ($allFrequency) {
            foreach ($allFrequency as $frequency) {
                $frequencyId = $frequency['frequency_id'];
                if (in_array($frequencyId, $selectedFrequencyArr)) {
                    $result[$frequencyId] = $this->frequencyHelper->formatFrequency(
                        $frequency['frequency_interval'],
                        $frequency['frequency_unit']
                    );
                }
            }
        }

        return $result;
    }

    /**
     * @param $courseId
     * @return mixed
     */
    public function getAddtionCategoryByCourse($courseId)
    {
        $objCourse = $this->subCourseModel;
        $objCourse->load($courseId);

        return $objCourse->getData('additional_category_ids');
    }

    /**
     * This function require session permission do not allow for: cron
     *
     * @param $customerId
     * @param $courseId
     * @return mixed
     */
    public function isHaveViewCoursePermission($customerId, $courseId)
    {
        if ($this->state->getAreaCode() === "cron") {
            throw new \Magento\Framework\Exception\LocalizedException(
                new \Magento\Framework\Phrase('Used this function for the environment have session only')
            );
        }

        if (isset($this->simpleLocalStorage['permission'][$courseId][$customerId])) {
            return $this->simpleLocalStorage['permission'][$courseId][$customerId];
        }

        $objCourse = $this->loadCourse($courseId);
        if (!$objCourse) {
            return $this->simpleLocalStorage['permission'][$courseId][$customerId] = true;
        }

        $membershipCourse = $objCourse->getMembershipIds();
        if (empty($membershipCourse)) {
            return $this->simpleLocalStorage['permission'][$courseId][$customerId] = true;
            // easy. empty membership of course allow every customer
        }

        $customerDataObject = $this->customerRepository->getById($customerId);

        if ($customerDataObject->getCustomAttribute('membership') == null) {
            return $this->simpleLocalStorage['permission'][$courseId][$customerId] = false;
        }

        $arrMembershipCustomer = explode(',', $customerDataObject->getCustomAttribute('membership')->getValue());
        if (empty($arrMembershipCustomer)) {
            return $this->simpleLocalStorage['permission'][$courseId][$customerId] = false;
        }

        foreach ($arrMembershipCustomer as $membership) {
            // check if customer don't have in membership of subscription course
            if (in_array($membership, $membershipCourse)) {
                return $this->simpleLocalStorage['permission'][$courseId][$customerId] = true;
            }
        }
        return $this->simpleLocalStorage['permission'][$courseId][$customerId];
    }

    public function loadProfile($profileId)
    {
        if (isset($this->simpleLocalStorage['profile'][$profileId])) {
            return $this->simpleLocalStorage['profile'][$profileId];
        }

        $objProfile = $this->subProfileModel;
        $objProfile->load($profileId);

        if (!empty($objProfile->getId())) {
            $this->simpleLocalStorage['profile'][$profileId] = $objProfile;
            return $this->simpleLocalStorage['profile'][$profileId];
        }

        return false;
    }

    /**
     * Validate when edit profile
     *
     * @param $courseId
     * @param $arrProductId
     * @param $qty
     * @param $arrProductIdQty
     * @param null $nDelivery
     * @return int
     * @throws \Exception
     */
    public function validateProductOfCourse($courseId, $arrProductId, $qty, $arrProductIdQty, $nDelivery = null)
    {
        $objCourse = $this->subCourseModel;
        $objCourse->load($courseId);

        if (empty($objCourse->getId())) {
            // not yet set course_id in quote
            return 0;
        }

        // Check product must belong course
        $errorCode = $this->checkCartIsValidForCourse($arrProductId, $courseId, $nDelivery);

        // Check at least on must have sku
        $mustHaveCatId = $objCourse->getData("must_select_sku");
        $isValid = $this->isValidMakeHaveInCart($arrProductId, $mustHaveCatId);
        if (!$isValid) {
            return Constant::ERROR_SUBSCRIPTION_COURSE_MUST_SELECT_SKU;
        }
        /*Check cart has main product course*/

        $minimumOrderQty = $objCourse->getData("minimum_order_qty");
        if ($qty < $minimumOrderQty) {
            return Constant::ERROR_SUBSCRIPTION_COURSE_MINIMUM_QTY; // Maximum limit
        }

        if (!$this->isValidMustHaveQtyInCategory($arrProductIdQty, $mustHaveCatId)) {
            return Constant::ERROR_SUBSCRIPTION_COURSE_MUST_HAVE_QTY_CATEGORY;
        }

        return 0;
    }

    /**
     * Hanpukai helper
     */

    public function getSubscriptionCourseType($courseId)
    {
        return $this->loadCourse($courseId)->getData('subscription_type');
    }

    public function getHanpukaiType($courseId)
    {
        return $this->loadCourse($courseId)->getData('hanpukai_type');
    }

    public function arrCategoryIdQty($courseId)
    {
        $objCourse = $this->subCourseModel;
        $objCourse->load($courseId);
        $mustHaveCatId = $objCourse->getData("must_select_sku");
        return explode(':', $mustHaveCatId);
    }

    public function getMinimumQtyShoppingCart($courseId)
    {
        $objCourse = $this->subCourseModel;
        $objCourse->load($courseId);
        if ($objCourse) {
            return $objCourse->getData('minimum_order_qty');
        } else {
            return 0;
        }
    }

    /**
     *  Tmp function
     *
     * @reutrn void
     */
    public function deleteAllProductOfSubCourse()
    {
        $arrCourseId =  [];
        $courseCollection = $this->courseCollectionFactory->create()->addFieldToSelect('course_id')
            ->addFieldToFilter('subscription_type', ['neq' => SubscriptionCourseType::TYPE_HANPUKAI]);
        foreach ($courseCollection as $course) {
            $arrCourseId[] = $course->getData('course_id');
        }
    }

    /**
     * Get Machine list of current sub course
     *
     * @param int $courseId
     *
     * @return array|bool|\Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function getMachineOption($courseId)
    {
        $machines = $this->loadCourse($courseId)->getProductMachines();
        if (!$machines) {
            return false;
        }

        $productIds = array_map(function ($machine) {
            return $machine['product_id'];
        }, $machines);

        $search = $this->searchCriteriaBuilder->addFilter('entity_id', $productIds, 'in')->create();
        $machineProducts = $this->productRepository->getList($search)->getItems();

        $machineProducts = array_filter(array_map(function ($product) {
            if ($this->checkProductAvailableForShow($product)) {
                return $product;
            }
            return null;
        }, $machineProducts));

        // sort items
        $sortMachineProducts = [];
        foreach ($productIds as $id) {
            if (isset($machineProducts[$id])) {
                $sortMachineProducts[] = $machineProducts[$id];
            }
        }

        return $sortMachineProducts;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function checkProductAvailableForShow($product)
    {
        $storeIdsOfProduct = $product->getStoreIds();
        $currentStoreId = $this->storeManager->getStore()->getId();
        $productIsActiveInStore = false;
        if (in_array($currentStoreId, $storeIdsOfProduct)) {
            $productIsActiveInStore = true;
        }
        if ($product->getStatus() == 1
            && $product->getIsSalable()
            && $product->getVisibility() != \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE
            && $productIsActiveInStore
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get course code by course ID
     *
     * @param $courseId
     * @return mixed
     */
    public function getCourseCodeByCourseId($courseId)
    {
        return $this->loadCourse($courseId)->getData('course_code');
    }

    /**
     * @param $configName
     * @return mixed
     */
    public function getStockConfigByPath($configName){
                return $this->scopeConfig->getValue('cataloginventory/item_options/' . $configName);
     }

    /**
     * Get course code for customer
     *
     * @param $customerId
     * @return string
     */
    public function getListCourseCodeByCustomerId($customerId)
    {
        $courseCode = $this->subCourseResourceModel->getAllCourseCodeInProfileActiveByCustomerId($customerId);
        return implode($courseCode,',');
    }

    public function getCourseByCode($courseCode) {
        try {
            $course = $this->courseRepository->getCourseByCode($courseCode);
            if ($course && $course->getIsEnable()) {
                return $course;
            }
        } catch (\Exception $e) {
            throw $e;
        }
        throw new NoSuchEntityException(
            __(
                'No such entity with %fieldName = %fieldValue',
                ['fieldName' => 'course_code', 'fieldValue' => $courseCode]
            )
        );
    }

    public function getListMachineTypeByCourse($courseId){
        return $this->loadCourse($courseId)->getListMachineType();
    }

}
