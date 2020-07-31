<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ResourceConnections\DB\Adapter\Pdo;

use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\LoggerInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\SelectFactory;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\Pdo\MysqlFactory;

// @codingStandardsIgnoreStart

/**
 * Proxy for MySQL database adapter
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @api
 * @since 100.0.2
 */
class MysqlProxy extends Mysql
// @codingStandardsIgnoreEnd
{
    const CONFIG_MAX_ALLOWED_LAG = 'maxAllowedLag';

    /**
     * @var bool
     */
    protected $masterConnectionOnly = false;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected $masterConnection;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected $slaveConnection;

    /**
     * @var array
     */
    protected $masterConfig;

    /**
     * @var array
     */
    protected $slaveConfig;

    /**
     * All possible write statements
     * First 3 symbols for each statement
     *
     * @var string[]
     */
    protected $writeQueryPrefixes = ['del', 'upd', 'ins', 'loc', 'set'];

    /**
     * @var MysqlFactory
     * @since 100.2.0
     */
    protected $mysqlFactory;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param SelectFactory $selectFactory
     * @param array $config
     * @param MysqlFactory|null $mysqlFactory
     */
    public function __construct(
        LoggerInterface $logger,
        SelectFactory $selectFactory,
        array $config,
        MysqlFactory $mysqlFactory = null
    ) {
        $this->logger = $logger;
        $this->selectFactory = $selectFactory;
        $this->mysqlFactory = $mysqlFactory ?: ObjectManager::getInstance()->get(MysqlFactory::class);
        if (!isset($config['slave'])) {
            $this->masterConfig = $config;
            $this->setUseMasterConnection();
        } else {
            $this->masterConfig = $config;
            unset($this->masterConfig['slave']);
            $this->slaveConfig = array_merge($this->masterConfig, $config['slave']);
        }
    }

    /**
     * Set master connection
     *
     * @return $this
     */
    public function setUseMasterConnection()
    {
        $this->masterConnectionOnly = true;
        return $this;
    }

    /**
     * Return master connection
     *
     * @return \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected function getMasterConnection()
    {
        if (!isset($this->masterConnection)) {
            $this->masterConnection = $this->mysqlFactory->create(
                Mysql::class,
                $this->masterConfig,
                $this->logger,
                $this->selectFactory
            );
        }
        return $this->masterConnection;
    }

    /**
     * Return slave connection
     *
     * @return \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected function getSlaveConnection()
    {
        if (!isset($this->slaveConnection)) {
            $this->slaveConnection = $this->mysqlFactory->create(
                Mysql::class,
                $this->slaveConfig,
                $this->logger,
                $this->selectFactory
            );
            if (!empty($this->slaveConfig[self::CONFIG_MAX_ALLOWED_LAG])) {
                $maxLag = (float)$this->slaveConfig[self::CONFIG_MAX_ALLOWED_LAG];
                $slaveStatus = $this->slaveConnection->fetchRow('SHOW SLAVE STATUS');
                if (!empty($slaveStatus['Seconds_Behind_Master'])
                    && (float)$slaveStatus['Seconds_Behind_Master'] >= $maxLag
                ) {
                    unset($this->slaveConnection);
                    $this->setUseMasterConnection();
                    return $this->getMasterConnection();
                }
            }
        }
        return $this->slaveConnection;
    }

    /**
     * Check that this is read only query
     *
     * @param string|\Magento\Framework\DB\Select $sql The SQL statement with placeholders.
     * @return bool
     */
    protected function isReadOnlyQuery($sql)
    {
        $sql = ltrim(preg_replace('/\s+/', ' ', $sql));
        $sqlMessage = explode(' ', $sql, 3);
        $startSql = strtolower(substr($sqlMessage[0], 0, 3));
        return
            !in_array($startSql, $this->writeQueryPrefixes) &&
            !in_array($startSql, $this->_ddlRoutines);
    }

    /**
     * Select defined connection
     *
     * @param string|\Magento\Framework\DB\Select $sql The SQL statement with placeholders.
     *
     * @return \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected function selectConnection($sql = null)
    {
        if (!$this->masterConnectionOnly &&
            ($sql === null || $this->isReadOnlyQuery($sql))
        ) {
            return $this->getSlaveConnection();
        }
        $this->setUseMasterConnection();
        return $this->getMasterConnection();
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->beginTransaction();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->commit();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->rollBack();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionLevel()
    {
        return $this->getMasterConnection()->getTransactionLevel();
    }

    /**
     * {@inheritdoc}
     */
    public function convertDate($date)
    {
        return $this->selectConnection()->convertDate($date);
    }

    /**
     * {@inheritdoc}
     */
    public function convertDateTime($datetime)
    {
        return $this->selectConnection()->convertDateTime($datetime);
    }

    /**
     * {@inheritdoc}
     */
    public function rawQuery($sql)
    {
        return $this->selectConnection($sql)->rawQuery($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function rawFetchRow($sql, $field = null)
    {
        return $this->selectConnection($sql)->rawFetchRow($sql, $field);
    }

    /**
     * {@inheritdoc}
     */
    public function query($sql, $bind = [])
    {
        return $this->selectConnection($sql)->query($sql, $bind);
    }

    /**
     * {@inheritdoc}
     */
    public function multiQuery($sql, $bind = [])
    {
        return $this->selectConnection($sql)->multiQuery($sql, $bind);
    }

    /**
     * {@inheritdoc}
     */
    public function proccessBindCallback($matches)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->proccessBindCallback($matches);
    }

    /**
     * {@inheritdoc}
     */
    public function setQueryHook($hook)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->setQueryHook($hook);
    }

    /**
     * {@inheritdoc}
     */
    public function dropForeignKey($tableName, $fkName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->dropForeignKey($tableName, $fkName, $schemaName);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function purgeOrphanRecords(
        $tableName,
        $columnName,
        $refTableName,
        $refColumnName,
        $onDelete = AdapterInterface::FK_ACTION_CASCADE
    ) {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->purgeOrphanRecords(
            $tableName,
            $columnName,
            $refTableName,
            $refColumnName,
            $onDelete
        );
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function tableColumnExists($tableName, $columnName, $schemaName = null)
    {
        return $this->selectConnection()->tableColumnExists($tableName, $columnName, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function addColumn($tableName, $columnName, $definition, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->addColumn($tableName, $columnName, $definition, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function dropColumn($tableName, $columnName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->dropColumn($tableName, $columnName, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function changeColumn(
        $tableName,
        $oldColumnName,
        $newColumnName,
        $definition,
        $flushData = false,
        $schemaName = null
    ) {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->changeColumn(
            $tableName,
            $oldColumnName,
            $newColumnName,
            $definition,
            $flushData,
            $schemaName
        );
    }

    /**
     * {@inheritdoc}
     */
    public function modifyColumn($tableName, $columnName, $definition, $flushData = false, $schemaName = null)
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->modifyColumn($tableName, $columnName, $definition, $flushData, $schemaName);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function showTableStatus($tableName, $schemaName = null)
    {
        return $this->selectConnection()->showTableStatus($tableName, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateTable($tableName, $schemaName = null)
    {
        return $this->getMasterConnection()->getCreateTable($tableName, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function getForeignKeys($tableName, $schemaName = null)
    {
        return $this->getMasterConnection()->getForeignKeys($tableName, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function getForeignKeysTree()
    {
        return $this->getMasterConnection()->getForeignKeysTree();
    }

    /**
     * {@inheritdoc}
     */
    public function modifyTables($tables)
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->modifyTables($tables);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexList($tableName, $schemaName = null)
    {
        return $this->getMasterConnection()->getIndexList($tableName, $schemaName);
    }

    /**
     * Creates and returns a new \Magento\Framework\DB\Select object for this adapter.
     *
     * @return Select
     */
    public function select()
    {
        return $this->selectFactory->create($this);
    }

    /**
     * {@inheritdoc}
     */
    public function quoteInto($text, $value, $type = null, $count = null)
    {
        return $this->selectConnection()->quoteInto($text, $value, $type, $count);
    }

    /**
     * {@inheritdoc}
     */
    public function loadDdlCache($tableCacheKey, $ddlType)
    {
        return $this->selectConnection()->loadDdlCache($tableCacheKey, $ddlType);
    }

    /**
     * {@inheritdoc}
     */
    public function saveDdlCache($tableCacheKey, $ddlType, $data)
    {
        $this->selectConnection()->saveDdlCache($tableCacheKey, $ddlType, $data);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function resetDdlCache($tableName = null, $schemaName = null)
    {
        $this->selectConnection()->resetDdlCache($tableName, $schemaName);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function disallowDdlCache()
    {
        $this->selectConnection()->disallowDdlCache();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function allowDdlCache()
    {
        $this->selectConnection()->allowDdlCache();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function describeTable($tableName, $schemaName = null)
    {
        return $this->getMasterConnection()->describeTable($tableName, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnCreateByDescribe($columnData)
    {
        return $this->getMasterConnection()->getColumnCreateByDescribe($columnData);
    }

    /**
     * {@inheritdoc}
     */
    public function createTableByDdl($tableName, $newTableName)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->createTableByDdl($tableName, $newTableName);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyColumnByDdl($tableName, $columnName, $definition, $flushData = false, $schemaName = null)
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->modifyColumnByDdl($tableName, $columnName, $definition, $flushData, $schemaName);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function changeTableEngine($tableName, $engine, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->changeTableEngine($tableName, $engine, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function changeTableComment($tableName, $comment, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->changeTableComment($tableName, $comment, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function insertForce($table, array $bind)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->insertForce($table, $bind);
    }

    /**
     * {@inheritdoc}
     */
    public function insertOnDuplicate($table, array $data, array $fields = [])
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->insertOnDuplicate($table, $data, $fields);
    }

    /**
     * {@inheritdoc}
     */
    public function insertMultiple($table, array $data)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->insertMultiple($table, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function insertArray($table, array $columns, array $data, $strategy = 0)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->insertArray($table, $columns, $data, $strategy);
    }

    /**
     * {@inheritdoc}
     */
    public function setCacheAdapter(FrontendInterface $cacheAdapter)
    {
        $this->selectConnection()->setCacheAdapter($cacheAdapter);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function newTable($tableName = null, $schemaName = null)
    {
        return $this->getMasterConnection()->newTable($tableName, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function createTable(Table $table)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->createTable($table);
    }

    /**
     * {@inheritdoc}
     */
    public function createTemporaryTable(\Magento\Framework\DB\Ddl\Table $table)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->createTemporaryTable($table);
    }

    /**
     * {@inheritdoc}
     */
    public function createTemporaryTableLike($temporaryTableName, $originTableName, $ifNotExists = false)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->createTemporaryTableLike(
            $temporaryTableName,
            $originTableName,
            $ifNotExists
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renameTablesBatch(array $tablePairs)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->renameTablesBatch($tablePairs);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnDefinitionFromDescribe($options, $ddlType = null)
    {
        return $this->getMasterConnection()->getColumnDefinitionFromDescribe($options, $ddlType);
    }

    /**
     * {@inheritdoc}
     */
    public function dropTable($tableName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->dropTable($tableName, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function dropTemporaryTable($tableName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->dropTemporaryTable($tableName, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function truncateTable($tableName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->truncateTable($tableName, $schemaName);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isTableExists($tableName, $schemaName = null)
    {
        return $this->selectConnection()->isTableExists($tableName, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function renameTable($oldTableName, $newTableName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->renameTable($oldTableName, $newTableName, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function addIndex(
        $tableName,
        $indexName,
        $fields,
        $indexType = AdapterInterface::INDEX_TYPE_INDEX,
        $schemaName = null
    ) {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->addIndex($tableName, $indexName, $fields, $indexType, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function dropIndex($tableName, $keyName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->dropIndex($tableName, $keyName, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function addForeignKey(
        $fkName,
        $tableName,
        $columnName,
        $refTableName,
        $refColumnName,
        $onDelete = AdapterInterface::FK_ACTION_CASCADE,
        $purge = false,
        $schemaName = null,
        $refSchemaName = null
    ) {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->addForeignKey(
            $fkName,
            $tableName,
            $columnName,
            $refTableName,
            $refColumnName,
            $onDelete,
            $purge,
            $schemaName,
            $refSchemaName
        );
    }

    /**
     * {@inheritdoc}
     */
    public function formatDate($date, $includeTime = true)
    {
        return $this->selectConnection()->formatDate($date, $includeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function startSetup()
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->startSetup();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function endSetup()
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->endSetup();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareSqlCondition($fieldName, $condition)
    {
        return $this->selectConnection()->prepareSqlCondition($fieldName, $condition);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareColumnValue(array $column, $value)
    {
        return $this->selectConnection()->prepareColumnValue($column, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getCheckSql($expression, $true, $false)
    {
        return $this->selectConnection()->getCheckSql($expression, $true, $false);
    }

    /**
     * {@inheritdoc}
     */
    public function getIfNullSql($expression, $value = 0)
    {
        return $this->selectConnection()->getIfNullSql($expression, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getCaseSql($valueName, $casesResults, $defaultValue = null)
    {
        return $this->selectConnection()->getCaseSql($valueName, $casesResults, $defaultValue);
    }

    /**
     * {@inheritdoc}
     */
    public function getConcatSql(array $data, $separator = null)
    {
        return $this->selectConnection()->getConcatSql($data, $separator);
    }

    /**
     * {@inheritdoc}
     */
    public function getLengthSql($string)
    {
        return $this->selectConnection()->getLengthSql($string);
    }

    /**
     * {@inheritdoc}
     */
    public function getLeastSql(array $data)
    {
        return $this->selectConnection()->getLeastSql($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getGreatestSql(array $data)
    {
        return $this->selectConnection()->getGreatestSql($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getDateAddSql($date, $interval, $unit)
    {
        return $this->selectConnection()->getDateAddSql($date, $interval, $unit);
    }

    /**
     * {@inheritdoc}
     */
    public function getDateSubSql($date, $interval, $unit)
    {
        return $this->selectConnection()->getDateSubSql($date, $interval, $unit);
    }

    /**
     * {@inheritdoc}
     */
    public function getDateFormatSql($date, $format)
    {
        return $this->selectConnection()->getDateFormatSql($date, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatePartSql($date)
    {
        return $this->selectConnection()->getDatePartSql($date);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubstringSql($stringExpression, $pos, $len = null)
    {
        return $this->selectConnection()->getSubstringSql($stringExpression, $pos, $len);
    }

    /**
     * {@inheritdoc}
     */
    public function getStandardDeviationSql($expressionField)
    {
        return $this->selectConnection()->getStandardDeviationSql($expressionField);
    }

    /**
     * {@inheritdoc}
     */
    public function getDateExtractSql($date, $unit)
    {
        return $this->selectConnection()->getDateExtractSql($date, $unit);
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName($tableName)
    {
        return $this->selectConnection()->getTableName($tableName);
    }

    /**
     * {@inheritdoc}
     */
    public function getTriggerName($tableName, $time, $event)
    {
        return $this->selectConnection()->getTriggerName($tableName, $time, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexName($tableName, $fields, $indexType = '')
    {
        return $this->selectConnection()->getIndexName($tableName, $fields, $indexType);
    }

    /**
     * {@inheritdoc}
     */
    public function getForeignKeyName($priTableName, $priColumnName, $refTableName, $refColumnName)
    {
        return $this->selectConnection()->getForeignKeyName(
            $priTableName,
            $priColumnName,
            $refTableName,
            $refColumnName
        );
    }

    /**
     * {@inheritdoc}
     */
    public function disableTableKeys($tableName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->disableTableKeys($tableName, $schemaName);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enableTableKeys($tableName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        $this->getMasterConnection()->enableTableKeys($tableName, $schemaName);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function insertFromSelect(Select $select, $table, array $fields = [], $mode = false)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->insertFromSelect($select, $table, $fields, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function selectsByRange($rangeField, \Magento\Framework\DB\Select $select, $stepCount = 100)
    {
        return $this->selectConnection()->selectsByRange($rangeField, $select, $stepCount);
    }

    /**
     * {@inheritdoc}
     */
    public function updateFromSelect(Select $select, $table)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->updateFromSelect($select, $table);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFromSelect(Select $select, $table)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->deleteFromSelect($select, $table);
    }

    /**
     * {@inheritdoc}
     */
    public function getTablesChecksum($tableNames, $schemaName = null)
    {
        return $this->selectConnection()->getTablesChecksum($tableNames, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function supportStraightJoin()
    {
        return $this->selectConnection()->supportStraightJoin();
    }

    /**
     * {@inheritdoc}
     */
    public function orderRand(Select $select, $field = null)
    {
        $this->selectConnection()->orderRand($select, $field);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function forUpdate($sql)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->forUpdate($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryKeyName($tableName, $schemaName = null)
    {
        return $this->selectConnection()->getPrimaryKeyName($tableName, $schemaName);
    }

    /**
     * {@inheritdoc}
     */
    public function decodeVarbinary($value)
    {
        return $this->selectConnection()->decodeVarbinary($value);
    }

    /**
     * {@inheritdoc}
     */
    public function createTrigger(\Magento\Framework\DB\Ddl\Trigger $trigger)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->createTrigger($trigger);
    }

    /**
     * {@inheritdoc}
     */
    public function dropTrigger($triggerName, $schemaName = null)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->dropTrigger($triggerName, $schemaName);
    }

    /**
     * Destroy connections with calling their destructor
     *
     * @return void
     */
    public function __destruct()
    {
        unset($this->slaveConnection);
        unset($this->masterConnection);
    }

    /**
     * {@inheritdoc}
     */
    public function getTables($likeCondition = null)
    {
        return $this->selectConnection()->getTables($likeCondition);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuoteIdentifierSymbol()
    {
        return $this->selectConnection()->getQuoteIdentifierSymbol();
    }

    /**
     * {@inheritdoc}
     */
    public function listTables()
    {
        return $this->selectConnection()->listTables();
    }

    /**
     * {@inheritdoc}
     */
    public function limit($sql, $count, $offset = 0)
    {
        return $this->selectConnection($sql)->limit($sql, $count, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return $this->selectConnection()->isConnected();
    }

    /**
     * {@inheritdoc}
     */
    public function closeConnection()
    {
        $this->selectConnection()->closeConnection();
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($sql)
    {
        return $this->selectConnection($sql)->prepare($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($tableName = null, $primaryKey = null)
    {
        return $this->selectConnection()->lastInsertId($tableName, $primaryKey);
    }

    /**
     * {@inheritdoc}
     */
    public function exec($sql)
    {
        return $this->selectConnection($sql)->exec($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function setFetchMode($mode)
    {
        $this->selectConnection()->setFetchMode($mode);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsParameters($type)
    {
        return $this->selectConnection()->supportsParameters($type);
    }

    /**
     * {@inheritdoc}
     */
    public function getServerVersion()
    {
        return $this->selectConnection()->getServerVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->selectConnection()->getConnection();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->selectConnection()->getConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function setProfiler($profiler)
    {
        if ($this->slaveConfig !== null) {
            $this->getSlaveConnection()->setProfiler($profiler);
        }
        return $this->getMasterConnection()->setProfiler($profiler);
    }

    /**
     * {@inheritdoc}
     */
    public function getProfiler()
    {
        return $this->selectConnection()->getProfiler();
    }

    /**
     * {@inheritdoc}
     */
    public function getStatementClass()
    {
        return $this->selectConnection()->getStatementClass();
    }

    /**
     * {@inheritdoc}
     */
    public function setStatementClass($class)
    {
        return $this->selectConnection()->setStatementClass($class);
    }

    /**
     * {@inheritdoc}
     */
    public function insert($table, array $bind)
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->insert($table, $bind);
    }

    /**
     * {@inheritdoc}
     */
    public function update($table, array $bind, $where = '')
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->update($table, $bind, $where);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($table, $where = '')
    {
        $this->setUseMasterConnection();
        return $this->getMasterConnection()->delete($table, $where);
    }

    /**
     * {@inheritdoc}
     */
    public function getFetchMode()
    {
        return $this->selectConnection()->getFetchMode();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($sql, $bind = [], $fetchMode = null)
    {
        return $this->selectConnection()->fetchAll($sql, $bind, $fetchMode);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchRow($sql, $bind = [], $fetchMode = null)
    {
        return $this->selectConnection()->fetchRow($sql, $bind, $fetchMode);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAssoc($sql, $bind = [])
    {
        return $this->selectConnection()->fetchAssoc($sql, $bind);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchCol($sql, $bind = [])
    {
        return $this->selectConnection()->fetchCol($sql, $bind);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchPairs($sql, $bind = [])
    {
        return $this->selectConnection()->fetchPairs($sql, $bind);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOne($sql, $bind = [])
    {
        return $this->selectConnection()->fetchOne($sql, $bind);
    }

    /**
     * {@inheritdoc}
     */
    public function quote($value, $type = null)
    {
        return $this->selectConnection()->quote($value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function quoteIdentifier($ident, $auto = false)
    {
        return $this->selectConnection()->quoteIdentifier($ident, $auto);
    }

    /**
     * {@inheritdoc}
     */
    public function quoteColumnAs($ident, $alias, $auto = false)
    {
        return $this->selectConnection()->quoteColumnAs($ident, $alias, $auto);
    }

    /**
     * {@inheritdoc}
     */
    public function quoteTableAs($ident, $alias = null, $auto = false)
    {
        return $this->selectConnection()->quoteTableAs($ident, $alias, $auto);
    }

    /**
     * {@inheritdoc}
     */
    public function lastSequenceId($sequenceName)
    {
        return $this->selectConnection()->lastSequenceId($sequenceName);
    }

    /**
     * {@inheritdoc}
     */
    public function nextSequenceId($sequenceName)
    {
        return $this->selectConnection()->nextSequenceId($sequenceName);
    }

    /**
     * {@inheritdoc}
     */
    public function foldCase($key)
    {
        return $this->selectConnection()->foldCase($key);
    }

    /**
     * {@inheritdoc}
     * @since 100.1.0
     */
    public function getAutoIncrementField($tableName, $schemaName = null)
    {
        $indexName = $this->getMasterConnection()->getPrimaryKeyName($tableName, $schemaName);
        $indexes = $this->getMasterConnection()->getIndexList($tableName);
        if ($indexName && count($indexes[$indexName]['COLUMNS_LIST']) == 1) {
            return current($indexes[$indexName]['COLUMNS_LIST']);
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaListener()
    {
        return parent::getSchemaListener();
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function __wakeup()
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    protected function _connect()
    {
        return;
    }
}
