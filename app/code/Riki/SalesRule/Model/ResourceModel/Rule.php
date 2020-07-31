<?php
namespace Riki\SalesRule\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\EntityManager\MetadataPool;

class Rule extends \Magento\SalesRule\Model\ResourceModel\Rule
{

    /**
     * @var array
     */
    protected $subscriptionCourseIds = [];

    /**
     * @var array
     */
    protected $subscriptionFrequencyIds = [];

    /**
     * Store associated with rule entities information map
     *
     * @var array
     */
    protected $_associatedEntitiesMap = [
        'website' => [
            'associations_table' => 'salesrule_website',
            'rule_id_field' => 'rule_id',
            'entity_id_field' => 'website_id',
        ],
        'customer_group' => [
            'associations_table' => 'salesrule_customer_group',
            'rule_id_field' => 'rule_id',
            'entity_id_field' => 'customer_group_id',
        ],
        'subscription_course' => [
            'associations_table' => 'salesrule_subscription_course',
            'rule_id_field' => 'rule_id',
            'entity_id_field' => 'course_id',
        ],
        'subscription_frequency' => [
            'associations_table' => 'salesrule_subscription_frequency',
            'rule_id_field' => 'rule_id',
            'entity_id_field' => 'frequency_id',
        ],
    ];

    /**
     * @var integer
     */
    protected $pointsDelta;

    /**
     * @var string
     */
    protected $typeBy;

    /**
     * @var array
     */
    protected $rewardPointRegistry = [];

    /**
     * @var array
     */
    protected $subCourseRegistry = [];

    /**
     * @var array
     */
    protected $subFrequencyRegistry = [];

    /**
     * @var array
     */
    protected $websiteRegistry = [];

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * Rule constructor.
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\SalesRule\Model\ResourceModel\Coupon $resourceCoupon
     * @param string|null $connectionName
     * @param \Magento\Framework\DataObject|null $associatedEntityMapInstance
     * @param Json|null $serializer
     * @param MetadataPool|null $metadataPool
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\SalesRule\Model\ResourceModel\Coupon $resourceCoupon,
        ?string $connectionName = null,
        ?\Magento\Framework\DataObject $associatedEntityMapInstance = null,
        Json $serializer = null,
        MetadataPool $metadataPool = null
    ) {
        $this->functionCache = $functionCache;
        parent::__construct($context, $string, $resourceCoupon, $connectionName, $associatedEntityMapInstance,
            $serializer, $metadataPool);
    }

    /**
     * @param AbstractModel $object
     * @return void
     */
    public function loadCustomerGroupIds(AbstractModel $object)
    {
        $objectID = $object->getData('rule_id');
        if (!isset($this->customerGroupIds[$objectID])
            && empty($this->customerGroupIds[$objectID])
        ) {
            $this->customerGroupIds[$objectID] = (array)$this->getCustomerGroupIds($object->getId());
        }
        $object->setData('customer_group_ids', $this->customerGroupIds[$objectID]);
    }


    /**
     * Bind sales rule to customer group(s) and website(s).
     * Save rule's associated store labels.
     * Save product attributes used in rule.
     *
     * @param \Magento\Framework\Model\AbstractModel $object AbstractModel
     *
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        if ($object->hasApplySubscription()) {
            $courseIds = $object->getApplySubscription();
            if (!is_array($courseIds)) {
                $courseIds = explode(',', (string)$courseIds);
            }
            $originalData = $object->getOrigData();
            $originalCourseIds = is_array($originalData['apply_subscription'])?$originalData['apply_subscription']:[];
            $arrayDiff = sizeof(array_diff($courseIds,$originalCourseIds)) > 0 ?array_diff($courseIds,$originalCourseIds):array_diff($originalCourseIds,$courseIds) ;
            if(is_array($arrayDiff) && sizeof($arrayDiff) > 0) {
                $this->bindRuleToEntity($object->getId(), $courseIds, 'subscription_course');
            }
        }
        if ($object->hasApplyFrequency()) {
            $frequencyIds = $object->getApplyFrequency();
            if (!is_array($frequencyIds)) {
                $frequencyIds = explode(',', (string)$frequencyIds);
            }
            $originalData = $object->getOrigData();
            $originalFrequencyIds = is_array($originalData['apply_frequency'])?$originalData['apply_frequency']:[];
            $arrayDiff = sizeof(array_diff($frequencyIds,$originalFrequencyIds)) > 0 ?array_diff($frequencyIds,$originalFrequencyIds):array_diff($originalFrequencyIds,$frequencyIds) ;
            if(is_array($arrayDiff) && sizeof($arrayDiff) > 0) {
                $this->bindRuleToEntity($object->getId(), $frequencyIds, 'subscription_frequency');
            }
        }
        if ($object->hasPointsDelta()) {
            $point = $object->getPointsDelta();
            $type = $object->getTypeBy();
            $this->getConnection()->insertOnDuplicate(
                $this->getTable('riki_rewards_salesrule'),
                ['rule_id' => $object->getId(), 'points_delta' => $point, 'type_by' => $type]
            );
        }
        return parent::_afterSave($object);
    }

    /**
     * Add customer group ids and website ids to rule data after load
     *
     * @param AbstractModel $object AbstractModel
     *
     * @return $this
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $this->loadSubscriptionCourseIds($object);
        $this->loadSubscriptionFrequencyIds($object);
        $this->loadRewardPoint($object);

        parent::_afterLoad($object);
        return $this;
    }

    /**
     * Load SubscriptionCourseIds
     *
     * @param AbstractModel $object AbstractModel
     *
     * @return void
     */
    public function loadSubscriptionCourseIds(AbstractModel $object)
    {
        $this->subscriptionCourseIds = (array)$this->getSubscriptionCourseIds($object->getId());
        $object->setData('apply_subscription', $this->subscriptionCourseIds);
    }

    /**
     * Load SubscriptionFrequencyIds
     *
     * @param AbstractModel $object AbstractModel
     *
     * @return void
     */
    public function loadSubscriptionFrequencyIds(AbstractModel $object)
    {
        $this->subscriptionFrequencyIds = (array)$this->getSubscriptionFrequencyIds($object->getId());
        $object->setData('apply_frequency', $this->subscriptionFrequencyIds);
    }

    /**
     * Load RewardPoint
     *
     * @param AbstractModel $object AbstractModel
     *
     * @return void
     */
    public function loadRewardPoint(AbstractModel $object)
    {
        if(isset($this->rewardPointRegistry[$object->getId()])){
            $row = $this->rewardPointRegistry[$object->getId()];
        }
        else{
            $select = $this->getConnection()->select()->from(
                $this->getTable('riki_rewards_salesrule'),
                ['points_delta', 'type_by']
            )->where('rule_id = ?', $object->getId());
            $row = $this->getConnection()->fetchRow($select);
            $this->rewardPointRegistry[$object->getId()] = $row;
        }

        if ($row) {
            $object->setData('points_delta', $row['points_delta']);
            $object->setData('type_by', $row['type_by']);
        }
    }

    /**
     * Retrieve customer group ids of specified rule
     *
     * @param int $ruleId int
     *
     * @return array
     */
    public function getSubscriptionCourseIds($ruleId)
    {
        if(isset($this->subCourseRegistry[$ruleId])){
            return $this->subCourseRegistry[$ruleId];
        }
        else{
            $aSubCourseData = $this->getAssociatedEntityIds($ruleId, 'subscription_course');
            $this->subCourseRegistry[$ruleId] = $aSubCourseData;
            return $aSubCourseData;
        }
    }

    /**
     * @param $ruleId
     * @return array
     */
    public function getSubscriptionFrequencyIds($ruleId)
    {
        if(isset($this->subFrequencyRegistry[$ruleId])){
            return $this->subFrequencyRegistry[$ruleId];
        }
        else{
            $aSubFrequencyData = $this->getAssociatedEntityIds($ruleId, 'subscription_frequency');
            $this->subFrequencyRegistry[$ruleId] = $aSubFrequencyData;
            return $aSubFrequencyData;
        }
    }

    /**
     * Retrieve website ids of specified rule
     *
     * @param int $ruleId
     * @return array
     */
    public function getWebsiteIds($ruleId)
    {
        if(isset($this->websiteRegistry[$ruleId])){
            return $this->websiteRegistry[$ruleId];
        }
        else{
            $aWebsiteData = $this->getAssociatedEntityIds($ruleId, 'website');
            $this->websiteRegistry[$ruleId] = $aWebsiteData;
            return $aWebsiteData;
        }
    }

    /**
     * Prepare sales rule's delivery number
     *
     * @param \Magento\Framework\Model\AbstractModel $object AbstractModel
     *
     * @return $this
     */
    public function _beforeSave(AbstractModel $object)
    {
        if ($object->getSubscriptionDelivery() == 3) {
            $object->setDeliveryN(new \Zend_Db_Expr('NULL'));
        }

        parent::_beforeSave($object);
        return $this;
    }

    /**
     * Retrieve reward salesrule data by given rule Id or array of Ids
     *
     * @param int|array $rule mix
     *
     * @return array
     */
    public function getRewardSalesrule($rule)
    {
        $data = [];
        $select = $this->getConnection()->select()->from(
            $this->getTable('riki_rewards_salesrule')
        )->where(
            'rule_id IN (?)',
            $rule
        );
        if (is_array($rule)) {
            $data = $this->getConnection()->fetchAll($select);
        } else {
            $data = $this->getConnection()->fetchRow($select);
        }
        return $data;
    }


    /**
     * check course's product is match the promotion rule
     *
     * @param int $ruleId
     * @param int $courseId
     * @param int $frequencyId
     *
     * @return int
     */
    public function getSubscriptionRule($ruleId, $courseId, $frequencyId)
    {
        $cacheKey = [$ruleId, $courseId, $frequencyId];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['c' => $this->getTable('salesrule_subscription_course')], ['c.course_id'])
            ->joinLeft(['f' => 'salesrule_subscription_frequency'], 'c.rule_id = f.rule_id', [])
            ->where('c.rule_id = ?', $ruleId)
            ->where('c.course_id = ?', $courseId)
            ->where('f.frequency_id = ?', $frequencyId);

        $result = $connection->fetchOne($select);
        $this->functionCache->store($result, $cacheKey);

        return $result;
    }

    /**
     * Get rules match subscription course.
     *
     * @param $courseId
     * @param $frequencyId
     *
     * @return array
     */
    public function getSubscriptionRules($courseId, $frequencyId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['c' => $this->getTable('salesrule_subscription_course')], ['c.rule_id'])
            ->joinLeft(['f' => 'salesrule_subscription_frequency'], 'c.rule_id = f.rule_id', [])
            ->where('c.course_id = ?', $courseId)
            ->where('f.frequency_id = ?', $frequencyId);

        return $connection->fetchCol($select);
    }

    /**
     * get course's category is match the promotion rule
     *
     * @param int $productId
     *
     * @return array
     */
    public function getCategoryProductRule($productId)
    {
        $cacheKey = [$productId];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['c' => $this->getTable('catalog_category_product')], ['c.category_id'])
            ->where('c.product_id = ?', $productId);

        $result = $connection->fetchCol($select);
        $this->functionCache->store($result, $cacheKey);

        return $result;
    }
}
