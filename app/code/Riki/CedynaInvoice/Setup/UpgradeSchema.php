<?php
namespace Riki\CedynaInvoice\Setup;

use Magento\Framework\DB\Ddl\Table;
use Riki\Sales\Helper\ConnectionHelper;

/**
 * Class UpgradeSchema
 * @package Riki\CedynaInvoice\Setup
 */
class UpgradeSchema extends \Riki\Framework\Setup\Version\Schema implements
    \Magento\Framework\Setup\UpgradeSchemaInterface

{
    /**
     * @var ConnectionHelper
     */
    protected $connectionHelper;

    /**
     * UpgradeSchema constructor.
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param ConnectionHelper $connectionHelper
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        ConnectionHelper $connectionHelper
    ) {
        parent::__construct($functionCache, $logger, $resourceConnection, $deploymentConfig);
        $this->connectionHelper = $connectionHelper;
    }
    /**
     * Upgrade schema version 1.0.1
     */
    public function version101()
    {
        $tableName = 'riki_cedyna_invoice';
        $columnName = 'import_date';
        $connection = $this->getConnection($tableName);
        if ($connection->isTableExists($tableName)) {
            if (!$connection->tableColumnExists($tableName, $columnName)) {
                $connection->addColumn(
                    $tableName,
                    $columnName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        null,
                        'after'     => 'qty',
                        'comment' => 'Invoice import date',
                    ]
                );
            }
        }
    }
    /**
     * Upgrade schema version 1.0.2
     */
    public function version102()
    {
        //remove old table
        $tableName = 'riki_cedyna_invoice';
        $defaultConnection = $this->connectionHelper->getDefaultConnection();
        $salesConnection = $this->connectionHelper->getSalesConnection();
        if ($defaultConnection->isTableExists($tableName)) {
            $defaultConnection->dropTable($tableName);
        }
        if ($salesConnection->isTableExists($tableName)) {
            $salesConnection->dropTable($tableName);
        }
        // create new table on sales database
        $table = $salesConnection->newTable(
            $salesConnection->getTableName($tableName)
        );

        $table->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        )->addColumn(
            'import_month',
            Table::TYPE_TEXT,
            6,
            ['nullable' => false],
            'Import month'
        )->addColumn(
            'target_month',
            Table::TYPE_TEXT,
            6,
            ['nullable' => false],
            'Target month'
        )->addColumn(
            'business_code',
            Table::TYPE_TEXT,
            40,
            ['nullable' => false],
            'Business code'
        )->addColumn(
            'shipped_out_date',
            Table::TYPE_DATE,
            null,
            ['nullable' => false],
            'Shipped out date'
        )->addColumn(
            'data_type',
            Table::TYPE_TEXT,
            2,
            ['nullable' => false],
            'Data Type'
        )->addColumn(
            'row_total',
            Table::TYPE_DECIMAL,
            '12,4',
            ['nullable' => false],
            'Row total'
        )->addColumn(
            'shipment_increment_id',
            Table::TYPE_TEXT,
            50,
            ['nullable' => true],
            'Shipment number'
        )->addColumn(
            'product_line_name',
            Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Product line name'
        )->addColumn(
            'unit_price',
            Table::TYPE_DECIMAL,
            '12,4',
            ['nullable' => true],
            'Unit price'
        )->addColumn(
            'qty',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Quantity'
        )->addColumn(
            'import_date',
            Table::TYPE_DATE,
            null,
            ['nullable' => true],
            'Invoice import date'
        )->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['default' => Table::TIMESTAMP_INIT],
            'Creation Time'
        )->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['default' => Table::TIMESTAMP_INIT_UPDATE ],
            'Updated at'
        );
        $salesConnection->createTable($table);
        $this->addIndex($tableName, ['id']);
    }
    /**
     * Upgrade schema version 1.0.3
     */
    public function version103()
    {
        $tableName = 'riki_cedyna_invoice';
        $columnName = 'import_month';
        $connection = $this->connectionHelper->getSalesConnection();
        if ($connection->isTableExists($tableName)) {
            if (!$connection->tableColumnExists($tableName, $columnName)) {
                $connection->addColumn(
                    $tableName,
                    $columnName,
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 6,
                        'nullable' => false,
                        'after'     => 'id',
                        'comment' => 'Import month',
                    ]
                );
            }
        }
    }

    /**
     * Upgrade schema version 1.0.4
     */
    public function version104()
    {
        $tableName = 'riki_cedyna_invoice';
        $columnName = 'shipment_increment_id';
        $connection = $this->connectionHelper->getSalesConnection();
        if ($connection->isTableExists($tableName)) {
            if ($connection->tableColumnExists($tableName, $columnName)) {
                $connection->changeColumn(
                    $tableName,
                    $columnName,
                    'increment_id',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length'=>50,
                        'nullable' => false,
                        'comment' => 'Increment ID',
                    ]
                );
            }
            $columnName = 'customer_id';
            if (!$connection->tableColumnExists($tableName, $columnName)) {
                $connection->addColumn(
                    $tableName,
                    $columnName,
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => 10,
                        'nullable' => false,
                        'unsigned' => true,
                        'after'     => 'import_date',
                        'comment' => 'Customer ID',
                    ]
                );
            }
            $columnName = 'riki_nickname';
            if (!$connection->tableColumnExists($tableName, $columnName)) {
                $connection->addColumn(
                    $tableName,
                    $columnName,
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'after'     => 'customer_id',
                        'comment' => 'Riki NickName',
                    ]
                );
            }
            $columnName = 'order_id';
            if (!$connection->tableColumnExists($tableName, $columnName)) {
                $connection->addColumn(
                    $tableName,
                    $columnName,
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => 10,
                        'nullable' => false,
                        'unsigned' => true,
                        'after'     => 'customer_id',
                        'comment' => 'Order Entity ID',
                    ]
                );
            }
            $columnName = 'order_created_date';
            if (!$connection->tableColumnExists($tableName, $columnName)) {
                $connection->addColumn(
                    $tableName,
                    $columnName,
                    [
                        'type' => Table::TYPE_TIMESTAMP,
                        'length' => null,
                        'nullable' => true,
                        'after'     => 'order_id',
                        'comment' => 'Order created date',
                    ]
                );
            }
        }
    }
    public function version105()
    {
        $tableName = 'riki_cedyna_invoice';
        $columnName = 'returned_date';
        $connection = $this->connectionHelper->getSalesConnection();
        if ($connection->isTableExists($tableName)) {
            if (!$connection->tableColumnExists($tableName, $columnName)) {
                $connection->addColumn(
                    $tableName,
                    $columnName,
                    [
                        'type' => Table::TYPE_DATE,
                        'nullable' => true,
                        'after' => 'shipped_out_date',
                        'comment' => 'Returned date',
                    ]
                );
            }
        }
    }
}
