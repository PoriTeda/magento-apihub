<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model\ResourceModel;

use Magento\Framework\App\Http\Context as HttpContext;

/**
 * TargetRule Product Index by Rule Product List Type Resource Model
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Index extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Increment value for generate unique bind names
     *
     * @var int
     */
    protected $_bindIncrement = 0;

    /**
     * Target rule data
     *
     * @var \Magento\TargetRule\Helper\Data
     */
    protected $_targetRuleData;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Customer segment data
     *
     * @var \Magento\CustomerSegment\Helper\Data
     */
    protected $_customerSegmentData;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_session;

    /**
     * @var \Magento\CustomerSegment\Model\Customer
     */
    protected $_customer;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_visibility;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment
     */
    protected $_segmentCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var \Magento\TargetRule\Model\ResourceModel\Rule
     */
    protected $_rule;

    /**
     * @var \Magento\TargetRule\Model\ResourceModel\IndexPool
     */
    protected $_indexPool;

    /**
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    private $stockHelper;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\TargetRule\Model\ResourceModel\IndexPool $indexPool
     * @param \Magento\TargetRule\Model\ResourceModel\Rule $rule
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $segmentCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Product\Visibility $visibility
     * @param \Magento\CustomerSegment\Model\Customer $customer
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\CustomerSegment\Helper\Data $customerSegmentData
     * @param \Magento\TargetRule\Helper\Data $targetRuleData
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\CatalogInventory\Helper\Stock $stockHelper
     * @param string $connectionName
     * @param HttpContext|null $httpContext
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\TargetRule\Model\ResourceModel\IndexPool $indexPool,
        \Magento\TargetRule\Model\ResourceModel\Rule $rule,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $segmentCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Magento\CustomerSegment\Model\Customer $customer,
        \Magento\Customer\Model\Session $session,
        \Magento\CustomerSegment\Helper\Data $customerSegmentData,
        \Magento\TargetRule\Helper\Data $targetRuleData,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\CatalogInventory\Helper\Stock $stockHelper = null,
        string $connectionName = null,
        HttpContext $httpContext = null
    ) {
        $this->_indexPool = $indexPool;
        $this->_rule = $rule;
        $this->_segmentCollectionFactory = $segmentCollectionFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->_visibility = $visibility;
        $this->_customer = $customer;
        $this->_session = $session;
        $this->_coreRegistry = $coreRegistry;
        $this->_customerSegmentData = $customerSegmentData;
        $this->_targetRuleData = $targetRuleData;
        $this->stockHelper = $stockHelper
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\CatalogInventory\Helper\Stock::class);
        $this->httpContext = $httpContext
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(HttpContext::class);

        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize connection and define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_targetrule_index', 'entity_id');
    }

    /**
     * Retrieve constant value overfill limit for product ids index
     *
     * @return int
     */
    public function getOverfillLimit()
    {
        return 20;
    }

    /**
     * Retrieve array of defined product list type id
     *
     * @return int[]
     */
    public function getTypeIds()
    {
        return [
            \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS,
            \Magento\TargetRule\Model\Rule::UP_SELLS,
            \Magento\TargetRule\Model\Rule::CROSS_SELLS
        ];
    }

    /**
     * Retrieve product Ids
     *
     * @param \Magento\TargetRule\Model\Index $object
     * @return array
     */
    public function getProductIds($object)
    {
        $segmentsIds = array_merge([0], $this->_getSegmentsIdsFromCurrentCustomer());

        $productIds = [];
        foreach ($segmentsIds as $segmentId) {
            $matchedProductIds = $this->_indexPool->get($object->getType())
                ->loadProductIdsBySegmentId($object, $segmentId);

            if (empty($matchedProductIds)) {
                $matchedProductIds = $this->_matchProductIdsBySegmentId($object, $segmentId);
                $this->_indexPool->get($object->getType())
                    ->saveResultForCustomerSegments(
                        $object,
                        $segmentId,
                        $matchedProductIds
                    );
            }

            $productIds = array_merge($matchedProductIds, $productIds);
        }

        $productIds = array_diff(array_unique($productIds), $object->getExcludeProductIds());
        $rotationMode = $this->_targetRuleData->getRotationMode($object->getType());
        if ($rotationMode == \Magento\TargetRule\Model\Rule::ROTATION_SHUFFLE) {
            shuffle($productIds);
        }
        return array_slice($productIds, 0, $object->getLimit());
    }

    /**
     * Match, save and return applicable product ids by segmentId object
     *
     * @param \Magento\TargetRule\Model\Index $object
     * @param string $segmentId
     * @return array
     */
    protected function _matchProductIdsBySegmentId($object, $segmentId)
    {
        $limit = $object->getLimit() + $this->getOverfillLimit();
        $productIds = [];
        $ruleCollection = $object->getRuleCollection();
        if ($this->_customerSegmentData->isEnabled()) {
            $ruleCollection->addSegmentFilter($segmentId);
        }
        foreach ($ruleCollection as $rule) {
            /* @var $rule \Magento\TargetRule\Model\Rule */
            if (count($productIds) >= $limit) {
                break;
            }
            if (!$rule->checkDateForStore($object->getStoreId())) {
                continue;
            }
            $excludeProductIds = array_merge([$object->getProduct()->getEntityId()], $productIds);
            $resultIds = $this->_getProductIdsByRule($rule, $object, $rule->getPositionsLimit(), $excludeProductIds);
            $productIds = array_merge($productIds, $resultIds);
        }
        return $productIds;
    }

    /**
     * Retrieve found product ids by Rule action conditions
     * If rule has cached select - get it
     *
     * @param \Magento\TargetRule\Model\Rule $rule
     * @param \Magento\TargetRule\Model\Index $object
     * @param int $limit
     * @param array $excludeProductIds
     * @return array
     */
    protected function _getProductIdsByRule($rule, $object, $limit, $excludeProductIds = [])
    {
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $collection = $this->_productCollectionFactory->create()->setStoreId(
            $object->getStoreId()
        )->addPriceData(
            $object->getCustomerGroupId()
        )->setVisibility(
            $this->_visibility->getVisibleInCatalogIds()
        );

        $actionSelect = $rule->getActionSelect();
        $actionBind = $rule->getActionSelectBind();

        if ($actionSelect === null) {
            $actionBind = [];
            $actionSelect = $rule->getActions()->getConditionForCollection($collection, $object, $actionBind);
            $rule->setActionSelect((string)$actionSelect)->setActionSelectBind($actionBind)->save();
        }

        if ($actionSelect) {
            $collection->getSelect()->where($actionSelect);
        }
        if ($excludeProductIds) {
            $collection->addFieldToFilter('entity_id', ['nin' => $excludeProductIds]);
        }

        $this->stockHelper->addInStockFilterToCollection($collection);

        $select = $collection->getSelect();
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);
        $select->columns('entity_id', 'e');
        $select->limit($limit);

        $bind = $this->_prepareRuleActionSelectBind($object, $actionBind);
        $result = $this->getConnection()->fetchCol($select, $bind);

        return $result;
    }

    /**
     * Prepare bind array for product select
     *
     * @param \Magento\TargetRule\Model\Index $object
     * @param array $actionBind
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _prepareRuleActionSelectBind($object, $actionBind)
    {
        $bind = [];
        if (!is_array($actionBind)) {
            $actionBind = [];
        }

        foreach ($actionBind as $bindData) {
            if (!is_array($bindData) || !array_key_exists('bind', $bindData) || !array_key_exists('field', $bindData)
            ) {
                continue;
            }
            $k = $bindData['bind'];
            $v = $object->getProduct()->getDataUsingMethod($bindData['field']);

            if (!empty($bindData['callback'])) {
                $callbacks = $bindData['callback'];
                if (!is_array($callbacks)) {
                    $callbacks = [$callbacks];
                }
                foreach ($callbacks as $callback) {
                    if (is_array($callback)) {
                        $v = $this->{$callback[0]}($v, $callback[1]);
                    } else {
                        $v = $this->{$callback}($v);
                    }
                }
            }

            if (is_array($v)) {
                $v = join(',', $v);
            }

            $bind[$k] = $v;
        }

        return $bind;
    }

    /**
     * Retrieve new SELECT instance (used Read Adapter)
     *
     * @return \Magento\Framework\DB\Select
     */
    public function select()
    {
        return $this->getConnection()->select();
    }

    /**
     * Retrieve SQL condition fragment by field, operator and value
     *
     * @param string $field
     * @param string $operator
     * @param int|string|array $value
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getOperatorCondition($field, $operator, $value)
    {
        switch ($operator) {
            case '!=':
            case '>=':
            case '<=':
            case '>':
            case '<':
                $selectOperator = sprintf('%s?', $operator);
                break;
            case '{}':
            case '!{}':
                if (is_array($value)) {
                    $selectOperator = ' IN (?)';
                } else {
                    $selectOperator = ' LIKE ?';
                    $value = '%' . $value . '%';
                }
                if (substr($operator, 0, 1) == '!') {
                    $selectOperator = ' NOT' . $selectOperator;
                }
                break;

            case '()':
                $selectOperator = ' IN(?)';
                break;

            case '!()':
                $selectOperator = ' NOT IN(?)';
                break;

            default:
                $selectOperator = '=?';
                break;
        }
        $field = $this->getConnection()->quoteIdentifier($field);
        return $this->getConnection()->quoteInto("{$field}{$selectOperator}", $value);
    }

    /**
     * Retrieve SQL condition fragment by field, operator and binded value
     * also modify bind array
     *
     * @param string $field
     * @param mixed $attribute
     * @param string $operator
     * @param array $bind
     * @param array $callback
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getOperatorBindCondition($field, $attribute, $operator, &$bind, $callback = [])
    {
        $field = $this->getConnection()->quoteIdentifier($field);
        $bindName = ':targetrule_bind_' . $this->_bindIncrement++;
        switch ($operator) {
            case '!=':
            case '>=':
            case '<=':
            case '>':
            case '<':
                $condition = sprintf('%s%s%s', $field, $operator, $bindName);
                break;
            case '{}':
                $condition = sprintf('%s LIKE %s', $field, $bindName);
                $callback[] = 'bindLikeValue';
                break;

            case '!{}':
                $condition = sprintf('%s NOT LIKE %s', $field, $bindName);
                $callback[] = 'bindLikeValue';
                break;

            case '()':
                $condition = $this->getConnection()->prepareSqlCondition(
                    $bindName,
                    ['finset' => new \Zend_Db_Expr($field)]
                );
                break;

            case '!()':
                $condition = $this->getConnection()->prepareSqlCondition(
                    $bindName,
                    ['finset' => new \Zend_Db_Expr($field)]
                );
                $condition = sprintf('NOT (%s)', $condition);
                break;

            default:
                $condition = sprintf('%s=%s', $field, $bindName);
                break;
        }

        $bind[] = ['bind' => $bindName, 'field' => $attribute, 'callback' => $callback];

        return $condition;
    }

    /**
     * Prepare bind value for LIKE condition
     * Callback method
     *
     * @param string $value
     * @return string
     */
    public function bindLikeValue($value)
    {
        return '%' . $value . '%';
    }

    /**
     * Prepare bind array of ids from string or array
     *
     * @param string|int|array $value
     * @return array
     */
    public function bindArrayOfIds($value)
    {
        if (!is_array($value)) {
            $value = explode(',', $value);
        }

        $value = array_map('trim', $value);
        $value = array_filter($value, 'is_numeric');

        return $value;
    }

    /**
     * Prepare bind value (percent of value)
     *
     * @param float $value
     * @param int $percent
     * @return float
     */
    public function bindPercentOf($value, $percent)
    {
        return round($value * ($percent / 100), 4);
    }

    /**
     * Remove index data from index tables
     *
     * @param int|null $typeId
     * @param \Magento\Store\Model\Store|int|array|null $store
     * @return $this
     */
    public function cleanIndex($typeId = null, $store = null)
    {
        $connection = $this->getConnection();

        if ($store instanceof \Magento\Store\Model\Store) {
            $store = $store->getId();
        }

        if ($typeId === null) {
            foreach ($this->getTypeIds() as $typeId) {
                $this->_indexPool->get($typeId)->cleanIndex($store);
            }

            $where = $store === null ? '' : ['store_id IN(?)' => $store];
            $connection->delete($this->getMainTable(), $where);
        } else {
            $where = ['type_id=?' => $typeId];
            if ($store !== null) {
                $where['store_id IN(?)'] = $store;
            }
            $connection->delete($this->getMainTable(), $where);
            $this->_indexPool->get($typeId)->cleanIndex($store);
        }

        return $this;
    }

    /**
     * Remove products from index tables
     *
     * @param int|null $productId
     * @return $this
     */
    public function deleteProductFromIndex($productId = null)
    {
        foreach ($this->getTypeIds() as $typeId) {
            $this->_indexPool->get($typeId)->deleteProductFromIndex($productId);
        }
        return $this;
    }

    /**
     * Remove target rule matched product index data by product id or/and rule id
     *
     * @param array|int|null $productId
     * @param array|int|string $ruleIds
     * @return $this
     */
    public function removeProductIndex($productId = null, $ruleIds = [])
    {
        $this->_rule->unbindRuleFromEntity($ruleIds, $productId, 'product');
        return $this;
    }

    /**
     * Bind target rule to specified product
     *
     * @param \Magento\TargetRule\Model\Rule $object
     * @return $this
     */
    public function saveProductIndex($object)
    {
        $this->_rule->bindRuleToEntity($object->getId(), $object->getMatchingProductIds(), 'product');
        return $this;
    }

    /**
     * Adds order by random to select object
     *
     * @param \Magento\Framework\DB\Select $select
     * @param string|null $field
     * @return $this
     */
    public function orderRand(\Magento\Framework\DB\Select $select, $field = null)
    {
        $this->getConnection()->orderRand($select, $field);
        return $this;
    }

    /**
     * Get SegmentsIds From Current Customer
     *
     * @return array
     */
    protected function _getSegmentsIdsFromCurrentCustomer()
    {
        $segmentIds = [];
        if ($this->_customerSegmentData->isEnabled()) {
            $customer = $this->_coreRegistry->registry('segment_customer');
            if (!$customer) {
                $customer = $this->_session->getCustomer();
            }
            $websiteId = $this->_storeManager->getWebsite()->getId();

            if (!$customer->getId()) {
                $segmentIds = (array)$this->httpContext->getValue(
                    \Magento\CustomerSegment\Helper\Data::CONTEXT_SEGMENT
                );
            } else {
                $segmentIds = $this->_customer->getCustomerSegmentIdsForWebsite($customer->getId(), $websiteId);
            }

            if (count($segmentIds)) {
                $segmentIds = $this->_segmentCollectionFactory->getActiveSegmentsByIds($segmentIds);
            }
        }
        return $segmentIds;
    }
}
