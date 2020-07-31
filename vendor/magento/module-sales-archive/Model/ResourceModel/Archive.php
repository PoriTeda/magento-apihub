<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Model\ResourceModel;

/**
 * Archive resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Archive extends \Magento\Sales\Model\ResourceModel\EntityAbstract
{
    /**
     * Archive entities tables association
     *
     * @var $_tables array
     */
    protected $_tables = [
        \Magento\SalesArchive\Model\ArchivalList::ORDER => [
            'sales_order_grid',
            'magento_sales_order_grid_archive',
        ],
        \Magento\SalesArchive\Model\ArchivalList::INVOICE => [
            'sales_invoice_grid',
            'magento_sales_invoice_grid_archive',
        ],
        \Magento\SalesArchive\Model\ArchivalList::SHIPMENT => [
            'sales_shipment_grid',
            'magento_sales_shipment_grid_archive',
        ],
        \Magento\SalesArchive\Model\ArchivalList::CREDITMEMO => [
            'sales_creditmemo_grid',
            'magento_sales_creditmemo_grid_archive',
        ],
    ];

    /**
     * Sales archive config
     *
     * @var \Magento\SalesArchive\Model\Config
     */
    protected $_salesArchiveConfig;

    /**
     * Sales archival model list
     *
     * @var \Magento\SalesArchive\Model\ArchivalList
     */
    protected $_archivalList;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Attribute $attribute
     * @param \Magento\SalesSequence\Model\Manager $sequenceManager
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite $entityRelationComposite
     * @param \Magento\SalesArchive\Model\Config $salesArchiveConfig
     * @param \Magento\SalesArchive\Model\ArchivalList $archivalList
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite $entityRelationComposite,
        \Magento\Sales\Model\ResourceModel\Attribute $attribute,
        \Magento\SalesSequence\Model\Manager $sequenceManager,
        \Magento\SalesArchive\Model\Config $salesArchiveConfig,
        \Magento\SalesArchive\Model\ArchivalList $archivalList,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        $connectionName = null
    ) {
        $this->_salesArchiveConfig = $salesArchiveConfig;
        $this->_archivalList = $archivalList;
        $this->dateTime = $dateTime;
        parent::__construct(
            $context,
            $entitySnapshot,
            $entityRelationComposite,
            $attribute,
            $sequenceManager,
            $connectionName
        );
    }

    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
    }

    /**
     * Check archive entity existence
     *
     * @param string $archiveEntity
     * @return bool
     */
    public function isArchiveEntityExists($archiveEntity)
    {
        return isset($this->_tables[$archiveEntity]);
    }

    /**
     * Get archive entity table
     *
     * @param string $archiveEntity
     * @return false|string
     */
    public function getArchiveEntityTable($archiveEntity)
    {
        if (!$this->isArchiveEntityExists($archiveEntity)) {
            return false;
        }
        return $this->getTable($this->_tables[$archiveEntity][1]);
    }

    /**
     * Retrieve archive entity source table
     *
     * @param string $archiveEntity
     * @return false|string
     */
    public function getArchiveEntitySourceTable($archiveEntity)
    {
        if (!$this->isArchiveEntityExists($archiveEntity)) {
            return false;
        }
        return $this->getTable($this->_tables[$archiveEntity][0]);
    }

    /**
     * Checks if order already in archive
     *
     * @param int $id order id
     * @return bool
     */
    public function isOrderInArchive($id)
    {
        $ids = $this->getIdsInArchive(\Magento\SalesArchive\Model\ArchivalList::ORDER, [$id]);
        return !empty($ids);
    }

    /**
     * Retrieve entity ids in archive
     *
     * @param string $archiveEntity
     * @param array|int $ids
     * @return array
     */
    public function getIdsInArchive($archiveEntity, $ids)
    {
        if (!$this->isArchiveEntityExists($archiveEntity) || empty($ids)) {
            return [];
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $select = $this->getConnection()->select()->from(
            $this->getArchiveEntityTable($archiveEntity),
            'entity_id'
        )->where(
            'entity_id IN(?)',
            $ids
        );

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Retrieve order ids for archive
     *
     * @param array $orderIds
     * @param bool $useAge
     * @return array
     */
    public function getOrderIdsForArchive($orderIds = [], $useAge = false)
    {
        $statuses = $this->_salesArchiveConfig->getArchiveOrderStatuses();
        $archiveAge = $useAge ? $this->_salesArchiveConfig->getArchiveAge() : 0;

        if (empty($statuses)) {
            return [];
        }

        $select = $this->_getOrderIdsForArchiveSelect($statuses, $archiveAge);
        if (!empty($orderIds)) {
            $select->where('entity_id IN(?)', $orderIds);
        }
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Retrieve order ids in archive select
     *
     * @param array $statuses
     * @param int $archiveAge
     * @return \Magento\Framework\DB\Select
     */
    protected function _getOrderIdsForArchiveSelect($statuses, $archiveAge)
    {
        $connection = $this->getConnection();
        $table = $this->getArchiveEntitySourceTable(\Magento\SalesArchive\Model\ArchivalList::ORDER);
        $select = $connection->select()->from($table, 'entity_id')->where('status IN(?)', $statuses);

        if ($archiveAge) {
            // Check archive age
            $archivePeriodExpr = $connection->getDateSubSql(
                $connection->quote($this->dateTime->formatDate(true)),
                (int)$archiveAge,
                \Magento\Framework\DB\Adapter\AdapterInterface::INTERVAL_DAY
            );
            $select->where($archivePeriodExpr . ' >= updated_at');
        }

        return $select;
    }

    /**
     * Retrieve order ids for archive subselect expression
     *
     * @return \Zend_Db_Expr
     */
    public function getOrderIdsForArchiveExpression()
    {
        $statuses = $this->_salesArchiveConfig->getArchiveOrderStatuses();
        $archiveAge = $this->_salesArchiveConfig->getArchiveAge();

        if (empty($statuses)) {
            $statuses = [0];
        }
        $select = $this->_getOrderIdsForArchiveSelect($statuses, $archiveAge);
        return new \Zend_Db_Expr($select);
    }

    /**
     * Move records to from regular grid tables to archive
     *
     * @param string $archiveEntity
     * @param string $conditionField
     * @param array $conditionValue
     * @return $this
     */
    public function moveToArchive($archiveEntity, $conditionField, $conditionValue)
    {
        if (!$this->isArchiveEntityExists($archiveEntity)) {
            return $this;
        }
        $connection = $this->getConnection();
        $sourceTable = $this->getArchiveEntitySourceTable($archiveEntity);
        $targetTable = $this->getArchiveEntityTable($archiveEntity);

        $insertFields = array_intersect(
            array_keys($connection->describeTable($targetTable)),
            array_keys($connection->describeTable($sourceTable))
        );

        $fieldCondition = $connection->quoteIdentifier($conditionField) . ' IN(?)';
        $select = $connection->select()->from($sourceTable, $insertFields)->where($fieldCondition, $conditionValue);

        $connection->query($select->insertFromSelect($targetTable, $insertFields, true));
        return $this;
    }

    /**
     * Remove regords from source grid table
     *
     * @param string $archiveEntity
     * @param string $conditionField
     * @param array $conditionValue
     * @return $this
     */
    public function removeFromGrid($archiveEntity, $conditionField, $conditionValue)
    {
        if (!$this->isArchiveEntityExists($archiveEntity)) {
            return $this;
        }
        $connectionMock = $this->getConnection();
        $sourceTable = $this->getArchiveEntitySourceTable($archiveEntity);
        $targetTable = $this->getArchiveEntityTable($archiveEntity);
        $sourceResource = $this->_archivalList->getResource($archiveEntity);
        if ($conditionValue instanceof \Zend_Db_Expr) {
            $select = $connectionMock->select();
            // Remove order grid records moved to archive
            $select->from($targetTable, $sourceResource->getIdFieldName());
            $condition = $connectionMock->quoteInto(
                $sourceResource->getIdFieldName() . ' IN(?)',
                new \Zend_Db_Expr($select)
            );
        } else {
            $fieldCondition = $connectionMock->quoteIdentifier($conditionField) . ' IN(?)';
            $condition = $connectionMock->quoteInto($fieldCondition, $conditionValue);
        }

        $connectionMock->delete($sourceTable, $condition);
        return $this;
    }

    /**
     * Remove records from archive
     *
     * @param string $archiveEntity
     * @param string $conditionField
     * @param null $conditionValue
     * @return $this
     */
    public function removeFromArchive($archiveEntity, $conditionField = '', $conditionValue = null)
    {
        if (!$this->isArchiveEntityExists($archiveEntity)) {
            return $this;
        }
        $connection = $this->getConnection();
        $sourceTable = $this->getArchiveEntityTable($archiveEntity);
        $targetTable = $this->getArchiveEntitySourceTable($archiveEntity);
        $sourceResource = $this->_archivalList->getResource($archiveEntity);

        $insertFields = array_intersect(
            array_keys($connection->describeTable($targetTable)),
            array_keys($connection->describeTable($sourceTable))
        );

        $selectFields = $insertFields;

        $updatedAtIndex = array_search('updated_at', $selectFields);
        if ($updatedAtIndex !== false) {
            unset($selectFields[$updatedAtIndex]);
            unset($insertFields[$updatedAtIndex]);
            $insertFields[] = 'updated_at';
            $selectFields['updated_at'] = new \Zend_Db_Expr('current_timestamp()');
        }

        $select = $connection->select()->from($sourceTable, $selectFields);

        if (!empty($conditionField)) {
            $select->where($connection->quoteIdentifier($conditionField) . ' IN(?)', $conditionValue);
        }

        $connection->query($select->insertFromSelect($targetTable, $insertFields, true));
        if ($conditionValue instanceof \Zend_Db_Expr) {
            $select->reset()->from($targetTable, $sourceResource->getIdFieldName());
            // Remove order grid records from archive
            $condition = $connection->quoteInto(
                $sourceResource->getIdFieldName() . ' IN(?)',
                new \Zend_Db_Expr($select)
            );
        } elseif (!empty($conditionField)) {
            $condition = $connection->quoteInto(
                $connection->quoteIdentifier($conditionField) . ' IN(?)',
                $conditionValue
            );
        } else {
            $condition = '';
        }

        $connection->delete($sourceTable, $condition);
        return $this;
    }

    /**
     * Removes orders from archive and restore in orders grid tables,
     * returns restored order ids
     *
     * @param array $orderIds
     * @throws \Exception
     * @return array
     */
    public function removeOrdersFromArchiveById($orderIds)
    {
        $this->beginTransaction();
        try {
            foreach ($this->_archivalList->getEntityNames() as $entity) {
                $conditionalField = 'order_id';
                if ($entity === \Magento\SalesArchive\Model\ArchivalList::ORDER) {
                    $conditionalField = 'entity_id';
                }

                $entityIds = $this->getIdsInArchive(
                    $entity,
                    $orderIds
                );

                if (!empty($entityIds)) {
                    $this->removeFromArchive(
                        $entity,
                        $conditionalField,
                        $orderIds
                    );
                }
            }
            $this->commit();
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }

        return $orderIds;
    }

    /**
     * Update grid records
     *
     * @param string $archiveEntity
     * @param array $ids
     * @return $this
     */
    public function updateGridRecords($archiveEntity, $ids)
    {
        if (!$this->isArchiveEntityExists($archiveEntity) || empty($ids)) {
            return $this;
        }

        /* @var $resource \Magento\Framework\Model\ResourceModel\Db\AbstractDb */
        $resource = $this->_archivalList->getResource($archiveEntity);

        $gridColumns = array_keys(
            $this->getConnection()->describeTable($this->getArchiveEntityTable($archiveEntity))
        );

        $columnsToSelect = [];

        $select = $resource->getUpdateGridRecordsSelect($ids, $columnsToSelect, $gridColumns, true);

        $this->getConnection()->query(
            $select->insertFromSelect($this->getArchiveEntityTable($archiveEntity), $columnsToSelect, true)
        );

        return $this;
    }

    /**
     * Find related to order entity ids for checking of new items in archive
     *
     * @param string $archiveEntity
     * @param array $ids
     * @return array
     */
    public function getRelatedIds($archiveEntity, $ids)
    {
        if (empty($archiveEntity) || empty($ids)) {
            return [];
        }

        /** @var $resource \Magento\Framework\Model\ResourceModel\Db\AbstractDb */
        $resource = $this->_archivalList->getResource($archiveEntity);

        $select = $this->getConnection()->select()->from(
            ['main_table' => $resource->getMainTable()],
            'entity_id'
        )->joinInner(
            // Filter by archived order
            ['order_archive' => $this->getArchiveEntityTable('order')],
            'main_table.order_id = order_archive.entity_id',
            []
        )->where(
            'main_table.entity_id IN(?)',
            $ids
        );

        return $this->getConnection()->fetchCol($select);
    }
}
