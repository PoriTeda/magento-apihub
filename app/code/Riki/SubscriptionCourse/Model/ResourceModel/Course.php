<?php
namespace Riki\SubscriptionCourse\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Riki\SubscriptionCourse\Model\Course\Type;
use Riki\SubscriptionCourse\Model\CourseFactory;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\State as AppState;
use Magento\Store\Api\StoreResolverInterface as StoreResolverInterface;
use Magento\Store\Api\GroupRepositoryInterface as GroupRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface as StoreRepositoryInterface;
use Magento\Cms\Model\Template\FilterProvider;

class Course extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const TABLE_HANPUKAI_PRODUCT_FIXED = 'hanpukai_fixed';
    const TABLE_HANPUKAI_PRODUCT_SEQUENCE = 'hanpukai_sequence';

    protected $_hanpukaiFixedTable;
    protected $_hanpukaiSequenceTable;
    protected $_subscriptionMachineTable;
    protected $_subscriptionMachineTypeLinkTable;

    protected $dateTime;
    protected $timezoneInterface;

    /**
     * @var array
     */
    private static $_productDetailJS = [];

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */

    protected $_storeManager;

    /**
     * Store associated with rule entities information map
     *
     * @var array
     */
    protected $_associatedEntitiesMap = [
        'website' => [
            'associations_table' => 'subscription_course_website',
            'course_id_field' => 'course_id',
            'entity_id_field' => 'website_id',
        ],
        'frequency' => [
            'associations_table' => 'subscription_course_frequency',
            'course_id_field' => 'course_id',
            'entity_id_field' => 'frequency_id',
        ],
        'category' => [
            'associations_table' => 'subscription_course_category',
            'course_id_field' => 'course_id',
            'entity_id_field' => 'category_id',
            'is_addition_field' => 'is_addition',
            'profile_only_field' => 'profile_only'
        ],
        'payment' => [
            'associations_table' => 'subscription_course_payment',
            'course_id_field' => 'course_id',
            'entity_id_field' => 'payment_id',
        ],
        'membership' => [
            'associations_table' => 'subscription_course_membership',
            'course_id_field' => 'course_id',
            'entity_id_field' => 'membership_id',
        ],
        'merge_profile' => [
            'associations_table' => 'subscription_course_merge_profile',
            'course_id_field' => 'course_id',
            'entity_id_field' => 'merge_profile_to',
        ],
        'multi_machine' => [
            'associations_table' => 'subscription_course_machine_type_link',
            'course_id_field' => 'course_id',
            'entity_id_field' => 'machine_type_id',
        ],
    ];
    protected $_courseFactory;
    protected $connectionName = 'sales';
    protected  $_categoryFactory;
    protected  $_productFactory;
    protected $_stdTimezone;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected  $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\CatalogRule\Model\RuleFactory
     */
    protected $_ruleModelFactory;

    /**
     * @var \Bluecom\PaymentCustomer\Model\Source\Config\Customer\Group
     */
    protected $_customerGroup;

    protected $_customer;

    /**
     * @var \Magento\CatalogRule\Model\Indexer\Product\ProductRuleProcessor
     */
    protected $_productRuleProcessor;

    /**
     * @var \Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor
     */
    protected $_ruleProductProcessor;

    /**
     * @var \Riki\SalesRule\Model\ResourceModel\Rule
     */
    protected $_salesruleResourceModel;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \Riki\MachineApi\Model\B2CMachineSkusFactory
     */
    protected $machineTypeFactory;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;
    protected $storeResolverInterface;
    protected $groupRepositoryInterface;
    protected $storeRepositoryInterface;
    protected $productCategoryExist = [];
    protected $scopeConfig;

    protected $productCollection = [];
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    private $serialize;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * @var FilterProvider
     */
    private $filterProvider;

    /**
     * @var \Riki\ProductStockStatus\Helper\StockData
     */
    protected $stockDataHelper;

    /**
     * @var \Magento\Catalog\Helper\Output
     */
    private $attributeOutputHelper;

    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    private $imageFactory;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;
    /**
     * Course constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Riki\SubscriptionCourse\Helper\Data $helperSubscriptionCourse
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     * @param CourseFactory $courseFactory
     * @param \Riki\Subscription\Logger\LoggerReplaceProduct $logger
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AppState $state
     * @param \Magento\CatalogRule\Model\RuleFactory $ruleModelFactory
     * @param \Bluecom\PaymentCustomer\Model\Source\Config\Customer\Group $customerGroup
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor $ruleProductProcessor
     * @param \Magento\CatalogRule\Model\Indexer\Product\ProductRuleProcessor $productRuleProcessor
     * @param \Riki\SalesRule\Model\ResourceModel\Rule $salesruleResourceModel
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        CourseFactory $courseFactory,
        \Riki\Subscription\Logger\LoggerReplaceProduct $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\App\State $state,
        \Magento\CatalogRule\Model\RuleFactory $ruleModelFactory,
        \Bluecom\PaymentCustomer\Model\Source\Config\Customer\Group $customerGroup,
        \Magento\Customer\Model\Customer $customer,
        \Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor $ruleProductProcessor,
        \Magento\CatalogRule\Model\Indexer\Product\ProductRuleProcessor $productRuleProcessor,
        \Riki\SalesRule\Model\ResourceModel\Rule $salesruleResourceModel,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        StoreRepositoryInterface $storeRepositoryInterface,
        StoreResolverInterface $storeResolverInterface,
        GroupRepositoryInterface $groupRepositoryInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\MachineApi\Model\B2CMachineSkusFactory $machineTypeFactory,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magento\Backend\Model\Auth\Session $authSession,
        FilterProvider $filterProvider,
        \Riki\ProductStockStatus\Helper\StockData $stockDataHelper,
        \Magento\Catalog\Helper\Output $output,
        \Magento\Catalog\Helper\ImageFactory $imageHelperFactory,
        \Magento\Framework\Escaper $escaper,
        $connectionName = null
    )
    {
        $this->functionCache = $functionCache;
        $this->_customerRepository = $customerRepository;
        $this->_salesruleResourceModel = $salesruleResourceModel;
        $this->_productRuleProcessor = $productRuleProcessor;
        $this->_ruleProductProcessor = $ruleProductProcessor;
        $this->_stdTimezone = $stdTimezone;
        $this->_storeManager = $storeManagerInterface;
        $this->_categoryFactory = $categoryFactory;
        $this->_productFactory = $productFactory;
        $this->_courseFactory = $courseFactory;
        $this->dateTime = $datetime;
        $this->timezoneInterface = $timezoneInterface;
        $this->_logger = $logger;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->appState = $state;
        $this->_ruleModelFactory = $ruleModelFactory;
        $this->_customerGroup = $customerGroup;
        $this->_customer  = $customer;
        $this->resourceConnection = $context->getResources();
        $this->storeRepositoryInterface = $storeRepositoryInterface;
        $this->storeResolverInterface = $storeResolverInterface;
        $this->groupRepositoryInterface = $groupRepositoryInterface;
        $this->scopeConfig = $scopeConfig;
        $this->machineTypeFactory = $machineTypeFactory;
        $this->cache = $cache;
        $this->serialize = $serialize;
        $this->authSession = $authSession;
        $this->filterProvider = $filterProvider;
        $this->stockDataHelper = $stockDataHelper;
        $this->attributeOutputHelper = $output;
        $this->imageFactory = $imageHelperFactory;
        $this->escaper = $escaper;
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('subscription_course', 'course_id');
    }

    public function getWebsiteIds($id)
    {
        return $this->getAssociatedEntityIds($id, 'website');
    }

    public function getFrequencyIds($id)
    {
        return $this->getAssociatedEntityIds($id, 'frequency');
    }

    public function getCategoryIds($id)
    {
        return $this->getAssociatedCategoryIds($id, 'category');
    }

    public function getAdditionalCategoryIds($id)
    {
        return $this->getAssociatedAdditionalCategoryIdsEntity($id, 'category');
    }

    public function getProfileCategoryIds($id)
    {
        return $this->getAssociatedProfileCategoryIdsEntity($id, 'category');
    }

    public function getPaymentIds($id)
    {
        return $this->getAssociatedEntityIds($id, 'payment');
    }
    public function getMembershipIds($id)
    {
        return $this->getAssociatedEntityIds($id, 'membership');
    }
    public function getMergeProfileTo($id)
    {
        return $this->getAssociatedEntityIds($id, 'merge_profile');
    }

    public function getMultiMachine($id)
    {
        return $this->getAssociatedEntityIds($id, 'multi_machine');
    }

    public function getAssociatedEntityIds($id, $entityType)
    {
        $entityInfo = $this->_getAssociatedEntityInfo($entityType);
        $select = $this->getConnection()->select()->from(
            $this->getTable($entityInfo['associations_table']),
            [$entityInfo['entity_id_field']]
        )->where(
            $entityInfo['course_id_field'] . ' = ?',
            $id
        );
        return $this->getConnection()->fetchCol($select);
    }

    public function getAssociatedCategoryIds($id, $entityType)
    {
        $entityInfo = $this->_getAssociatedEntityInfo($entityType);
        $select = $this->getConnection()->select()->from(
            $this->getTable($entityInfo['associations_table']),
            [$entityInfo['entity_id_field']]
        )->where(
            $entityInfo['course_id_field'] . ' = ?',
            $id
        )->where(
            $entityInfo['is_addition_field'] . '= ?',
            false
        )->where(
            $entityInfo['profile_only_field'] . '= ?',
            false
        );
        return $this->getConnection()->fetchCol($select);
    }

    public function getAssociatedAdditionalCategoryIdsEntity($id, $entityType)
    {
        $entityInfo = $this->_getAssociatedEntityInfo($entityType);
        $select = $this->getConnection()->select()->from(
            $this->getTable($entityInfo['associations_table']),
            [$entityInfo['entity_id_field']]
        )->where(
            $entityInfo['course_id_field'] . ' = ?',
            $id
        )->where(
            $entityInfo['is_addition_field'] . '= ?',
            true
        );
        return $this->getConnection()->fetchCol($select);
    }

    public function getAssociatedProfileCategoryIdsEntity($id, $entityType)
    {
        $entityInfo = $this->_getAssociatedEntityInfo($entityType);
        $select = $this->getConnection()->select()->from(
            $this->getTable($entityInfo['associations_table']),
            [$entityInfo['entity_id_field']]
        )->where(
            $entityInfo['course_id_field'] . ' = ?',
            $id
        )->where(
            $entityInfo['profile_only_field'] . '= ?',
            true
        );
        return $this->getConnection()->fetchCol($select);
    }

    public function getAssociatedProductIds($id, $entityType)
    {
        $entityInfo = $this->_getAssociatedEntityInfo($entityType);
        $select = $this->getConnection()->select()->from(
            $this->getTable($entityInfo['associations_table']),
            [$entityInfo['entity_id_field'], $entityInfo['priority']]
        )->where(
            $entityInfo['course_id_field'] . ' = ?',
            $id
        );
        return $this->getConnection()->fetchAll($select);
    }

    protected function _getAssociatedEntityInfo($entityType)
    {
        if (isset($this->_associatedEntitiesMap[$entityType])) {
            return $this->_associatedEntitiesMap[$entityType];
        }

        throw new \Magento\Framework\Exception\LocalizedException(
            __('There is no information about associated entity type "%1".', $entityType)
        );
    }

    /**
     * @return string
     */
    public function getHanpukaiFixedTable(){
        if(!$this->_hanpukaiFixedTable){
            $this->_hanpukaiFixedTable = $this->getTable('hanpukai_fixed');
        }
        return $this->_hanpukaiFixedTable;
    }

    /**
     * @return string
     */
    public function getHanpukaiSequenceTable(){
        if(!$this->_hanpukaiSequenceTable){
            $this->_hanpukaiSequenceTable = $this->getTable('hanpukai_sequence');
        }
        return $this->_hanpukaiSequenceTable;
    }

    /**
     * @return string
     */
    public function getSubscriptionMachineTable(){
        if(!$this->_subscriptionMachineTable){
            $this->_subscriptionMachineTable = $this->getTable('subscription_machine');
        }
        return $this->_subscriptionMachineTable;
    }

    /**
     * @return string
     */
    public function getSubscriptionMachineTypeLinkTable(){
        if(!$this->_subscriptionMachineTypeLinkTable){
            $this->_subscriptionMachineTypeLinkTable = $this->getTable('subscription_course_machine_type_link');
        }
        return $this->_subscriptionMachineTypeLinkTable;
    }


    protected function _afterLoad(AbstractModel $object)
    {
        $object->setData('frequency_ids', (array)$this->getFrequencyIds($object->getId()));
        $object->setData('website_ids', (array)$this->getWebsiteIds($object->getId()));
        $object->setData('category_ids', (array)$this->getCategoryIds($object->getId()));
        $object->setData('profile_category_ids', (array)$this->getProfileCategoryIds($object->getId()));
        $object->setData('additional_category_ids', (array)$this->getAdditionalCategoryIds($object->getId()));
        $object->setData('payment_ids', (array)$this->getPaymentIds($object->getId()));
        $membership = (array)$this->getMembershipIds($object->getId());
        if (count($membership) > 0) {
            if ($membership[0] == 0) {
                $object->setData('membership_ids', array());
            } else {
                $object->setData('membership_ids', (array)$this->getMembershipIds($object->getId()));
            }
        }
        $object->setData('merge_profile_to', (array)$this->getMergeProfileTo($object->getId()));
        $object->setData('multi_machine', (array)$this->getMultiMachine($object->getId()));

        return parent::_afterLoad($object);
    }

    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $object->setUpdatedDate($this->dateTime->gmtDate('Y-m-d H:i:s'));
        return parent::_beforeSave($object);
    }

    protected function _afterSave(AbstractModel $object)
    {
        if ($object->hasWebsiteIds()) {
            $websiteIds = $object->getWebsiteIds();
            if (!is_array($websiteIds)) {
                $websiteIds = explode(',', (string)$websiteIds);
            }
            $this->bindCourseToEntity($object->getId(), $websiteIds, 'website');
        }
        if ($object->hasFrequencyIds()) {
            $frequencyIds = $object->getFrequencyIds();
            if (!is_array($frequencyIds)) {
                $frequencyIds = explode(',', (string)$frequencyIds);
            }
            $this->bindCourseToEntity($object->getId(), $frequencyIds, 'frequency');
        }
        if(!$object->hasIsUpdateStatus()) {
            if ($object->hasCategoryIds()) {
                $categoryIds = $object->getCategoryIds();
                if (!is_array($categoryIds)) {
                    $categoryIds = explode(',', (string)$categoryIds);
                }
                $this->bindCourseToEntity($object->getId(), $categoryIds, 'category');
            } else {
                $this->deleteCourseCategory($object->getId());
            }
            if ($object->hasAdditionalCategoryIds()) {
                $additionalCategoryIds = $object->getAdditionalCategoryIds();
                if (!is_array($additionalCategoryIds)) {
                    $additionalCategoryIds = explode(',', (string)$additionalCategoryIds);
                }
                $this->bindCourseToEntity($object->getId(), $additionalCategoryIds, 'category', true);
            } else {
                $this->deleteCourseCategory($object->getId(), true);
            }

            if ($object->hasProfileCategoryIds()) {
                $profileCategoryIds = $object->getProfileCategoryIds();
                if (!is_array($profileCategoryIds)) {
                    $profileCategoryIds = explode(',', (string)$profileCategoryIds);
                }
                $this->bindCourseToEntity($object->getId(), $profileCategoryIds, 'category', false,true);
            } else {
                $this->deleteCourseCategory($object->getId(), false,true);
            }

            if ($object->hasMergeProfileTo()) {
                $mergeProfileTo = $object->getMergeProfileTo();
                if (!is_array($mergeProfileTo)) {
                    $mergeProfileTo = explode(',', (string)$mergeProfileTo);
                }
                $this->bindCourseToEntity($object->getId(), $mergeProfileTo, 'merge_profile');
            } else {
                $this->deleteMergeProfileTo($object->getId());
            }
        }
        if ($object->hasPaymentIds()) {
            $paymentIds = $object->getPaymentIds();
            if (!is_array($paymentIds)) {
                $paymentIds = explode(',', (string)$paymentIds);
            }
            $this->bindCourseToEntity($object->getId(), $paymentIds, 'payment');
        }

        if ($object->hasProducts()) {
            $productIds = $object->getProducts();
            $this->bindCourseToProduct($object, $productIds);
        }
        if(!$object->hasIsUpdateStatus()) {
            $membershipIds = $object->getMembershipIds();
            if (!is_array($membershipIds)) {
                $membershipIds = explode(',', (string)$membershipIds);
            }
            $this->bindCourseToEntity($object->getId(), $membershipIds, 'membership');
        }
        if ($object->hasMachines()) {
            $productIds = $object->getMachines();
            $this->bindCourseToMachine($object, $productIds);
            $this->renderCatalogRule($object, $productIds);
        }
        if ($object->getSubscriptionType() == Type::TYPE_MULTI_MACHINES) {
            $types = $object->getMultiMachine();
            $this->bindCourseToMachine($object, $types);
            $machineTypeModel = $this->machineTypeFactory->create();
            $listMachines = [];
            foreach ($types as $type) {
                $machines = $machineTypeModel->getResource()->getMachinesByType($type);
                if (!$machines) {
                    continue;
                }
                foreach ($machines as $machine) {
                    $key = $machine['product_id'];
                    if (array_key_exists($key, $listMachines)) {
                        $currentDiscount = $listMachines[$key]['discount_percent'];
                        if ($currentDiscount < $machine['discount_percent']) {
                            $listMachines[$key] = $machine;
                        }
                    } else {
                        $listMachines[$key] = $machine;
                    }
                }
            }
            $this->renderCatalogRuleForMachineType($object, $listMachines);
        }

        // Update catalog rule
        if($object->hasDataChanges()){
            $this->updateCatalogRule($object);
        }

        parent::_afterSave($object);
        return $this;
    }

    /**
     * Render catalog rule
     *
     * @param \Riki\SubscriptionCourse\Model\Course $course
     * @param array $productsData
     */
    public function renderCatalogRule($course, $productsData)
    {
        if ($course->getName()) {
            foreach ($productsData as $productId => $machine) {
                $product = $this->_productFactory->create()->load($productId);
                $data = [
                    'name' => 'Machine - ' . $course->getName(),
                    'is_active' => 1,
                    'website_ids' => $course->getAvailableWebsites(),
                    'customer_group_ids' => $this->getCustomerGroupIds(),
                    'subscription' => 2, // sub only
                    'apply_subscription_course_and_frequency' => [$course->getId() => $course->getFrequencies()],
                    'subscription_delivery' => 3, // default
                    'simple_action' => 'by_percent',
                    'is_machine' => 1,
                    'machine_id' => $productId,
                    'conditions' => [
                        '1' => [
                            'type' => 'Magento\CatalogRule\Model\Rule\Condition\Combine',
                            'aggregator' => 'all',
                            'value' => '1',
                            'new_child' => ''
                        ],
                        '1--1' => [
                            'type' => 'Magento\CatalogRule\Model\Rule\Condition\Product',
                            'attribute' => 'sku',
                            'operator' => '==',
                            'value' => $product->getSku()
                        ]
                    ]
                ];
                if (isset($machine['discount_amount']) && $machine['discount_amount']) {
                    $data['discount_amount'] = $machine['discount_amount'];
                }
                if (isset($machine['is_free']) && $machine['is_free'] == 1) {
                    $data['discount_amount'] = 100;
                }
                if (isset($machine['wbs']) && $machine['wbs']) {
                    $data['machine_wbs'] = $machine['wbs'];
                }

                $ruleModel = $this->_ruleModelFactory->create();

                $ruleId = $ruleModel->getResource()->getMachineCatalogRule($course->getId(), $productId);
                if ($ruleId) {
                    $data['rule_id'] = $ruleId;
                }

                $ruleModel->loadPost($data);
                $ruleModel->save();
                $this->_ruleProductProcessor->markIndexerAsInvalid();
                //$this->_productRuleProcessor->markIndexerAsInvalid();
            }
        }
    }

    /**
     * Render catalog rule for machine type
     *
     * @param \Riki\SubscriptionCourse\Model\Course $course
     * @param array $productsData
     */
    public function renderCatalogRuleForMachineType($course, $productsData)
    {
        if ($course->getName()) {
            foreach ($productsData as $key => $machine) {
                $productId = explode('-', $key)[0];
                $product = $this->_productFactory->create()->load($productId);
                $data = [
                    'name' => 'Machine - ' . $course->getName(),
                    'is_active' => 1,
                    'website_ids' => $course->getAvailableWebsites(),
                    'customer_group_ids' => $this->getCustomerGroupIds(),
                    'subscription' => 2, // sub only
                    'apply_subscription_course_and_frequency' => [$course->getId() => $course->getFrequencies()],
                    'subscription_delivery' => 3, // default
                    'simple_action' => 'by_percent',
                    'is_machine' => 1,
                    'machine_id' => $productId,
                    'conditions' => [
                        '1' => [
                            'type' => 'Magento\CatalogRule\Model\Rule\Condition\Combine',
                            'aggregator' => 'all',
                            'value' => '1',
                            'new_child' => ''
                        ],
                        '1--1' => [
                            'type' => 'Magento\CatalogRule\Model\Rule\Condition\Product',
                            'attribute' => 'sku',
                            'operator' => '==',
                            'value' => $product->getSku()
                        ]
                    ]
                ];
                if (isset($machine['discount_percent']) && $machine['discount_percent']) {
                    $data['discount_amount'] = $machine['discount_percent'];
                }
                if (isset($machine['is_free']) && $machine['is_free'] == 1) {
                    $data['discount_amount'] = 100;
                }
                if (isset($machine['wbs']) && $machine['wbs']) {
                    $data['machine_wbs'] = $machine['wbs'];
                }

                $ruleModel = $this->_ruleModelFactory->create();

                $ruleId = $ruleModel->getResource()->getMachineCatalogRule($course->getId(), $productId);
                if ($ruleId) {
                    $data['rule_id'] = $ruleId;
                }

                $ruleModel->loadPost($data);
                $ruleModel->save();
                $this->_ruleProductProcessor->markIndexerAsInvalid();
                //$this->_productRuleProcessor->markIndexerAsInvalid();
            }
        }
    }

    /**
     * Update catalog rule
     *
     * @param \Riki\SubscriptionCourse\Model\Course $course
     */
    public function updateCatalogRule($course)
    {
        $machines = $course->getProductMachines();
        if ($machines) {
            $machineIds = [];
            foreach ($machines as $machine) {
                $machineIds[$machine['product_id']] = $machine;
            }
            $this->renderCatalogRule($course, $machineIds);
        }
    }

    /**
     * Get all customer group ids
     *
     * @return array
     */
    public function getCustomerGroupIds()
    {
        $groupCustomerIds = [];
        $groupCustomers = $this->_customerGroup->toOptionArray();
        foreach ($groupCustomers as $option) {
            $groupCustomerIds[] = $option['value'];
        }
        return $groupCustomerIds;
    }

    public function bindCourseToMachine($course, $productsData)
    {
        $this->getConnection()->beginTransaction();
        if ($course->getSubscriptionType() == Type::TYPE_MULTI_MACHINES) {
            $table = $this->getSubscriptionMachineTypeLinkTable();
        } else {
            $table = $this->getSubscriptionMachineTable();
        }

        try {
            if ($course->getSubscriptionType() == Type::TYPE_MULTI_MACHINES) {
                $this->_multiplyBunchInsertMachineType($course, $productsData, $table, null);
            } else {
                $this->_multiplyBunchInsertMachine($course, $productsData, $table,
                    ['is_free', 'discount_amount', 'wbs', 'sort_order']);
            }
            $this->getConnection()->commit();

        } catch (\Exception $e) {
            $this->getConnection()->rollback();
            throw $e;
        }

        return $this;
    }

    public function _multiplyBunchInsertMachine($course, $productsData, $table, $columns)
    {
        $ruleModelResource = $this->_ruleModelFactory->create()->getResource();
        $courseId = $course->getId();

        $oldMachines = $this->getOldMachineIds($courseId);

        $insert = array_diff_key($productsData, $oldMachines);
        $delete = array_diff_key($oldMachines, $productsData);

        $update = array_intersect_key($productsData, $oldMachines);

        $connection = $this->getConnection();

        /**
         * Delete machines from course
         */
        if (!empty($delete)) {
            $cond = ['product_id IN(?)' => array_keys($delete), 'course_id=?' => $courseId];
            $connection->delete($table, $cond);

            // delete machine catalogrules
            $productIds = array_keys($delete);
            $ruleIds = [];
            foreach ($productIds as $productId) {
                if ($ruleId = $ruleModelResource->getMachineCatalogRule($courseId, $productId)) {
                    $ruleIds[] = $ruleId;
                }
            }
            if ($ruleIds) {
                $ruleModelResource->removeRule($ruleIds);
            }
        }

        /**
         * Add machines to course
         */
        if (!empty($insert)) {
            $data = [];
            foreach ($insert as $productId => $postData) {
                $rowData = [
                    'course_id' => (int)$courseId,
                    'product_id' => (int)$productId
                ];

                foreach($columns as $column){
                    if (isset($postData[$column])) {
                        $rowData[$column] = $postData[$column];
                    }
                }
                // reset discount_amount for is_free machine
                if (isset($postData['is_free']) && $postData['is_free'] == 1) {
                    $rowData['discount_amount'] = 100;
                }
                $data[] = $rowData;
            }
            $connection->insertMultiple($table, $data);
        }

        /**
         * Update product data in course
         */
        if (!empty($update)) {
            foreach ($update as $productId => $postData) {
                $where = ['course_id = ?' => (int)$courseId, 'product_id = ?' => (int)$productId];
                $bind = [];
                foreach($columns as $column){
                    if (isset($postData[$column])) {
                        $bind[$column] = $postData[$column];
                    }
                }
                // reset discount_amount for is_free machine
                if (isset($postData['is_free']) && $postData['is_free'] == 1) {
                    $bind['discount_amount'] = 100;
                }
                if ($bind) {
                    $connection->update($table, $bind, $where);
                }
            }
        }

        return $this;
    }

    public function _multiplyBunchInsertMachineType($course, $machineList, $table, $columns=null)
    {
        $courseId = $course->getId();

        $oldMachines = $this->getOldMachineTypesIds($courseId);

        $insert = array_diff($machineList, array_keys($oldMachines));
        $delete = array_diff(array_keys($oldMachines), $machineList);

        $connection = $this->getConnection();
        /**
         * Delete machines from course
         */
        if (!empty($delete)) {
            $cond = ['machine_type_id IN(?)' => array_values($delete), 'course_id=?' => $courseId];
            $connection->delete($table, $cond);
        }

        /**
         * Add machines to course
         */
        if (!empty($insert)) {
            $data = [];
            foreach ($insert as $machineTypeId) {
                $rowData = [
                    'course_id' => (int)$courseId,
                    'machine_type_id' => (int)$machineTypeId
                ];
                $data[] = $rowData;
            }
            $connection->insertMultiple($table, $data);
        }

        return $this;
    }

    public function getOldMachineIds($courseId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getSubscriptionMachineTable(),
            ['product_id', 'course_id']
        )->where(
            'course_id = :course_id'
        );
        $bind = ['course_id' => (int)$courseId];

        return $this->getConnection()->fetchPairs($select, $bind);
    }

    public function getOldMachineTypesIds($courseId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getSubscriptionMachineTypeLinkTable(),
            ['machine_type_id', 'course_id']
        )->where(
            'course_id = :course_id'
        );
        $bind = ['course_id' => (int)$courseId];

        return $this->getConnection()->fetchPairs($select, $bind);
    }

    public function bindCourseToEntity($courseIds, $entityIds, $entityType, $isAdditionalCategory = false,$profileOnly = false)
    {
        $this->getConnection()->beginTransaction();

        try {
            $this->_multiplyBunchInsert($courseIds, $entityIds, $entityType, $isAdditionalCategory,$profileOnly);
        } catch (\Exception $e) {
            $this->getConnection()->rollback();
            throw $e;
        }

        $this->getConnection()->commit();

        return $this;
    }

    protected function _multiplyBunchInsert($ruleIds, $entityIds, $entityType, $isAdditionalCategory = false,$profileOnly = false)
    {
        if (empty($ruleIds) || empty($entityIds)) {
            return $this;
        }
        if (!is_array($ruleIds)) {
            $ruleIds = [(int)$ruleIds];
        }
        if (!is_array($entityIds)) {
            $entityIds = [(int)$entityIds];
        }
        $data = [];
        $count = 0;
        $entityInfo = $this->_getAssociatedEntityInfo($entityType);
        foreach ($ruleIds as $ruleId) {
            foreach ($entityIds as $entityId) {
                if ($entityType == 'category') {
                    $data[] = [
                        $entityInfo['entity_id_field'] => $entityId,
                        $entityInfo['course_id_field'] => $ruleId,
                        $entityInfo['is_addition_field'] => $isAdditionalCategory,
                        $entityInfo['profile_only_field'] => $profileOnly
                    ];
                } else {
                    $data[] = [
                        $entityInfo['entity_id_field'] => $entityId,
                        $entityInfo['course_id_field'] => $ruleId,
                    ];
                }
                $count++;
                if ($count % 1000 == 0) {
                    $this->getConnection()->insertOnDuplicate(
                        $this->getTable($entityInfo['associations_table']),
                        $data,
                        [$entityInfo['course_id_field']]
                    );
                    $data = [];
                }
            }
        }
        if (!empty($data)) {
            $this->getConnection()->insertOnDuplicate(
                $this->getTable($entityInfo['associations_table']),
                $data,
                [$entityInfo['course_id_field']]
            );
        }

        if ($this->getTable($entityInfo['associations_table']) == 'subscription_course_category') {
            $this->getConnection()->delete(
                $this->getTable($entityInfo['associations_table']),
                $this->getConnection()->quoteInto(
                    $entityInfo['course_id_field'] . ' IN (?) AND ',
                    $ruleIds
                ) . $this->getConnection()->quoteInto(
                    $entityInfo['entity_id_field'] . ' NOT IN (?) AND ',
                    $entityIds
                ) . $this->getConnection()->quoteInto(
                    $entityInfo['is_addition_field']. ' IN (?) AND ',
                    $isAdditionalCategory
                ). $this->getConnection()->quoteInto(
                    $entityInfo['profile_only_field']. ' IN (?) ',
                    $profileOnly
                )
            );
        } else {
            $this->getConnection()->delete(
                $this->getTable($entityInfo['associations_table']),
                $this->getConnection()->quoteInto(
                    $entityInfo['course_id_field'] . ' IN (?) AND ',
                    $ruleIds
                ) . $this->getConnection()->quoteInto(
                    $entityInfo['entity_id_field'] . ' NOT IN (?)',
                    $entityIds
                )
            );
        }
        return $this;
    }

    public function getAllFrequencies()
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('subscription_frequency'))
            ->order('position ASC');

        return $connection->fetchAll($select);
    }

    public function getFrequencyEntities($courseId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['sf' => $this->getTable('subscription_frequency')])
            ->joinRight(
                ['scf' => $this->getTable('subscription_course_frequency')],
                'scf.frequency_id = sf.frequency_id',
                []
            )
            ->where('scf.course_id = ?', $courseId)
            ->order('position');

        return $connection->fetchAll($select);
    }

    protected function _afterDelete(\Magento\Framework\Model\AbstractModel $course)
    {
        $connection = $this->getConnection();
        $connection->delete(
            $this->getTable('subscription_course_frequency'),
            ['course_id=?' => $course->getId()]
        );
        $connection->delete(
            $this->getTable('subscription_course_website'),
            ['course_id=?' => $course->getId()]
        );
        $connection->delete(
            $this->getTable('subscription_course_category'),
            ['course_id=?' => $course->getId()]
        );
        $connection->delete(
            $this->getTable('subscription_course_payment'),
            ['course_id=?' => $course->getId()]
        );
        $connection->delete(
            $this->getTable('subscription_course_membership'),
            ['course_id=?' => $course->getId()]
        );
        $connection->delete(
            $this->getTable('subscription_course_merge_profile'),
            ['course_id=?' => $course->getId()]
        );
        return parent::_afterDelete($course);
    }

    /**
     *
     *
     * @param $course
     * @param $productsData
     * @return $this
     * @throws \Exception
     */
    public function bindCourseToProduct($course, $productsData)
    {
        $this->getConnection()->beginTransaction();
        try {

            if($course->getSubscriptionType() == SubscriptionType::TYPE_HANPUKAI){
                switch($course->getHanpukaiType()){
                    case SubscriptionType::TYPE_HANPUKAI_FIXED:
                        $this->_multiplyBunchInsertProduct($course, $productsData);
                        $this->_multiplyBunchInsertHanpukaiFixedProduct($course, $productsData);
                        break;
                    case SubscriptionType::TYPE_HANPUKAI_SEQUENCE:
                        $this->_multiplyBunchInsertProduct($course, $productsData);
                        $this->_multiplyBunchInsertHanpukaiSequenceProduct($course, $productsData);
                        break;
                    default:
                        throw new \Magento\Framework\Exception\LocalizedException(__('The subscription can not be saved'));
                }
            }else{
                $this->_multiplyBunchInsertProduct($course, $productsData);
            }
        } catch (\Exception $e) {
            $this->getConnection()->rollback();
            throw $e;
        }
        $this->getConnection()->commit();
        return $this;
    }

    public function _multiplyBunchInsertProduct($course, $entityIds) {

        $newProductIds = [];
        $positionNewProduct = [];

        foreach ($entityIds as $key => $productNew) {
            $newProductIds[] = $key;
            $positionNewProduct[$key] = isset($productNew['position']) ? $productNew['position'] : 0;
        }

        return $this;
    }

    /**
     * insert/update/delete product for hanpukai subscription
     *
     * @param $course
     * @param array $postProducts
     * @param $table
     * @param array $columns
     * @return $this
     */
    protected function _multiplyBunchInsertHanpukaiProduct($course, $postProducts, $table, $columns) {

        $id = $course->getId();

        $oldProducts = $this->getOldHanpukaiProductIdsByTable($course, $table);

        $insert = array_diff_key($postProducts, $oldProducts);
        $delete = array_diff_key($oldProducts, $postProducts);

        $update = array_intersect_key($postProducts, $oldProducts);

        $connection = $this->getConnection();

        /**
         * Delete products from course
         */
        if (!empty($delete)) {
            $cond = ['product_id IN(?)' => array_keys($delete), 'course_id=?' => $id];
            $connection->delete($table, $cond);
        }

        /**
         * Add products to course
         */
        if (!empty($insert)) {
            $data = [];
            foreach ($insert as $productId => $postData) {
                $rowData = [
                    'course_id' => (int)$id,
                    'product_id' => (int)$productId
                ];

                foreach($columns as $column){
                    $rowData[$column] = $postData[$column];
                }

                $data[] = $rowData;
            }
            $connection->insertMultiple($table, $data);
        }

        /**
         * Update product data in course
         */
        if (!empty($update)) {
            foreach ($update as $productId => $postData) {
                $where = ['course_id = ?' => (int)$id, 'product_id = ?' => (int)$productId];
                $bind = [];
                foreach($columns as $column){
                    $bind[$column] = $postData[$column];
                }

                $connection->update($table, $bind, $where);
            }
        }

        return $this;
    }

    /**
     * insert/update/delete product for hanpukai fixed subscription
     *
     * @param $course
     * @param $postProducts
     * @return Course
     */
    protected function _multiplyBunchInsertHanpukaiFixedProduct($course, $postProducts) {

        $postProducts = array_map(function($postProduct){
            return [
                'qty'   =>  (int)$postProduct['qty'] <= 0? 1: (int)$postProduct['qty'],
                'unit_case'   =>  isset($postProduct['unit_case'])? $postProduct['unit_case']: 'EA',
                'unit_qty'   =>  isset($postProduct['unit_qty'])? $postProduct['unit_qty']: 1
            ];
        }, $postProducts);

        return $this->_multiplyBunchInsertHanpukaiProduct($course, $postProducts, $this->getHanpukaiFixedTable(), ['qty','unit_case','unit_qty']);
    }

    /**
     * insert/update/delete product for hanpukai sequence subscription
     *
     * @param $course
     * @param $postProducts
     * @return Course
     */
    protected function _multiplyBunchInsertHanpukaiSequenceProduct($course, $postProducts) {

        $postProducts = array_map(function($postProduct){
            return [
                'qty'   =>  (int)$postProduct['qty'] <= 0? 1: (int)$postProduct['qty'],
                'delivery_number'   =>  (int)$postProduct['delivery_number'] <= 0? 1: (int)$postProduct['delivery_number'],
                'unit_case'   =>  isset($postProduct['unit_case'])? $postProduct['unit_case']: 'EA',
                'unit_qty'   =>  isset($postProduct['unit_qty'])? $postProduct['unit_qty']: 1
            ];
        }, $postProducts);

        return $this->_multiplyBunchInsertHanpukaiProduct($course, $postProducts, $this->getHanpukaiSequenceTable(), ['delivery_number', 'qty','unit_case','unit_qty']);
    }


    public function deleteCourseCategory($categoryId = 0, $isAddition = false,$profileOnly = false) {
        $connection = $this->getConnection();
        $connection->delete(
            $this->getTable('subscription_course_category'),
            [
                'course_id=?' => $categoryId,
                'is_addition=?' => $isAddition,
                'profile_only=?' => $profileOnly
            ]
        );
        return $this;
    }
    public function deleteMergeProfileTo($categoryId = 0, $isAddition = false) {
        $connection = $this->getConnection();
        $connection->delete(
            $this->getTable('subscription_course_merge_profile'),
            [
                'course_id=?' => $categoryId
            ]
        );
        $adminUser = $this->authSession->getUser();
        $e = new \Exception();
        if ($adminUser)
        {
            $adminUsername = $adminUser->getUserName();
            $this->_logger->info("NED-3462 : Trace : " . $e->getTraceAsString());
            $this->_logger->info('Delete MergeProfileTo of Course ID '.$categoryId.' by Admin User '.$adminUsername);
        } else
        {
            $this->_logger->info("NED-3462 : Trace : " . $e->getTraceAsString());
            $this->_logger->info('Delete MergeProfileTo of Course ID '.$categoryId);
        }
        return $this;
    }


    public function getAllCourses()
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('subscription_course'));

        return $connection->fetchAll($select);
    }

    /**
     *  check course's product is match the promotion rule
     */
    public function getProductCourseByRule($item, $rule, $course, $frequency)
    {
        $courseId = $this->_salesruleResourceModel->getSubscriptionRule($rule, $course, $frequency);
        if ($courseId) {
            $connection = $this->getConnection();
            $select = $connection->select()
                ->from(['c' => $this->getTable('subscription_course')], ['COUNT(c.course_id)'])
                ->where('c.course_id = ?', $courseId);

            return $connection->fetchOne($select);
        }

        return 0;
    }

    /**
     *  check course's category product is match the promotion rule
     */
    public function getCategoryProductCourseByRule($item, $rule, $course, $frequency)
    {
        $courseId = $this->_salesruleResourceModel->getSubscriptionRule($rule, $course, $frequency);
        $catIds = $this->_salesruleResourceModel->getCategoryProductRule($item);
        if ($courseId && $catIds) {
            $cacheKey = [$courseId, implode('_', $catIds)];
            if ($this->functionCache->has($cacheKey)) {
                return $this->functionCache->load($cacheKey);
            }
            $connection = $this->getConnection();
            $select = $connection->select()
                ->from(['c' => $this->getTable('subscription_course')], ['COUNT(c.course_id)'])
                ->joinLeft(['cat' => 'subscription_course_category'], 'c.course_id = cat.course_id', [])
                ->where('c.course_id = ?', $course)
                ->where('cat.category_id IN (?)', $catIds);

            $result = $connection->fetchOne($select);
            $this->functionCache->store($result, $cacheKey);

            return $result;
        }

        return 0;
    }
    /**
     * Get all course of a customer
     *
     * @param $customerId
     * @param $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCoursesByMembership($customerId,$storeId){
        $store = $this->_storeManager;
        $websiteId = $store->getStore($storeId)->getWebsiteId();
        $customer = $this->_customer->load($customerId);
        $membershipIds = $customer->getMembership();

        $courseIds = [];
        if( ! empty($membershipIds)) {
            $entityInfo = $this->_getAssociatedEntityInfo('membership');
            $select = $this->getConnection()->select()->from(
                $this->getTable($entityInfo['associations_table']),
                [$entityInfo['course_id_field']]
            )->where(
                $entityInfo['entity_id_field'] . ' in ('.$membershipIds.')'
            )->distinct(true);
            $courseIds = $this->getConnection()->fetchCol($select);
        }

        $courseNoConfigMembership = [];
        if( ! empty($membershipIds)) {
            $entityInfo = $this->_getAssociatedEntityInfo('membership');
            $select = $this->getConnection()->select()->from(
                $this->getTable($entityInfo['associations_table']),
                [$entityInfo['course_id_field']]
            )->where(
                $entityInfo['entity_id_field'] . ' = 0'
            )->distinct(true);
            $courseNoConfigMembership = $this->getConnection()->fetchCol($select);
        }
        $courseIds = array_merge($courseIds, $courseNoConfigMembership);
        $courseModel = $this->_courseFactory->create()->getCollection();
        $courseModel->getSelect()->join(array('cw'=>'subscription_course_website'),'main_table.course_id = cw.course_id',array('*'));
        if(empty($courseIds)) {
            $courseModel->addFieldToFilter('main_table.course_id','EMPTY'); /* Make it cannot happen. */
        }
        else {
            $courseModel->addFieldToFilter('main_table.course_id',$courseIds);
        }

        $courseModel->addFieldToFilter('cw.website_id',$websiteId);
        $courseModel->addFieldToFilter('main_table.visibility',array(2,3));
        $courseModel->addFieldToFilter('main_table.is_enable',1);
        return $courseModel;
    }

    /**
     * Get all product in a course by course_id
     * $webApi != null use call web api get product subscription
     *
     * @param $courseId
     * @param $storeId
     * @param null $webApi
     * @return $this|bool|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllProductByCourse($courseId,$storeId,$webApi=null,$isAdditional=false){
        $course = $this->_courseFactory->create()->load($courseId);
        $products = [];
        $productHanpukais = [];

        if($course->getSubscriptionType() == SubscriptionType::TYPE_HANPUKAI){
            return $this->getAllProductByCourseForHanpukai($course, $storeId);
        }else {
            if($isAdditional){
                $categoryIds = $this->getAdditionalCategoryIds($courseId);
            }
            else{
                $categoryIds = $this->getCategoryIds($courseId);
                $profileCategoryIds = $this->getProfileCategoryIds($courseId);
                if(sizeof($profileCategoryIds) > 0) {
                    $categoryIds = array_merge($categoryIds,$profileCategoryIds);
                }
            }
            if(!empty($categoryIds)) {
                $filter = $this->searchCriteriaBuilder
                    ->addFilter('store_id', $storeId)
                    ->addFilter('category_id', $categoryIds,'in')
                    ->addFilter('available_subscription',1)
                    ->create();
                $productRepository = $this->productRepository->getList($filter);

                //get all product by category in course
                if($this->appState->getAreaCode() == 'adminhtml'){
                    foreach ($productRepository->getItems() as $item) {
                        $products[] = $item->getId();
                    }
                }else{
                    foreach ($productRepository->getItems() as $item) {
                        if($webApi==null){
                            if ($this->checkProductAvailableForShow($item, $storeId)) {
                                $products[] = $item->getId();
                            }
                        }else{
                            $products[] = $item->getId();
                        }
                    }
                }
            }

            $productModel = $this->_productFactory->create()->getCollection()->addAttributeToSelect('*');
            if (sizeof($products) >=1) {
                $productModel->addAttributeToFilter('entity_id', ['in' => $products]);
                if($course->getSubscriptionType() == SubscriptionType::TYPE_HANPUKAI){
                    foreach ($productModel as $index => $product){
                        foreach ($productHanpukais as $id => $item){
                            if ($product->getId() == $id){
                                $product->setData('fix_qty',$item);
                            }
                        }
                    }
                }
                return $productModel;
            }
            return false;
            //all product of course
        }
    }

    /**
     * @param $course
     * @param $storeId
     * @return $this|bool|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllProductByCourseForHanpukai($course, $storeId){
        $hanpukaiData = null;
        switch($course->getHanpukaiType()){
            case SubscriptionType::TYPE_HANPUKAI_FIXED:
                $arrProductHanpukaiConfig = $this->getHanpukaiFixedProductsDataPieCase($course);
                $arrProductIdConfig = array_keys($arrProductHanpukaiConfig);
                $hanpukaiData  = $this->_productFactory->create()->getCollection()->addStoreFilter($storeId);
                $hanpukaiData->addAttributeToSelect('*');
                $hanpukaiData->addFieldToFilter('entity_id', ['in' => $arrProductIdConfig]);
                foreach ($hanpukaiData as $item) {
                    $addData = $arrProductHanpukaiConfig[$item->getId()];
                    $item->addData(array('fix_qty' => $addData['qty']));
                    $item->addData(array('unit_case' => $addData['unit_case']));
                    $item->addData(array('unit_qty' => $addData['unit_qty']));
                }
                break;
            case SubscriptionType::TYPE_HANPUKAI_SEQUENCE:
                $arrProductHanpukaiConfig = $this->getHanpukaiSequenceFirstDelivery($course);
                $arrProductIdConfig = array_keys($arrProductHanpukaiConfig);
                $hanpukaiData  = $this->_productFactory->create()->getCollection()->addStoreFilter($storeId);
                $hanpukaiData->addAttributeToSelect('*');
                $hanpukaiData->addFieldToFilter('entity_id',['in' => $arrProductIdConfig]);
                foreach ($hanpukaiData  as $item) {
                    $dataAdd = $arrProductHanpukaiConfig[$item->getId()];
                    $item->addData(array('fix_qty' => $dataAdd['qty']));
                    $item->addData(array('unit_case' => $dataAdd['unit_case']));
                    $item->addData(array('unit_qty' => $dataAdd['unit_qty']));
                }
                break;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(__('The subscription can not be saved'));
        }
        if($hanpukaiData->getItems()) {
            return $hanpukaiData;
        }else{
            return false;
        }
    }

    /**
     * @param $courseId
     * @param $storeId
     * @param null $webApi
     * @return bool|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllProductByCoursePieceCase($courseId,$storeId,$webApi=null,$isAdditional=false){
        $key = $courseId . '_' . $storeId . '_' . $webApi . '_' . $isAdditional;
        if (isset($this->productCollection[$key])) {
            return $this->productCollection[$key];
        }
        $course = $this->_courseFactory->create()->load($courseId);
        $products = [];
        $productHanpukais = [];

        if($course->getSubscriptionType() == SubscriptionType::TYPE_HANPUKAI){
            $this->productCollection[$key] = $this->getAllProductByCourseForHanpukai($course, $storeId);
            return $this->productCollection[$key];
        }else {
            if($isAdditional){
                $categoryIds = $this->getAdditionalCategoryIds($courseId);
            }else {
                $categoryIds = $this->getCategoryIds($courseId);
            }
            if(!empty($categoryIds)) {
                $filter = $this->searchCriteriaBuilder
                    ->addFilter('store_id', $storeId)
                    ->addFilter('category_id', $categoryIds,'in')
                    ->addFilter('available_subscription',1)
                    ->create();
                $productRepository = $this->productRepository->getList($filter);

                //get all product by category in course
                if($this->appState->getAreaCode() == 'adminhtml'){
                    foreach ($productRepository->getItems() as $item) {
                        if ($this->checkProductAvailableForShowForBackEnd($item, $storeId)) {
                            $products[] = $item->getId();
                        }
                    }
                }else{
                    foreach ($productRepository->getItems() as $item) {
                        if($webApi==null) {
                            if ($this->checkProductAvailableForShow($item, $storeId)) {
                                $products[] = $item->getId();
                            }
                        }else{
                            $products[] = $item->getId();
                        }
                    }
                }

            }
            $productModel = $this->_productFactory->create()->getCollection()->addAttributeToSelect('*');
            if (sizeof($products) >=1) {
                $productModel->addAttributeToFilter('entity_id', ['in' => $products]);
                if($course->getSubscriptionType() == SubscriptionType::TYPE_HANPUKAI){
                    foreach ($productModel as $index => $product){
                        foreach ($productHanpukais as $id => $item){
                            if ($product->getId() == $id){
                                $product->setData('fix_qty',$item);
                            }
                        }
                    }
                    $productModel->save();
                }
                return $productModel;
            }
            return false;
            //all product of course
        }
    }

    /**
     * @param $course
     * @param $storeId
     * @return $this|bool|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllProductByCoursePieceCaseForHanpukai($course, $storeId){
        $hanpukaiData = null;
        switch($course->getHanpukaiType()){
            case SubscriptionType::TYPE_HANPUKAI_FIXED:
                $arrProductHanpukaiConfig = $this->getHanpukaiFixedProductsDataPieCase($course);
                $arrProductIdConfig = array_keys($arrProductHanpukaiConfig);
                $hanpukaiData  = $this->_productFactory->create()->getCollection()->addAttributeToSelect('*')->addStoreFilter($storeId);
                $hanpukaiData->addFieldToFilter('entity_id', ['in' => $arrProductIdConfig]);
                foreach ($hanpukaiData as $item) {
                    $addData = $arrProductHanpukaiConfig[$item->getId()];
                    $item->addData(array('fix_qty' => $addData['qty']));
                    $item->addData(array('unit_case' => $addData['unit_case']));
                    $item->addData(array('unit_qty' => $addData['unit_qty']));
                }
                break;
            case SubscriptionType::TYPE_HANPUKAI_SEQUENCE:
                $arrProductHanpukaiConfig = $this->getHanpukaiSequenceFirstDelivery($course);
                $arrProductIdConfig = array_keys($arrProductHanpukaiConfig);
                $hanpukaiData  = $this->_productFactory->create()->getCollection()->addAttributeToSelect('*')->addStoreFilter($storeId);
                $hanpukaiData->addFieldToFilter('entity_id',['in' => $arrProductIdConfig]);
                foreach ($hanpukaiData  as $item) {
                    $addData = $arrProductHanpukaiConfig[$item->getId()];
                    $item->addData(array('fix_qty' => $addData['qty']));
                    $item->addData(array('unit_case'=> $addData['unit_case']));
                    $item->addData(array('unit_qty'=> $addData['unit_qty']));
                }
                break;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(__('The subscription can not be saved'));
        }

        if($hanpukaiData->getItems()) {
            return $hanpukaiData;
        }else{
            return false;
        }
    }

    /**
     * @param $course
     * @param $storeId
     *
     * @return array
     */
    public function getAllProductHanpukaiSequenceConfig($courseId, $storeId)
    {
        $hanpukaiData = null;
        $course = $this->_courseFactory->create()->load($courseId);
        $hanpukaiType = $course->getHanpukaiType();
        if ($hanpukaiType == SubscriptionType::TYPE_HANPUKAI_SEQUENCE) {
            $arrProductHanpukaiConfig = $this->getHanpukaiSequenceProductsData($course);;
            $arrProductIdConfig = array_keys($arrProductHanpukaiConfig);
            $hanpukaiData  = $this->_productFactory->create()->getCollection()->addAttributeToSelect('*')->addStoreFilter($storeId);
            $hanpukaiData->addFieldToFilter('entity_id',['in' => $arrProductIdConfig]);
            foreach ($hanpukaiData  as $item) {
                $addData = $arrProductHanpukaiConfig[$item->getId()];
                $item->addData(array('fix_qty' => $addData['qty']));
                $item->addData(array('unit_case'=> $addData['unit_case']));
                $item->addData(array('unit_qty'=> $addData['unit_qty']));
            }
        }
        return $hanpukaiData;
    }

    public function loadProductById($productId)
    {
        return $this->_productFactory->create()->load($productId);
    }

    /**
     * @param $product
     * @param $storeId
     * @return bool
     */
    public function checkProductAvailableForShowForBackEnd($product, $storeId)
    {
        $storeIdsOfProduct = $product->getStoreIds();
        if (!in_array($storeId, $storeIdsOfProduct)) {
            return false;
        }

        return true;
    }

    /**
     * @param $product
     * @param $storeId
     * @return bool
     */
    public function checkProductAvailableForShow($product, $storeId)
    {
        $storeIdsOfProduct = $product->getStoreIds();
        $productIsActiveInStore = false;
        if (in_array($storeId, $storeIdsOfProduct)) {
            $productIsActiveInStore = true;
        }

        if($product->getStatus() != 1)
            return false;
        if(!$productIsActiveInStore)
            return false;
        if(
            $this->appState->getAreaCode() != FrontNameResolver::AREA_CODE
            && $product->getVisibility() == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE
        )
            return false;

        return true;
    }


    /**
     *  Get Customers ordered subscription course profile
     */
    public function getCustomersFromSubscriptionProfile($courseId = 0)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['c' => $this->getTable('customer_entity')], ['DISTINCT(c.entity_id)','c.email'])
            ->joinLeft(['p' => 'subscription_profile'], 'c.entity_id = p.customer_id', [])
            ->where('p.course_id = ?', $courseId);
        return $connection->fetchAll($select);
    }

    public function getCustomerIdByCourse($courseId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from('subscription_profile', ['customer_id'])
            ->where('course_id = ?', $courseId);
        return $connection->fetchCol($select);
    }

    // set relationship entity
    public function getAssociatedEntity($id, $entityType, $data)
    {
        $entityInfo = $this->_getAssociatedEntityInfo($entityType);
        $select = $this->getConnection()->select()->from(
            $this->getTable($entityInfo['associations_table']),
            [$data]
        )->where(
            $entityInfo['course_id_field'] . ' = ?',
            $id
        );
        return $this->getConnection()->fetchAll($select);
    }

    /**
     * Avoid: Duplicate entry $key for key product_id after update
     * We need to remove the record which has the same $key + oldId
     * @param $key string the first key, second key is product_id
     * @param $table string the relationship table n-n
     * @param $oldId
     * @param $newId
     */
    protected function removeDuplicateProductWith($key, $table, $oldId, $newId)
    {
        $connection = $this->getConnection();
        $oldKeyIds = $connection->fetchCol(
            $connection->select()
                ->from($table, [$key])
                ->where('product_id=?', $oldId)
        );
        $newKeyIds = $connection->fetchCol(
            $connection->select()
                ->from($table, [$key])
                ->where('product_id=?', $newId)
        );
        $removeIds = array_intersect($oldKeyIds, $newKeyIds);
        if (sizeof($removeIds)) {
            $connection->delete(
                $table,
                [$key . ' IN (?)' => $removeIds, 'product_id = ?' => $oldId]
            );
            $this->_logger->info('Removed duplicate product key "'.$oldId.'" in table "'.$table.'".');
        }
    }

    public function replaceProduct($oldId, $newId)
    {
        $connection = $this->getConnection();

        /*hanpukai fixed table*/
        $hanpukaiFixedTable = $connection->getTableName(
            self::TABLE_HANPUKAI_PRODUCT_FIXED
        );

        /*hanpukai sequence table*/
        $hanpukaiSequenceTable = $connection->getTableName(
            self::TABLE_HANPUKAI_PRODUCT_SEQUENCE
        );

        /*list course id from hanpukai fixed table*/
        $courseIdFromHanpukaiFixedTable = $this->getProductCourseByTable($oldId, $hanpukaiFixedTable);

        /*list course id from hanpukai sequence table*/
        $courseIdFromHanpukaiSequenceTable = $this->getProductCourseByTable($oldId, $hanpukaiSequenceTable);

        $courseIds = array_unique( array_merge(
            $courseIdFromHanpukaiFixedTable,
            $courseIdFromHanpukaiSequenceTable
        ));

        if ($courseIds) {
            try {
                $connection->beginTransaction();

                /*replace process for hanpukai fixed table*/
                if (!empty($courseIdFromHanpukaiFixedTable)) {
                    /*remove duplicate*/
                    $this->removeDuplicateProductWith('course_id', $hanpukaiFixedTable, $oldId, $newId);

                    /*replace old product id by new product it*/
                    $connection->update( $hanpukaiFixedTable, ['product_id' => $newId], ['product_id=?' => $oldId]);
                }

                /*replace process for hanpukai sequence table*/
                if (!empty($courseIdFromHanpukaiSequenceTable)) {
                    /*remove duplicate*/
                    $this->removeDuplicateProductWith('course_id', $hanpukaiSequenceTable, $oldId, $newId);

                    /*replace old product id by new product it*/
                    $connection->update( $hanpukaiSequenceTable, ['product_id' => $newId], ['product_id=?' => $oldId]);
                }

                $connection->commit();
                $this->_logger->info('Updated course ids: "'.implode(', ', $courseIds).'".');
            } catch (\Exception $e) {
                $connection->rollBack();
                throw $e;
            }
        }

        return $this;
    }

    /**
     * Delete category product
     *
     * @param $productId
     * @return $this
     * @throws \Exception
     */
    public function deleteProductInCategory($productId)
    {
        $cateTable = $this->getTable('catalog_category_product');

        $connection = $this->_resources->getConnection(
            \Riki\Sales\Helper\ConnectionHelper::defaultAdapter
        );

        // select course will update
        $catIds = $connection->fetchCol(
            $connection->select()->from(
                $cateTable, ['category_id']
            )->where(
                'product_id=?', $productId
            )
        );

        if ($catIds) {
            try {

                $connection->beginTransaction();

                /* delete category product */
                $connection->delete( $cateTable, ['product_id = ?' => $productId] );

                $connection->commit();

                $this->_logger->info('Delete product id "'.$productId.'" from category ids: "'.implode(', ', $catIds).'".');
            } catch (\Exception $e) {
                $connection->rollBack();
                throw $e;
            }
        }

        return $this;
    }

    /**
     *
     *
     * @param $course
     * @param $table
     * @return array
     */
    public function getOldHanpukaiProductIdsByTable($course, $table){
        $select = $this->getConnection()->select()->from(
            $table,
            ['product_id', 'course_id']
        )->where(
            'course_id = :course_id'
        );
        $bind = ['course_id' => (int)$course->getId()];

        return $this->getConnection()->fetchPairs($select, $bind);
    }

    /**
     * Get first delivery product for hanpukai sequence
     *
     * @param $course
     */
    public function getHanpukaiSequenceFirstDelivery($course)
    {
        $arrHanpukaiSequenceConfig = $this->getHanpukaiSequenceProductsData($course);
        $firstDelivery = $this->getFirstDeliveryNumber($arrHanpukaiSequenceConfig);
        foreach ($arrHanpukaiSequenceConfig as $key => $value) {
            if ($value['delivery_number'] != $firstDelivery) {
                unset($arrHanpukaiSequenceConfig[$key]);
            }
        }
        return $arrHanpukaiSequenceConfig;
    }


    /**
     * GetFirstDeliveryNumber
     *
     * @param $arrProduct
     *
     * @return bool
     */
    public function getFirstDeliveryNumber($arrProduct)
    {
        $deliveryNumberArr = array();
        foreach ($arrProduct as $key => $value) {
            if(isset($value['delivery_number'])) {
                $deliveryNumberArr[] = $value['delivery_number'];
            }
        }
        $deliveryNumberArr = $this->sort($deliveryNumberArr, count($deliveryNumberArr));
        return isset($deliveryNumberArr[0])?$deliveryNumberArr[0]:false;
    }

    /**
     * Sort
     *
     * @param $arr
     * @param $length
     * @return mixed
     */
    public function sort($arr, $length)
    {
        for($i=0; $i < $length - 1; $i++) {
            for($j = $i+1 ; $j < $length; $j++) {
                if((int)$arr[$j] < (int)$arr[$i]) {
                    $tmp = $arr[$i];
                    $arr[$i] = $arr[$j];
                    $arr[$j] = $tmp;
                }
            }
        }
        return $arr;
    }
    /**
     * Get data of hanpukai sequence products
     *
     * @param \Riki\SubscriptionCourse\Model\Course $course
     * @return array
     */
    public function getHanpukaiSequenceProductsData($course)
    {
        $result = array();

        $select = $this->getConnection()->select()->from(
            $this->getTable('hanpukai_sequence'),
            ['product_id', 'delivery_number', 'qty', 'unit_case', 'unit_qty' ]
        )->where(
            'course_id = :course_id'
        );
        $bind = ['course_id' => (int)$course->getId()];

        $query = $this->getConnection()->query($select, $bind);

        while (true == ($row = $query->fetch())) {
            $result[$row['product_id']] = ['delivery_number'    =>  $row['delivery_number'], 'qty'  =>  $row['qty'] , 'unit_case'  =>  $row['unit_case'] , 'unit_qty'  =>  $row['unit_qty'] ];
        }

        return $result;
    }

    /**
     * Get data of hanpukai fixed products
     *
     * @param \Riki\SubscriptionCourse\Model\Course $course
     * @return array
     */
    public function getHanpukaiFixedProductsData($course)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('hanpukai_fixed'),
            ['product_id', 'qty']
        )->where(
            'course_id = :course_id'
        );
        $bind = ['course_id' => (int)$course->getId()];

        return $this->getConnection()->fetchPairs($select, $bind);
    }

    /**
     * @param $course
     * @return array|\Riki\MachineApi\Model\ResourceModel\B2CMachineSkus\Collection
     */
    public function getListMachineType($course)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('subscription_course_machine_type_link'),
            ['machine_type_id']
        )->where(
            'course_id = :course_id'
        );
        $bind = ['course_id' => (int)$course->getId()];

        $listMachineId = $this->getConnection()->fetchCol($select, $bind);
        if (!empty($listMachineId)) {
            return $this->machineTypeFactory->create()->getCollection()
                ->addFieldToFilter('type_id', ['in' =>$listMachineId])->load();
        }
        return [];
    }
    /**
     * Get data of hanpukai fixed products
     *
     * @param \Riki\SubscriptionCourse\Model\Course $course
     * @return array
     */
    public function getHanpukaiFixedProductsDataPieCase($course)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('hanpukai_fixed'),
            ['product_id', 'qty', 'unit_qty','unit_case']
        )->where(
            'course_id = :course_id'
        );

        $bind = ['course_id' => (int)$course->getId()];

        $query = $this->getConnection()->query($select, $bind);

        $result  = [];
        while (true == ($row = $query->fetch())) {
            $result[$row['product_id']] = ['qty'  =>  $row['qty'],'unit_qty'  =>  $row['unit_qty'],'unit_case'  =>  $row['unit_case'] ];
        }

        return $result;
    }

    public function getMachinesByCourse($courseId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('subscription_machine')
        )->where(
            'course_id = :course_id'
        )->order('sort_order ASC');
        $bind = ['course_id' => (int)$courseId];

        return $this->getConnection()->fetchAll($select, $bind);
    }

    public function getMachine($courseId, $productId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getTable('subscription_machine'))
            ->where('course_id = :course_id')
            ->where('product_id = :product_id');
        $bind = ['course_id' => (int)$courseId, 'product_id' => (int)$productId];

        return $this->getConnection()->fetchRow($select, $bind);
    }

    /**
     * Get course factory object
     *
     * @return CourseFactory
     */
    public function getCourseFactory()
    {
        return $this->_courseFactory;
    }

    /**
     * Get course code by sku
     *
     * @param $arrSku
     * @return array
     */
    public function getCourseCodeBySku($arrSku)
    {
        $coursesArr = $this->getCourseCodeByCategory();
        $coursesKeyArr = (count($coursesArr) > 0) ? array_keys($coursesArr) : null;
        $result = null;
        if (count($arrSku) > 0) {
            $select = $this->resourceConnection->getConnection()
                ->select()
                ->from(['pro_en' => 'catalog_product_entity'], ['entity_id'])
                ->join(['cate_pro' => 'catalog_category_product'], 'pro_en.entity_id = cate_pro.product_id', ['category_id'])
                ->where('pro_en.sku IN (?)', $arrSku);

            $data = $this->resourceConnection->getConnection()->fetchAll($select);
            if (count($data) > 0) {
                foreach ($data as $row) {
                    $categoryId = $row['category_id'];
                    if (in_array($categoryId, $coursesKeyArr)) {
                        $result = $coursesArr[$categoryId];
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Get course code by sku product
     *
     * @return array
     */
    public function getCourseCodeByCategory()
    {
        $arrCourseCode = [];
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['cour_cate' => $this->getTable('subscription_course_category')], 'category_id')
            ->join(['cour' => $this->getTable('subscription_course')], 'cour_cate.course_id = cour.course_id', ['course_code'])
            ->where('cour.course_code != ?', '');

        $data = $connection->fetchAll($select);
        $totalItem = count($data);
        if (!empty($totalItem)) {
            foreach ($data as $listItem) {
                $arrCourseCode[$listItem['category_id']] = $listItem['course_code'];
            }
        }

        return $arrCourseCode;
    }

    /**
     * @param $courseId
     * @param array $fields
     * @return array
     */
    public function getCourseInfoById($courseId, $fields = []){
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('subscription_course'), $fields)
            ->where('course_id=?', $courseId);

        return $connection->fetchRow($select);
    }

    /**
     * Get product course by table
     *
     * @param $productId
     * @param $tableName
     * @return array
     */
    public function getProductCourseByTable($productId, $tableName)
    {
        $connection = $this->getConnection();

        /*select list course id*/
        $courseIds = $connection->fetchCol($connection->select()
            ->from($tableName, ['course_id'])
            ->where('product_id=?', $productId));

        if ($courseIds) {
            return $courseIds;
        } else {
            return [];
        }
    }

    public function getProductByCategoryIds($categoryIds)
    {
        $products = [];
        if(!empty($categoryIds)) {
            $filter = $this->searchCriteriaBuilder
                ->addFilter('category_id', $categoryIds,'in')
                ->addFilter('available_subscription',1)
                ->create();
            $productRepository = $this->productRepository->getList($filter);

            //get all product by category in course
            if($this->appState->getAreaCode() == 'adminhtml'){
                foreach ($productRepository->getItems() as $item) {
                    $products[] = $item->getId();
                }
            }
        }
        return $products;
    }
    public function sortCategory($arrayChildren){
        $categorySort = [];
        $categoryCollection = $this->_categoryCollectionFactory->create();
        $categoryCollection->addAttributeToFilter('entity_id', ['in'    =>  $arrayChildren])
            ->addAttributeToSelect('position')
            ->addAttributeToSort('position','desc');
        if($categoryCollection){
            foreach ($categoryCollection as $catagory){
                $categorySort[] = $catagory['entity_id'];
            }
        }
        return $categorySort;
    }
    /**
     * Get additional products
     *
     *
     * @return array
     */
    public function getListOfProductGroupByAdditionalCategory($courseId)
    {
        if ($this->functionCache->has($courseId)) {
            return $this->functionCache->load($courseId);
        }
        $product = array();
        $model = $this->getSubscriptionCourseModel($courseId);
        $arrCategoryId = $model->getData('additional_category_ids');
        if ($arrCategoryId) {
            $listCategory =$this->loadCategoriesByIds($arrCategoryId);
            foreach($listCategory as $loadedCategoryId    =>  $loadedCategory){
                if(in_array($loadedCategoryId, $arrCategoryId)){
                    $aProductCategories = $this->getProductCollectionByCategory($loadedCategory);
                    if (count($aProductCategories)) {
                        $product[$loadedCategoryId] = $aProductCategories;
                    }
                }
            }
        }
        $this->functionCache->store($product,$courseId);
        return $product;
    }
    /**
     * Get Category by Course ID
     *
     * @return array
     */
    public function getListOfProductGroupByCategory($courseId,$isAdditional = false)
    {
        if ($this->functionCache->has([$courseId,$isAdditional])) {
            return $this->functionCache->load([$courseId,$isAdditional]);
        }
        $product = array();
        $model = $this->getSubscriptionCourseModel($courseId);

        if ($model->getSubscriptionType() == SubscriptionType::TYPE_HANPUKAI) {
            return $this->getAllProductByCoursePieceCaseForHanpukaiSort($model, $this->getStoreId());
        }
        if ($isAdditional) {
            $arrCategoryId = $model->getData('additional_category_ids');
        } else {
            $arrCategoryId = $model->getData('category_ids');
            if($model->hasData('profile_category_ids')) {
                $arrCategoryId = array_merge($arrCategoryId,$model->getData('profile_category_ids'));
            }
        }
        if($arrCategoryId) {
            $arrCategory =  $this->loadCategoriesByIds($arrCategoryId);
            foreach($arrCategory as $loadedCategoryId    =>  $loadedCategory){
                if(in_array($loadedCategoryId, $arrCategoryId)){
                    $productByCategory = $this->getProductCollectionByCategory($loadedCategory);
                    if(count($productByCategory) > 0){
                        $product[$loadedCategoryId] = $productByCategory;
                    }
                }
            }
        }
        $this->functionCache->store($product,[$courseId,$isAdditional]);
        return $product;
    }

    public function getListCagegorySubscriptionCourse($courseId) {
        $arrCategoryId = [];
        $model = $this->getSubscriptionCourseModel($courseId);

        $arrCategoryId = $model->getData('category_ids');
        if($model->hasData('profile_category_ids')) {
            $arrCategoryId = array_merge($arrCategoryId,$model->getData('profile_category_ids'));
        }
        if($arrCategoryId) {
            $arrCategory = $this->loadCategoriesByIdsAppReact($arrCategoryId);
        }
        return $arrCategoryId;

    }
    /**
     * @param array $categoriesId
     * @return $this
     * @throws LocalizedException
     */
    public function loadCategoriesByIds(array $categoriesId){
        $listCategory = [];
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->_categoryFactory->create()->getCollection();
        $collection->addAttributeToFilter('entity_id', ['in'    =>  $categoriesId])
            ->addAttributeToSelect('position')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('is_active')
            ->addAttributeToSort('position');
        foreach($collection as $category){
            $listCategory[$category->getId()] = $category;
        }
        return $listCategory;
    }
    /**
     * @param array $categoriesId
     * @return $this
     * @throws LocalizedException
     */
    public function loadCategoriesByIdsAppReact(array $categoriesId){
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->_categoryFactory->create()->getCollection();
        $collection->addAttributeToFilter('entity_id', ['in'    =>  $categoriesId])
            ->addAttributeToSelect('position')
            ->addAttributeToSelect('image')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('description')
            ->addAttributeToSelect('is_active')
            ->addFieldToFilter('is_active', 1)
        ->addAttributeToSort('position');
        foreach ($collection as $category) {
            $listCategory[join("", $categoriesId)][$category->getId()] = $category;
        }
        if (!empty($listCategory)) {
            return  $listCategory[join("", $categoriesId)];
        }
        return [];
    }
    /**
     * Get Category by Course ID
     *
     * @return array
     */
    public function getListOfProductGroupByCategoryAppReact($courseId, $arrCategoryId, $page, $limit, $isCategoryHomePage)
    {
        if ($this->functionCache->has([$courseId])) {
            return $this->functionCache->load([$courseId]);
        }
        $product = array();
        if($arrCategoryId) {
            $loadedCategories =  $this->loadCategoriesByIdsAppReact($arrCategoryId);
            foreach($loadedCategories as $loadedCategoryId => $loadedCategory){
                if(in_array($loadedCategoryId, $arrCategoryId)) {
                    $productByCategory = $this->getProductCollectionByCategoryAppRect($loadedCategory, $page, $limit, $isCategoryHomePage);
                    if(count($productByCategory) > 0) {
                        $product[$loadedCategoryId] = $productByCategory;
                    }
                }
            }
        }

        $this->functionCache->store($product, [$courseId]);
        return $product;
    }

    /**
     * @param $arrCategoryAditionalId
     * @param $courseId
     * @param bool $isAdditional
     * @return array
     */
    public function getListCategoriesAppReact(&$arrCategoryAditionalId, &$arrProfileCategoryIds, $courseId, $isAdditional = false)
    {
        $loadedCategoriesTmp = [];
        $arrCategoryAditionalId = [];
        $arrProfileCategoryIds = [];
        $model = $this->getSubscriptionCourseModel($courseId);

        if ($isAdditional) {
            $arrCategoryAditionalId = $model->getData('additional_category_ids');
        }

        $arrCategoryId = $model->getData('category_ids');

        if($model->hasData('profile_category_ids')) {
            $arrProfileCategoryIds = $model->getData('profile_category_ids');
        }

        $arrCategoryId = array_merge($arrCategoryId, $arrProfileCategoryIds);
        $arrCategoryId = array_merge($arrCategoryId, $arrCategoryAditionalId);

        if ($arrCategoryId) {
            $loadedCategories =  $this->loadCategoriesByIdsAppReact($arrCategoryId);
            foreach($loadedCategories as $loadedCategoryId => $loadedCategory) {
                if(in_array($loadedCategoryId, $arrCategoryId)) {
                    $loadedCategoriesTmp[$loadedCategoryId] = $loadedCategory->getData();
                }
            }
        }
        return $loadedCategoriesTmp;
    }
    /**
     * @param $arrCategoryId
     * @return array
     */
    public function getListCategoriesRecommendAppReact($arrCategoryId)
    {
        $loadedCategoriesTmp = [];

        if ($arrCategoryId) {
            $loadedCategories =  $this->loadCategoriesByIdsAppReact($arrCategoryId);
            foreach($loadedCategories as $loadedCategoryId => $loadedCategory) {
                if(in_array($loadedCategoryId, $arrCategoryId)) {
                    $loadedCategoriesTmp[$loadedCategoryId] = $loadedCategory->getData();
                }
            }
        }
        return $loadedCategoriesTmp;
    }

    /**
     * @param $text
     * @return mixed
     */
    public function filterText($text)
    {
        return $this->filterProvider->getBlockFilter()->filter($text);
    }

    /**
     *
     */
    public function sortCategoryByPosition($arrCategory)
    {
        $arrResult = array();
        foreach ($arrCategory as $categoryId) {
            $categoryObj = $this->getCategoryById($categoryId);
            $arrResult[$categoryObj->getPosition()] = $categoryId;
        }
        ksort($arrResult);
        return array_values($arrResult);
    }
    public function getProductCollectionByCategory(\Magento\Catalog\Model\Category $category)
    {
//        if($fc = $this->cache->load($this->getCacheKey(["products","in","category",$category->getId()]))){
//            return $this->serialize->unserialize($fc);
//        }

        $categoryId = $category->getId();
        $result = array();
        $cacheTags = [];
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
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $category->getProductCollection()
            ->addFieldToFilter('available_subscription', 1)
            ->setOrder('position', 'ASC');
        if ($category->getIsActive()) {
            $productCollection = $this->addStockInfoToProductCollection($productCollection);
            $cacheTags[] = \Magento\Catalog\Model\Category::CACHE_TAG . "_" . $category->getId();
            foreach ($productCollection as $product) {
                if ($this->checkProductAvailableForShow($product,$currentStoreId) && !in_array($product->getId(),$this->productCategoryExist )) {
                    $result[] = $product;
                    $cacheTags [] = \Magento\Catalog\Model\Product::CACHE_TAG . "_" . $product->getId();
                    $this->productCategoryExist[] = $product->getId();
                }
            }
        }

//        $this->cache->save($this->serialize->serialize($result), $this->getCacheKey(["products","in","category",$category->getId()]), array_unique($cacheTags));

        return $result;
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductCollectionByCategoryAppRect(\Magento\Catalog\Model\Category $category, $page, $limit, $isCategoryHomePage)
    {
        $categoryId = $category->getId();
        $result = array();
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
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $category->getProductCollection()
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
                'special_price',
                'tier_price'
            ]);
            if ($isCategoryHomePage == 1) {
                $productCollection = $productCollection->addFieldToFilter('available_subscription', 1);
            }
        $productCollection = $productCollection->setOrder('position', 'ASC');

        if ($category->getIsActive()) {
            $productCollection = $this->addStockInfoToProductCollectionAppReact($productCollection, $page, $limit);
            foreach ($productCollection as $product) {
                if ($this->checkProductAvailableForShow($product,$currentStoreId) && !in_array($product->getId(), $this->productCategoryExist )) {
                    $result[] = $product;
                    $this->productCategoryExist[] = $product->getId();
                }
            }
        }
        return $result;
    }
    /**
     * Get current course
     *
     * @return \Riki\SubscriptionCourse\Model\Course
     */
    public function getSubscriptionCourseModel($courseId)
    {
        if ($this->functionCache->has($courseId)) {
            return $this->functionCache->load($courseId);
        }
        $result = $this->_courseFactory->create()->load($courseId);
        $this->functionCache->store($result,$courseId);
        return $result;
    }
    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function addStockInfoToProductCollection(\Magento\Catalog\Model\ResourceModel\Product\Collection $collection)
    {
        $collection->getSelect()
            ->joinLeft(
                ["ci_stock_item" => 'cataloginventory_stock_item'],
                'e.entity_id=ci_stock_item.product_id',
                [
                    'managed_stock' => new \Zend_Db_Expr("IF(use_config_manage_stock=1," . (int)$this->getStockConfigByPath('manage_stock') . ",ci_stock_item.manage_stock )"),
                    'min_sale_qty' => new \Zend_Db_Expr("IF(use_config_min_sale_qty=1," . (int)$this->getStockConfigByPath('min_sale_qty') . ",ci_stock_item.min_sale_qty)"),
                    'max_sale_qty' => new \Zend_Db_Expr("IF(use_config_max_sale_qty=1," . (int)$this->getStockConfigByPath('max_sale_qty') . ",ci_stock_item.max_sale_qty)"),
                    'is_in_stock_org' => 'ci_stock_item.is_in_stock',
                    'quantity_in_stock'   =>  'ci_stock_item.qty'
                ],
                null,
                'left'
            )
            ->where('ci_stock_item.website_id IN(' . implode(',', [0, $this->getWebsiteId()]) . ')');
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
        foreach($joinedAttributes as $joinedAttribute){
            try{
                if((strpos((string)$collection->getSelectSql(), 'AS `at_' . $joinedAttribute . '`') === false)){
                    $collection->joinAttribute($joinedAttribute, 'catalog_product/' . $joinedAttribute, 'entity_id', null, 'left', $this->getStoreId()? $this->getStoreId() : null);
                }
            }catch (\Exception $e){
                $this->_logger->critical($e);
            }
        }
        return $collection;
    }
    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function addStockInfoToProductCollectionAppReact(\Magento\Catalog\Model\ResourceModel\Product\Collection $collection, $page = 1, $limit = 10)
    {
        $collection->getSelect()
            ->joinLeft(
                ["ci_stock_item" => 'cataloginventory_stock_item'],
                'e.entity_id=ci_stock_item.product_id',
                [
                    'managed_stock' => new \Zend_Db_Expr("IF(use_config_manage_stock=1," . (int)$this->getStockConfigByPath('manage_stock') . ",ci_stock_item.manage_stock )"),
                    'min_sale_qty' => new \Zend_Db_Expr("IF(use_config_min_sale_qty=1," . (int)$this->getStockConfigByPath('min_sale_qty') . ",ci_stock_item.min_sale_qty)"),
                    'max_sale_qty' => new \Zend_Db_Expr("IF(use_config_max_sale_qty=1," . (int)$this->getStockConfigByPath('max_sale_qty') . ",ci_stock_item.max_sale_qty)"),
                    'is_in_stock_org' => 'ci_stock_item.is_in_stock',
                    'quantity_in_stock'   =>  'ci_stock_item.qty'
                ],
                null,
                'left'
            )
            ->where('ci_stock_item.website_id IN(' . implode(',', [0, $this->getWebsiteId()]) . ')');
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
        foreach($joinedAttributes as $joinedAttribute){
            try{
                if((strpos((string)$collection->getSelectSql(), 'AS `at_' . $joinedAttribute . '`') === false)){
                    $collection->joinAttribute($joinedAttribute, 'catalog_product/' . $joinedAttribute, 'entity_id', null, 'left', $this->getStoreId()? $this->getStoreId() : null);
                }
            }catch (\Exception $e){
                $this->_logger->critical($e);
            }
        }
        if ($limit != 'all') {
            $collection->getSelect()->limitPage($page, $limit);
        }
        return $collection;
    }
    /*
     * get store Id
     *
        */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }
    /*
     * get website id
     *
     */
    public function getWebsiteId()
    {
        return $this->_storeManager->getStore()->getWebsiteId();
    }
    /**
     * @param $configName
     * @return mixed
     */
    public function getStockConfigByPath($configName){
        return $this->scopeConfig->getValue('cataloginventory/item_options/' . $configName);
    }
    /**
     * @param $course
     * @param $storeId
     * @return $this|bool|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllProductByCoursePieceCaseForHanpukaiSort($course, $storeId){
        $hanpukaiData = null;
        $listProduct = [];
        switch($course->getHanpukaiType()){
            case SubscriptionType::TYPE_HANPUKAI_FIXED:
                $arrProductHanpukaiConfig = $this->getHanpukaiFixedProductsDataPieCase($course);
                $arrProductIdConfig = array_keys($arrProductHanpukaiConfig);
                $hanpukaiData  = $this->_productFactory->create()->getCollection()->addAttributeToSelect('*')->addStoreFilter($storeId);
                $hanpukaiData->addFieldToFilter('entity_id', ['in' => $arrProductIdConfig]);
                $hanpukaiData->addAttributeToSort('position','desc');
                foreach ($hanpukaiData as $item) {
                    $addData = $arrProductHanpukaiConfig[$item->getId()];
                    $item->addData(array('fix_qty' => $addData['qty']));
                    $item->addData(array('unit_case' => $addData['unit_case']));
                    $item->addData(array('unit_qty' => $addData['unit_qty']));
                }
                break;
            case SubscriptionType::TYPE_HANPUKAI_SEQUENCE:
                $arrProductHanpukaiConfig = $this->getHanpukaiSequenceFirstDelivery($course);
                $arrProductIdConfig = array_keys($arrProductHanpukaiConfig);
                $hanpukaiData  = $this->_productFactory->create()->getCollection()->addAttributeToSelect('*')->addStoreFilter($storeId);
                $hanpukaiData->addFieldToFilter('entity_id',['in' => $arrProductIdConfig]);
                $hanpukaiData->addAttributeToSort('position','desc');
                foreach ($hanpukaiData  as $item) {
                    $addData = $arrProductHanpukaiConfig[$item->getId()];
                    $item->addData(array('fix_qty' => $addData['qty']));
                    $item->addData(array('unit_case'=> $addData['unit_case']));
                    $item->addData(array('unit_qty'=> $addData['unit_qty']));
                }
                break;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(__('The subscription can not be saved'));
        }
        if($hanpukaiData->getItems()) {
            $listProduct[] = $hanpukaiData->getItems();
            return $listProduct;
        }else{
            return $listProduct;
        }
    }

    /**
     * Get all course code by customer id
     *
     * @param $customerId
     * @return array
     */
    public function getAllCourseCodeInProfileActiveByCustomerId($customerId)
    {
        $courseCode = [];
        $connection = $this->getConnection();
        $tableCourse = $this->getTable('subscription_course');
        $tableSubscriptionProfile = $this->getTable('subscription_profile');

        $select = $connection->select()
            ->from(['cour' => $tableCourse], 'course_code')
            ->join(['profile' => $tableSubscriptionProfile], 'profile.course_id = cour.course_id', [])
            ->where('profile.status = ?', '1')
            ->where('profile.customer_id = ?', $customerId)
            ->where('profile.disengagement_date IS NULL')
            ->where('profile.disengagement_reason IS NULL')
            ->where('profile.disengagement_user IS NULL')
            ->distinct();

        $result = $connection->fetchCol($select);
        if (!empty($result)) {
            return $result;
        }
        return $courseCode;
    }

    public function getMachinesOfTypeByCourse(\Riki\SubscriptionCourse\Model\Course $course)
    {
        $types = $course->getMultiMachine();
        if (!$types) {
            return [];
        }
        $machineTypeModel = $this->machineTypeFactory->create();
        $listMachines = [];
        foreach ($types as $type) {
            $machines = $machineTypeModel->getResource()->getMachinesByType($type);
            if (!$machines) {
                continue;
            }
            foreach ($machines as $machine) {
                $key = $machine['product_id'];
                $listMachines[$key] = $machine['product_id'];
            }
        }
        return $listMachines;
    }


    /**
     * @param $keys
     * @return string
     */
    protected function getCacheKey($keys){
        $key = "COURSE_CACHE_";
        foreach ($keys as $key) {
            $key .= $key;
        }

        return $key;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return mixed|null
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
     * @param $product
     * @return mixed
     */
    public function getProductType($product)
    {
        return $product->getTypeId();
    }
}
