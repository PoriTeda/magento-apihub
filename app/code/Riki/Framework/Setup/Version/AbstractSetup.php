<?php
/**
 * Framework
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Framework
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Framework\Setup\Version;

use Magento\Framework\App;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\DB\Adapter\AdapterInterface;

// @todo need improve long methods
/**
 * Class AbstractSetup
 *
 * @category  RIKI
 * @package   Riki\Framework\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
abstract class AbstractSetup
{
    const TABLE_CONNECTION_NAME = 'table_connection_name';
    const TABLE_NAME = 'table_name';

    /**
     * Connections
     *
     * @var array
     */
    protected $connections;

    /**
     * Setup
     *
     * @var \Magento\Framework\Module\Setup
     */
    protected $setup;

    /**
     * Connection
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;
    /**
     * DeploymentConfig
     *
     * @var \Magento\Framework\App\DeploymentConfig
     */
    protected $deploymentConfig;
    /**
     * ResourceConnection
     *
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * FunctionCache
     *
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * AbstractSetup constructor.
     *
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache      helper
     * @param \Psr\Log\LoggerInterface                   $logger             logger
     * @param App\ResourceConnection                     $resourceConnection helper
     * @param App\DeploymentConfig                       $deploymentConfig   helper
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig
    ) {
        $this->functionCache = $functionCache;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * Getter
     *
     * @return mixed
     */
    public function getSetup()
    {
        return $this->setup;
    }

    /**
     * Setter
     *
     * @param mixed $setup setup
     *
     * @return $this
     */
    public function setSetup($setup)
    {
        $this->setup = $setup;
        return $this;
    }

    /**
     * Setter
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection connection
     *
     * @return $this
     */
    public function setConnection(
        \Magento\Framework\DB\Adapter\AdapterInterface $connection
    ) {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Get config connections
     *
     * @return array
     */
    public function getConnections()
    {
        if (!$this->connections) {
            $config = $this->deploymentConfig
                ->get(ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS);
            $this->connections = array_keys($config);
        }

        return $this->connections;
    }

    /**
     * Get connection by table name
     *
     * @param null $tableName tableName
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getConnection($tableName = null)
    {
        $connectionName = ModuleDataSetupInterface::DEFAULT_SETUP_CONNECTION;
        if ($tableName) {
            if ($this->functionCache->has($tableName)) {
                $connectionName = $this->functionCache->load($tableName);
                return $this->resourceConnection->getConnection($connectionName);
            }
            foreach ($this->getConnections() as $name) {
                try {
                    $connection = $this->resourceConnection
                        ->getConnectionByName($name);
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                    continue;
                }
                if ($connection->isTableExists($tableName)) {
                    $this->functionCache->store($name, $tableName);
                    return $connection;
                }
            }
        }

        return $this->connection
            ? $this->connection
            : $this->resourceConnection->getConnection($connectionName);
    }

    /**
     * Alias of getTable
     *
     * @param string $tableName tableName
     *
     * @return string
     */
    public function table($tableName)
    {
        return $this->getTable($tableName);
    }

    /**
     * Get full-name of table
     *
     * @param string $tableName tableName
     *
     * @return mixed
     */
    public function getTable($tableName)
    {
        if ($this->functionCache->has($tableName)) {
            return $this->functionCache->load($tableName);
        }

        $result = $this->getConnection($tableName)
            ->getTableName($tableName);

        $this->functionCache->store($result, $tableName);

        return $result;
    }

    /**
     * Add a column to table
     *
     * @param string $tableName  tableName
     * @param string $columnName columnName
     * @param array  $def        def
     * @param bool   $force      force
     *
     * @return bool
     */
    public function addColumn($tableName, $columnName, $def, $force = false)
    {
        $tableName = $this->getTable($tableName);
        $conn = $this->getConnection($tableName);
        if (!$conn->isTableExists($tableName)) {
            return false;
        }

        if ($force) {
            $this->dropColumn($tableName, $columnName);
        } else if ($conn->tableColumnExists($tableName, $columnName)) {
            return false;
        }

        $conn->startSetup();
        $conn->addColumn($tableName, $columnName, $def);
        $conn->endSetup();

        return true;
    }

    /**
     * Drop a column of a table
     *
     * @param string $tableName  tableName
     * @param string $columnName columnName
     *
     * @return bool
     */
    public function dropColumn($tableName, $columnName)
    {
        $tableName = $this->getTable($tableName);
        $conn = $this->getConnection($tableName);
        if (!$conn->isTableExists($tableName)) {
            return false;
        }
        // @todo should queue action to apply start & end setup for multiple action,
        // I will do it when have time
        $conn->startSetup();
        $conn->dropColumn($tableName, $columnName);
        $conn->endSetup();

        return true;
    }

    /**
     * Change column of a table
     *
     * @param string $tableName     tableName
     * @param string $oldColumnName oldColumnName
     * @param string $newColumnName newColumnName
     * @param array  $def           def
     *
     * @return bool
     */
    public function changeColumn($tableName, $oldColumnName, $newColumnName, $def)
    {
        $tableName = $this->getTable($tableName);
        $conn = $this->getConnection($tableName);
        if (!$conn->isTableExists($tableName)) {
            return false;
        }

        if (!$conn->tableColumnExists($tableName, $oldColumnName)) {
            return false;
        }

        if ($conn->tableColumnExists($tableName, $newColumnName)) {
            return false;
        }

        $conn->startSetup();
        $conn->changeColumn($tableName, $oldColumnName, $newColumnName, $def);
        $conn->endSetup();

        return true;
    }

    /**
     * Modify column
     *
     * @param string $tableName  tableName
     * @param string $columnName columnName
     * @param array  $def        def
     *
     * @return bool
     */
    public function modifyColumn($tableName, $columnName, $def)
    {
        $tableName = $this->getTable($tableName);
        $conn = $this->getConnection($tableName);
        if (!$conn->isTableExists($tableName)) {
            return false;
        }
        if (!$conn->tableColumnExists($tableName, $columnName)) {
            return false;
        }

        $conn->startSetup();
        $conn->modifyColumn($tableName, $columnName, $def);
        $conn->endSetup();

        return true;
    }

    /**
     * Add foreign key
     *
     * @param string $tableName  tableName
     * @param string $columnName columnName
     * @param string $tableRef   tableRef
     * @param string $columnRef  columnRef
     * @param null   $action     action
     * @param null   $keyName    keyName
     *
     * @return bool
     */
    public function addForeignKey(
        $tableName,
        $columnName,
        $tableRef,
        $columnRef,
        $action = null,
        $keyName = null
    ) {
        $tableName = $this->getTable($tableName);
        $tableRef = $this->getTable($tableRef);
        $conn = $this->getConnection($tableName);
        if (!$conn->isTableExists($tableName) || !$conn->isTableExists($tableRef)) {
            return false;
        }
        if (!$keyName) {
            $keyName = $conn->getForeignKeyName(
                $tableName,
                $columnName,
                $tableRef,
                $columnRef
            );
        }
        if (!$action) {
            $action = \Magento\Framework\DB\Adapter\AdapterInterface::FK_ACTION_RESTRICT;
        }

        $conn->startSetup();
        $conn->addForeignKey(
            $keyName,
            $tableName,
            $columnName,
            $tableRef,
            $columnRef,
            $action
        );
        $conn->endSetup();

        return true;
    }

    /**
     * Drop foreign key
     *
     * @param string $tableName tableName
     * @param string $fkName    fkName
     *
     * @return bool
     */
    public function dropForeignKey($tableName, $fkName)
    {
        $tableName = $this->getTable($tableName);
        $conn = $this->getConnection($tableName);
        if (!$conn->isTableExists($tableName)) {
            return false;
        }
        if (is_array($fkName)) {
            $fkName = $conn->getForeignKeyName(...$fkName);
        }
        $conn->startSetup();
        $conn->dropForeignKey($tableName, $fkName);
        $conn->endSetup();

        return true;
    }

    /**
     * Add index
     *
     * @param string $tableName tableName
     * @param array  $columns   columns
     * @param null   $indexName indexName
     * @param string $indexType indexType
     *
     * @return bool
     */
    public function addIndex(
        $tableName,
        $columns,
        $indexName = null,
        $indexType = ''
    ) {
        $tableName = $this->getTable($tableName);
        $conn = $this->getConnection($tableName);
        if (!$conn->isTableExists($tableName)) {
            return false;
        }
        if (!$columns) {
            return false;
        }
        if (!$indexName) {
            $indexName = $conn->getIndexName($tableName, $columns, $indexType);
        }
        if (!$indexType) {
            $indexType = AdapterInterface::INDEX_TYPE_INDEX;
        }

        $conn->startSetup();
        $conn->addIndex($tableName, $indexName, $columns, $indexType);
        $conn->endSetup();

        return true;
    }

    /**
     * Drop index
     *
     * @param string $tableName tableName
     * @param string $indexName indexName
     * @param string $indexType indexType
     *
     * @return bool
     */
    public function dropIndex($tableName, $indexName, $indexType = '')
    {
        $tableName = $this->getTable($tableName);
        $conn = $this->getConnection($tableName);
        if (!$conn->isTableExists($tableName)) {
            return false;
        }

        if (is_array($indexName)) {
            $indexName = $conn->getIndexName($tableName, $indexName, $indexType);
        }

        $conn->startSetup();
        $conn->dropIndex($tableName, $indexName);
        $conn->endSetup();

        return true;
    }

    /**
     * Drop a table
     *
     * @param string $tableName tableName
     *
     * @return bool
     */
    public function dropTable($tableName)
    {
        $tableName = $this->getTable($tableName);
        $conn = $this->getConnection($tableName);
        if (!$conn->isTableExists($tableName)) {
            return false;
        }
        $conn->startSetup();
        $conn->dropTable($tableName);
        $conn->endSetup();

        return true;
    }

    /**
     * Create table
     *
     * @param string $tableName      tableName
     * @param array  $columns        columns
     * @param null   $resourceName connectionName
     *
     * @return bool|\Zend_Db_Statement_Interface
     */
    public function createTable($tableName, $columns, $resourceName = null)
    {
        if (!$columns) {
            return false;
        }
        try {
            $conn = $resourceName
                ? $this->resourceConnection->getConnection($resourceName)
                : $this->getConnection();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return false;
        }
        $tableName = $conn->getTableName($tableName);
        $table = $conn->newTable($tableName);
        foreach ($columns as $column) {
            $table->addColumn(...$column);
        }
        $conn->startSetup();
        $conn->createTable($table);
        $conn->endSetup();

        return true;
    }

    /**
     * Insert array
     *
     * @param string $tableName tableName
     * @param array  $columns   columns
     * @param array  $data      data
     *
     * @return bool
     */
    public function insertArray($tableName, $columns, $data)
    {
        $tableName = $this->getTable($tableName);
        $conn = $this->getConnection($tableName);
        if (!$conn->isTableExists($tableName)) {
            return false;
        }
        if (empty($columns) || empty($data)) {
            return false;
        }

        $conn->startSetup();
        $conn->insertArray($tableName, $columns, $data);
        $conn->endSetup();

        return true;
    }

    /**
     * Delete a/many rows by cond
     *
     * @param string $tableName tableName
     * @param string $cond      cond
     *
     * @return bool
     */
    public function delete($tableName, $cond = '')
    {
        $tableName = $this->getTable($tableName);
        $conn = $this->getConnection($tableName);
        if (!$conn->isTableExists($tableName)) {
            return false;
        }
        if (empty($cond)) {
            return false;
        }

        $conn->startSetup();
        $conn->delete($tableName, $cond);
        $conn->endSetup();

        return true;
    }

    /**
     * Execute setup/upgrade
     *
     * @param \Magento\Framework\Setup\ModuleContextInterface $context context
     * @param null                                            $setup   setup
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function execute(
        \Magento\Framework\Setup\ModuleContextInterface $context,
        $setup = null
    ) {
        if ($setup) {
            $this->setSetup($setup);
        }
        $methods = get_class_methods($this);
        natsort($methods);
        foreach ($methods as $method) {
            if (substr($method, 0, 7) != 'version') {
                continue;
            }

            $version = str_replace('version', '', $method);
            if (strlen($version) < 3 || strlen($version) > 4) {
                throw new \Exception(
                    sprintf('Method %s is incorrect format version', $method)
                );
            }
            $major = substr($version, 0, 1);
            $minor = substr($version, 1, 1);
            $patch = substr($version, 2, 2);

            if (strlen(intval($major)) != strlen($major)
                || strlen(intval($minor)) != strlen($minor)
                || strlen(intval($patch)) != strlen($patch)
            ) {
                throw new \Exception(
                    sprintf(
                        '%s is incorrect format version method',
                        get_class($this) . '::' . $method
                    )
                );
            }

            $version = $major . '.' . $minor . '.' . $patch;
            if (version_compare($context->getVersion(), $version) >= 0) {
                continue;
            }

            $this->$method();
        }
        return $this;
    }
}
