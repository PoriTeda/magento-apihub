<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\ResourceModel;

/**
 * Customer segment resource model
 *
 * @api
 * @since 100.0.2
 */
class Segment extends \Magento\Rule\Model\ResourceModel\AbstractResource
{
    /**
     * @var \Magento\Customer\Model\Config\Share
     */
    protected $_configShare;

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Helper
     */
    protected $_resourceHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Quote\Model\QueryResolver
     */
    protected $queryResolver;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    protected $resourceQuote;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param Helper $resourceHelper
     * @param \Magento\Customer\Model\Config\Share $configShare
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Quote\Model\ResourceModel\Quote $resourceQuote
     * @param \Magento\Quote\Model\QueryResolver $queryResolver
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\CustomerSegment\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Customer\Model\Config\Share $configShare,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Quote\Model\ResourceModel\Quote $resourceQuote,
        \Magento\Quote\Model\QueryResolver $queryResolver,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->_resourceHelper = $resourceHelper;
        $this->_configShare = $configShare;
        $this->dateTime = $dateTime;
        $this->resourceQuote = $resourceQuote;
        $this->queryResolver = $queryResolver;
    }

    /**
     * Store associated with rule entities information map
     *
     * @var array
     */
    protected $_associatedEntitiesMap = [
        'website' => [
            'associations_table' => 'magento_customersegment_website',
            'rule_id_field' => 'segment_id',
            'entity_id_field' => 'website_id',
        ],
        'event' => [
            'associations_table' => 'magento_customersegment_event',
            'rule_id_field' => 'segment_id',
            'entity_id_field' => 'event',
        ],
    ];

    /**
     * Segment websites table name
     *
     * @var string
     */
    protected $_websiteTable;

    /**
     * Initialize main table and table id field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_customersegment_segment', 'segment_id');
        $this->_websiteTable = $this->getTable('magento_customersegment_website');
    }

    /**
     * Add website ids to rule data after load
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        $object->setData('website_ids', (array)$this->getWebsiteIds($object->getId()));

        parent::_afterLoad($object);
        return $this;
    }

    /**
     * Match and save events.
     * Save websites associations.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $segmentId = $object->getId();

        $this->unbindRuleFromEntity($segmentId, [], 'event');
        if ($object->hasMatchedEvents()) {
            $matchedEvents = $object->getMatchedEvents();
            if (is_array($matchedEvents) && !empty($matchedEvents)) {
                $this->bindRuleToEntity($segmentId, $matchedEvents, 'event');
            }
        }

        if ($object->hasWebsiteIds()) {
            $websiteIds = $object->getWebsiteIds();
            if (!is_array($websiteIds)) {
                $websiteIds = explode(',', (string)$websiteIds);
            }
            $this->bindRuleToEntity($segmentId, $websiteIds, 'website');
        }

        parent::_afterSave($object);
        return $this;
    }

    /**
     * Delete association between customer and segment for specific segment
     *
     * @param \Magento\CustomerSegment\Model\Segment $segment
     * @return $this
     */
    public function deleteSegmentCustomers($segment)
    {
        $this->getConnection()->delete(
            $this->getTable('magento_customersegment_customer'),
            ['segment_id=?' => $segment->getId()]
        );
        return $this;
    }

    /**
     * Save customer Ids matched by segment SQL select on specific website
     *
     * @param \Magento\CustomerSegment\Model\Segment $segment
     * @param string $select
     * @return $this
     * @throws \Exception
     */
    public function saveCustomersFromSelect($segment, $select)
    {
        $customerTable = $this->getTable('magento_customersegment_customer');
        $connection = $this->getConnection();
        $segmentId = $segment->getId();
        $now = $this->dateTime->formatDate(time());

        $data = [];
        $count = 0;
        $stmt = $connection->query($select);
        $connection->beginTransaction();
        try {
            while ($row = $stmt->fetch()) {
                $data[] = [
                    'segment_id' => $segmentId,
                    'customer_id' => $row['entity_id'],
                    'website_id' => $row['website_id'],
                    'added_date' => $now,
                    'updated_date' => $now,
                ];
                $count++;
                if ($count % 1000 == 0) {
                    $connection->insertMultiple($customerTable, $data);
                    $data = [];
                }
            }
            if (!empty($data)) {
                $connection->insertMultiple($customerTable, $data);
            }
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }

        $connection->commit();

        return $this;
    }

    /**
     * Count customers in specified segment
     *
     * @param int $segmentId
     * @return int
     */
    public function getSegmentCustomersQty($segmentId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getTable('magento_customersegment_customer'),
            ['COUNT(DISTINCT customer_id)']
        )->where(
            'segment_id = ?',
            (int)$segmentId
        );

        return (int)$connection->fetchOne($select);
    }

    /**
     * Aggregate customer/segments relations by matched segment conditions
     *
     * @param \Magento\CustomerSegment\Model\Segment $segment
     * @return $this
     * @throws \Exception
     */
    public function aggregateMatchedCustomers($segment)
    {
        $connection = $this->getConnection();

        $connection->beginTransaction();
        try {
            $this->deleteSegmentCustomers($segment);
            $this->processConditions($segment);
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }

        $connection->commit();

        return $this;
    }

    /**
     * @param \Magento\CustomerSegment\Model\Segment $segment
     * @return $this
     * @throws \Exception
     */
    protected function processConditions($segment)
    {
        $websiteIds = $segment->getWebsiteIds();
        $relatedCustomers = [];
        if (!empty($websiteIds)) {
            $relatedCustomers = $this->getRelatedCustomers($segment, $websiteIds);
        }
        $this->saveMatchedCustomer($relatedCustomers, $segment);
        return $this;
    }

    /**
     * Retrieve customers that where matched by segment and website id
     *
     * @param \Magento\CustomerSegment\Model\Segment $segment
     * @param array $websiteIds
     * @return array
     */
    private function getRelatedCustomers($segment, $websiteIds)
    {
        $relatedCustomers = [];
        $customerIds = [];
        foreach ($websiteIds as $websiteId) {
            if ($this->_configShare->isGlobalScope() && empty($customerIds)) {
                $customerIds = $segment->getConditions()->getSatisfiedIds(null);
            } elseif ($this->_configShare->isWebsiteScope()) {
                $customerIds = $segment->getConditions()->getSatisfiedIds($websiteId);
            }
            //get customers ids that satisfy conditions
            foreach ($customerIds as $customerId) {
                $relatedCustomers[] = [
                    'entity_id' => $customerId,
                    'website_id' => $websiteId,
                ];
            }
        }
        return $relatedCustomers;
    }

    /**
     * @param array $relatedCustomers
     * @param \Magento\CustomerSegment\Model\Segment $segment
     * @return $this
     * @throws \Exception
     */
    protected function saveMatchedCustomer($relatedCustomers, $segment)
    {
        $connection = $this->getConnection();
        $customerTable = $this->getTable('magento_customersegment_customer');
        $segmentId = $segment->getId();
        $now = $this->dateTime->formatDate(time());
        $data = [];
        $count = 0;
        $connection->beginTransaction();
        try {
            foreach ($relatedCustomers as $customer) {
                $data[] = [
                    'segment_id' => $segmentId,
                    'customer_id' => $customer['entity_id'],
                    'website_id' => $customer['website_id'],
                    'added_date' => $now,
                    'updated_date' => $now,
                ];
                $count++;
                if ($count % 1000 == 0) {
                    $connection->insertMultiple($customerTable, $data);
                    $data = [];
                }
            }
            if (!empty($data)) {
                $connection->insertMultiple($customerTable, $data);
            }
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
        $connection->commit();

        return $this;
    }

    /**
     * Get select query result
     *
     * @param \Magento\Framework\DB\Select|string $sql
     * @param array $bindParams array of bind variables
     * @return int
     */
    public function runConditionSql($sql, $bindParams)
    {
        return $this->getConnection()->fetchOne($sql, $bindParams);
    }

    /**
     * Get empty select object
     *
     * @return \Magento\Framework\DB\Select
     */
    public function createSelect()
    {
        return $this->getConnection()->select();
    }

    /**
     * Quote parameters into condition string
     *
     * @param string $string
     * @param string|array $param
     * @return string
     */
    public function quoteInto($string, $param)
    {
        return $this->getConnection()->quoteInto($string, $param);
    }

    /**
     * Get comparison condition for rule condition operator which will be used in SQL query
     * depending of database we using
     *
     * @param string $operator
     * @return string
     */
    public function getSqlOperator($operator)
    {
        return $this->_resourceHelper->getSqlOperator($operator);
    }

    /**
     * Create string for select "where" condition based on field name, comparison operator and field value
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function createConditionSql($field, $operator, $value)
    {
        if (!is_array($value)) {
            $prepareValues = explode(',', $value);
            if (count($prepareValues) <= 1) {
                $value = $prepareValues[0];
            } else {
                $value = [];
                foreach ($prepareValues as $val) {
                    $value[] = trim($val);
                }
            }
        }

        /*
         * substitute "equal" operator with "is one of" if compared value is not single
         */
        if ((is_array($value) || $value instanceof \Countable)
            && count($value) != 1
            && in_array($operator, ['==', '!='])
        ) {
            $operator = $operator == '==' ? '()' : '!()';
        }
        $sqlOperator = $this->getSqlOperator($operator);
        $condition = '';

        switch ($operator) {
            case '{}':
            case '!{}':
                if (is_array($value)) {
                    if (!empty($value)) {
                        $condition = [];
                        foreach ($value as $val) {
                            $condition[] = $this->getConnection()->quoteInto(
                                $field . ' ' . $sqlOperator . ' ?',
                                '%' . $val . '%'
                            );
                        }
                        $condition = implode(' AND ', $condition);
                    }
                } else {
                    $condition = $this->getConnection()->quoteInto(
                        $field . ' ' . $sqlOperator . ' ?',
                        '%' . $value . '%'
                    );
                }
                break;
            case '()':
            case '!()':
                if (is_array($value) && !empty($value)) {
                    $condition = $this->getConnection()->quoteInto($field . ' ' . $sqlOperator . ' (?)', $value);
                }
                break;
            case '[]':
            case '![]':
                if (is_array($value) && !empty($value)) {
                    $conditions = [];
                    foreach ($value as $v) {
                        $conditions[] = $this->getConnection()->prepareSqlCondition(
                            $field,
                            ['finset' => $this->getConnection()->quote($v)]
                        );
                    }
                    $condition = sprintf('(%s)%s', join(' OR ', $conditions), $operator == '[]' ? '>0' : '=0');
                } else {
                    if ($operator == '[]') {
                        $condition = $this->getConnection()->prepareSqlCondition(
                            $field,
                            ['finset' => $this->getConnection()->quote($value)]
                        );
                    } else {
                        $condition = 'NOT (' . $this->getConnection()->prepareSqlCondition(
                            $field,
                            ['finset' => $this->getConnection()->quote($value)]
                        ) . ')';
                    }
                }
                break;
            case 'finset':
            case '!finset':
                $condition = $this->prepareFindInSetCondition($field, $operator, $value);
                break;
            case 'between':
                $condition = $field . ' ' . sprintf(
                    $sqlOperator,
                    $this->getConnection()->quote($value['start']),
                    $this->getConnection()->quote($value['end'])
                );
                break;
            default:
                $condition = $this->getConnection()->quoteInto($field . ' ' . $sqlOperator . ' ?', $value);
                break;
        }
        return $condition;
    }

    /**
     * Prepare SQL condition for 'finset' pseudo-operator
     *
     * 'finset' pseudo-operator is required to correctly processing multiple select's values
     * in case of '==' or '!=' operators selected for comparison operation.
     *
     * @param string $field
     * @param string $operator
     * @param array|string $value
     * @return string
     */
    private function prepareFindInSetCondition($field, $operator, $value)
    {
        $condition = '';
        if (is_array($value)) {
            $conditions = [];
            foreach ($value as $v) {
                $sqlCondition = $this->getConnection()->prepareSqlCondition(
                    $field,
                    ['finset' => $this->getConnection()->quote($v)]
                );
                $sqlCondition .= ($operator == 'finset' ? '>0' : '=0');
                $conditions[] = $sqlCondition;
            }
            if ($operator == 'finset') {
                $condition = join(' AND ', $conditions)
                    . ' AND '
                    . strlen(implode(',', $value)) . '=' . $this->getConnection()->getLengthSql($field);
            } else {
                $condition = join(' OR ', $conditions)
                    . ' OR '
                    . strlen(implode(',', $value)) . '<>' . $this->getConnection()->getLengthSql($field);
            }
        }
        return $condition;
    }

    /**
     * Save all website Ids associated to specified segment
     *
     * @param \Magento\Framework\Model\AbstractModel|\Magento\CustomerSegment\Model\Segment $segment
     * @return $this
     * after 1.11.2.0 use $this->bindRuleToEntity() instead
     */
    protected function _saveWebsiteIds($segment)
    {
        if ($segment->hasWebsiteIds()) {
            $websiteIds = $segment->getWebsiteIds();
            if (!is_array($websiteIds)) {
                $websiteIds = explode(',', (string)$websiteIds);
            }
            $this->bindRuleToEntity($segment->getId(), $websiteIds, 'website');
        }

        return $this;
    }

    /**
     * Get Active Segments By Ids
     *
     * @param int[] $segmentIds
     * @return int[]
     */
    public function getActiveSegmentsByIds($segmentIds)
    {
        $activeSegmentsIds = [];
        if (count($segmentIds)) {
            $connection = $this->getConnection();
            $select = $connection->select()->from(
                $this->getMainTable(),
                ['segment_id']
            )->where(
                'segment_id IN (?)',
                $segmentIds
            )->where(
                'is_active = 1'
            );

            $segmentsList = $connection->fetchAll($select);
            foreach ($segmentsList as $segment) {
                $activeSegmentsIds[] = $segment['segment_id'];
            }
        }
        return $activeSegmentsIds;
    }
}
