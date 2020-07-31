<?php


namespace Nestle\Migration\Model\DataMigration;


use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Setup\Declaration\Schema\Sharding;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Db;
use Zend_Db_Statement_Exception;

/**
 * Class AbstractDataMigration
 * @package Nestle\Migration\Model\DataMigration
 */
class AbstractDataMigration
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var Sharding
     */
    protected $sharding;
    /**
     * @var
     */
    protected $columnDefinitions;

    /**
     * AbstractDataMigration constructor.
     * @param Sharding $sharding
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Sharding $sharding,
        ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
        $this->sharding = $sharding;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        return $this;
    }

    /**
     * @param $tableName
     * @param $resource
     * @return mixed
     */
    protected function readColumns($tableName, $resource)
    {
        $key = $tableName . "|" . $resource;
        if (!isset($this->columnDefinitions[$key])) {
            $adapter = $this->resourceConnection->getConnection($resource);
            $dbName = $this->resourceConnection->getSchemaName($resource);
            $stmt = $adapter->select()
                            ->from(
                                'information_schema.COLUMNS',
                                [
                                    'name'       => 'COLUMN_NAME',
                                    'default'    => 'COLUMN_DEFAULT',
                                    'type'       => 'DATA_TYPE',
                                    'nullable'   => new Expression('IF(IS_NULLABLE="YES", true, false)'),
                                    'definition' => 'COLUMN_TYPE',
                                    'extra'      => 'EXTRA',
                                    'comment'    => new Expression('IF(COLUMN_COMMENT="", NULL, COLUMN_COMMENT)')
                                ]
                            )
                            ->where('TABLE_SCHEMA = ?', $dbName)
                            ->where('TABLE_NAME = ?', $tableName)
                            ->order('ORDINAL_POSITION ASC');

            $this->columnDefinitions[$key] = $adapter->fetchAssoc($stmt);
        }

        return $this->columnDefinitions[$key];
    }

    /**
     * @param $tableName
     * @param $resource
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    protected function readConstraints($tableName, $resource)
    {
        $adapter = $this->resourceConnection->getConnection($resource);
        $condition = sprintf('`Non_unique` = 0');
        $sql = sprintf('SHOW INDEXES FROM %s WHERE %s', $tableName, $condition);
        $stmt = $adapter->query($sql);

        // Use FETCH_NUM so we are not dependent on the CASE attribute of the PDO connection
        $constraintsDefinition = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        return $constraintsDefinition;
    }
}
