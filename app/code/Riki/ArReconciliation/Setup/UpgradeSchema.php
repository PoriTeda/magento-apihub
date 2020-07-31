<?php
// @codingStandardsIgnoreFile
namespace Riki\ArReconciliation\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface {

    /**
     * @var \Riki\ArReconciliation\Setup\SetupHelper
     */
    protected $_setupHelper;

    /**
     * InstallSchema constructor.
     * @param SetupHelper $setupHelper
     */
    public function __construct(
        \Riki\ArReconciliation\Setup\SetupHelper $setupHelper
    ){
        $this->_setupHelper = $setupHelper;
    }

    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context )
    {
        $setup->startSetup();

        $connection = $this->_setupHelper->getSalesConnection();

        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            $this->version103($connection);
        }

        if (version_compare($context->getVersion(), '1.0.7') < 0) {
            $this->version107($connection);
        }

        if (version_compare($context->getVersion(), '1.0.8') < 0) {
            $this->version108($connection);
        }

        if (version_compare($context->getVersion(), '1.0.9') < 0) {
            $this->version109($connection);
        }

        if (version_compare($context->getVersion(), '1.1.0') < 0) {
            $this->version110($connection);
        }

        if (version_compare($context->getVersion(), '1.1.1') < 0) {
            $this->version111($connection);
        }

        if (version_compare($context->getVersion(), '1.1.2') < 0) {
            $this->version112($connection);
        }

        if (version_compare($context->getVersion(), '1.1.3') < 0) {
            $this->version113($connection);
        }

        if (version_compare($context->getVersion(), '1.1.4') < 0) {
            $this->version114($connection);
        }

        if (version_compare($context->getVersion(), '1.1.5') < 0) {
            $this->version115($connection);
        }

        if (version_compare($context->getVersion(), '1.1.6') < 0) {
            $this->version116($connection);
        }

        if (version_compare($context->getVersion(), '1.1.7') < 0) {
            $this->version117($connection);
        }

        if (version_compare($context->getVersion(), '1.1.8') < 0) {
            $this->version118($connection);
        }

        if (version_compare($context->getVersion(), '1.1.9') < 0) {
            $this->version119($connection);
        }

        if (version_compare($context->getVersion(), '1.2.0') < 0) {
            $this->version120($connection);
        }

        if (version_compare($context->getVersion(), '1.2.1') < 0) {
            $this->version121($connection);
        }

        if (version_compare($context->getVersion(), '1.2.2') < 0) {
            $this->version122($connection);
        }

        if (version_compare($context->getVersion(), '1.2.3') < 0) {
            $this->version123($connection);
        }

        if (version_compare($context->getVersion(), '1.2.4') < 0) {
            $this->version124($connection);
        }

        $setup->endSetup();
    }

    private function version103($connection)
    {
        /*
         * create table to store change log for sales_shipment
         * nestle_payment_date, nestle_payment_amount
         */

        $table = $connection->newTable('riki_shipment_log')
            ->addColumn(
                'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,10,
                ['identity' => true, 'unsigned' => true, 'nullable' => false,
                    'primary' => true,'auto_increment' => true],
                'ID'
            )->addColumn(
                'user_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,null,
                [ 'nullable' => false],
                'User Id'
            )->addColumn(
                'nestle_payment_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, array( 12,4 ) ,
                ['nullable' => true],
                'Amount'
            )->addColumn(
                'nestle_payment_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,null,
                ['nullable' => false ],
                'date'
            )->addColumn(
                'log',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,null,
                ['nullable' => TRUE],
                'amount and date before change'
            )->addColumn(
                'type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,225,
                ['nullable' => TRUE],
                'change by manually or auto import'
            )->addColumn(
                'created',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,null,
                ['nullable' => false ],
                'Created at'
            );

        $connection->createTable($table) ;
    }

    private function version107( $connection )
    {
        $tableName = 'sales_shipment';

        if ( $connection->isTableExists($tableName ) == true) {

            $connection->addColumn(
                $tableName,
                'nestle_payment_date',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                    'nullable' => true,
                    'comment' => 'nestle payment date'
                ]
            );

            $connection->addColumn(
                $tableName,
                'nestle_payment_amount',
                'decimal(12,4) default NULL'
            );
        }
    }

    private function version108( $connection )
    {
        $tableName = 'riki_shipment_log';

        if ($connection->isTableExists($tableName) == true) {

            $connection->addColumn(
                $tableName,
                'shipment_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => false ,
                    'comment' => 'Shipment id',
                    'after' => 'id'
                ]
            );
        }
    }

    private function version109( $connection )
    {
        $tableName = 'riki_shipment_log';

        if ($connection->isTableExists($tableName) == true) {

            $connection->addColumn(
                $tableName,
                'user_name',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => false ,
                    'comment' => 'User name',
                    'after' => 'user_id'
                ]
            );
        }
    }

    private function version110( $connection )
    {
        $table1 = 'sales_shipment';

        if ($connection->isTableExists($table1) == true) {

            $connection->addColumn(
                $table1,
                'payment_reconciliation',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length' => 4,
                    'nullable' => false ,
                    'default' => 0,
                    'comment' => 'Nestle payment reconciliation'
                ]
            );
        }

        $table2 = 'riki_shipment_log';

        if ($connection->isTableExists($table2) == true) {

            $connection->addColumn(
                $table2,
                'payment_reconciliation',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length' => 4,
                    'nullable' => false ,
                    'default' => 0,
                    'comment' => 'Nestle payment reconciliation'
                ]
            );
        }

        $table3 = 'sales_order';

        if ($connection->isTableExists($table3) == true) {

            $connection->addColumn( $table3, 'collected_amount', 'decimal(12,4) default NULL' );

            $connection->addColumn(
                $table3,
                'collected_date',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                    'nullable' => true,
                    'comment' => 'Nestle collected money date'
                ]
            );

            $connection->addColumn(
                $table3,
                'payment_reconciliation',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length' => 4,
                    'nullable' => false ,
                    'default' => 0,
                    'comment' => 'Nestle payment reconciliation'
                ]
            );
        }

        /*create table*/

        $table5 = $connection->newTable('riki_rma_refund_log')
            ->addColumn(
                'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,10,
                [ 'identity' => true, 'unsigned' => true, 'nullable' => false,
                  'primary' => true,'auto_increment' => true],
                'ID'
            )->addColumn(
                'rma_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,11,
                [ 'nullable' => false],
                'RMA Id'
            )->addColumn(
                'user_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,null,
                [ 'nullable' => false],
                'User Id'
            )->addColumn(
                'user_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,255,
                [ 'nullable' => false],
                'User name'
            )->addColumn(
                'refund_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, array( 12,4 ) ,
                ['nullable' => true],
                'Nestle Refund Amount'
            )->addColumn(
                'refund_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATE,null,
                ['nullable' => false ],
                'Nestle Refund Date'
            )->addColumn(
                'payment_reconciliation',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 4,
                ['nullable' => true, 'default' => 0],
                'Nestle payment reconciliation'
            )->addColumn(
                'log',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,null,
                ['nullable' => TRUE],
                'amount and date before change'
            )->addColumn(
                'type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,225,
                ['nullable' => TRUE],
                'change by manually or auto import'
            )->addColumn(
                'created',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,null,
                ['nullable' => false ],
                'Created at'
            );

        $connection->createTable($table5);
    }

    private function version111( $connection )
    {
        $table6 = $connection->newTable( 'riki_order_collected_log' )
            ->addColumn(
                'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,10,
                ['identity' => true, 'unsigned' => true, 'nullable' => false,
                  'primary' => true,'auto_increment' => true],
                'ID'
            )->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,11,
                [ 'nullable' => false],
                'Orders Id'
            )->addColumn(
                'user_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,null,
                [ 'nullable' => false],
                'User Id'
            )->addColumn(
                'user_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,255,
                [ 'nullable' => false],
                'User name'
            )->addColumn(
                'collected_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, array( 12,4 ) ,
                ['nullable' => true],
                'Nestle Collected Amount'
            )->addColumn(
                'collected_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATE,null,
                ['nullable' => false ],
                'Nestle Collected Date'
            )->addColumn(
                'payment_reconciliation',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 4,
                ['nullable' => true, 'default' => 0],
                'Nestle payment reconciliation'
            )->addColumn(
                'log',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,null,
                ['nullable' => TRUE],
                'amount and date before change'
            )->addColumn(
                'type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,225,
                ['nullable' => TRUE],
                'change by manually or auto import'
            )->addColumn(
                'created',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,null,
                ['nullable' => false ],
                'Created at'
            );

        $connection->createTable($table6);
    }

    private function version112($connection)
    {
        $table4 = 'magento_rma';

        if ($connection->isTableExists($table4) == true) {

            $connection->addColumn( $table4, 'refund_amount', 'decimal(12,4) default NULL' );

            $connection->addColumn(
                $table4,
                'refund_date',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                    'nullable' => true,
                    'comment' => 'Nestle refund money date'
                ]
            );

            $connection->addColumn(
                $table4,
                'payment_reconciliation',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length' => 4,
                    'nullable' => false ,
                    'default' => 0,
                    'comment' => 'Nestle payment reconciliation'
                ]
            );
        }
    }

    private function version113( $connection )
    {
        $table6 = $connection->newTable('riki_order_refund_log')
            ->addColumn(
                'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,10,
                ['identity' => true, 'unsigned' => true, 'nullable' => false,
                    'primary' => true,'auto_increment' => true],
                'ID'
            )->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,11,
                [ 'nullable' => false],
                'Orders Id'
            )->addColumn(
                'user_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,null,
                [ 'nullable' => false],
                'User Id'
            )->addColumn(
                'user_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,255,
                [ 'nullable' => false],
                'User name'
            )->addColumn(
                'refunded_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, array( 12,4 ) ,
                ['nullable' => true],
                'Nestle Refunded Amount'
            )->addColumn(
                'refunded_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATE,null,
                ['nullable' => false ],
                'Nestle Refunded Date'
            )->addColumn(
                'payment_reconciliation',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 4,
                ['nullable' => true, 'default' => 0],
                'Nestle payment reconciliation'
            )->addColumn(
                'log',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,null,
                ['nullable' => TRUE],
                'amount and date before change'
            )->addColumn(
                'type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,225,
                ['nullable' => TRUE],
                'change by manually or auto import'
            )->addColumn(
                'created',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,null,
                ['nullable' => false ],
                'Created at'
            );

        $connection->createTable($table6);

        $table3 = 'sales_order';

        if ($connection->isTableExists($table3) == true) {

            $connection->addColumn( $table3, 'refunded_amount', 'decimal(12,4) default NULL' );

            $connection->addColumn(
                $table3,
                'refunded_date',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                    'nullable' => true,
                    'comment' => 'Nestle collected money date'
                ]
            );
        }
    }

    private function version114($connection)
    {
        $table = $connection->newTable('riki_order_payment_status_log')
            ->addColumn(
                'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,10,
                ['identity' => true, 'unsigned' => true, 'nullable' => false,
                    'primary' => true,'auto_increment' => true],
                'ID'
            )->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,11,
                [ 'nullable' => false],
                'Orders Id'
            )->addColumn(
                'user_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,null,
                [ 'nullable' => false],
                'User Id'
            )->addColumn(
                'user_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,255,
                [ 'nullable' => false],
                'User name'
            )->addColumn(
                'previous_status',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => true],
                'Previous status'
            )->addColumn(
                'payment_status',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => true],
                'Payment status'
            )->addColumn(
                'created',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,null,
                ['nullable' => false ],
                'Created at'
            );

        $connection->createTable($table);
    }

    private function version115( $connection )
    {
        $table1 = 'riki_shipment_log';

        if ($connection->isTableExists($table1) == true) {

            $connection->addColumn(
                $table1,
                'shipment_increment_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Shipment Increment Id'
                ]
            );

            $connection->addColumn(
                $table1,
                'note',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Note'
                ]
            );
        }

        $table2 = 'riki_rma_refund_log';

        if ($connection->isTableExists($table2) == true) {

            $connection->addColumn(
                $table2,
                'rma_increment_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Rma Increment Id'
                ]
            );

            $connection->addColumn(
                $table2,
                'note',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Note'
                ]
            );
        }

        $table3 = 'riki_order_collected_log';

        if ($connection->isTableExists($table3) == true) {

            $connection->addColumn(
                $table3,
                'order_increment_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Order Increment Id'
                ]
            );

            $connection->addColumn(
                $table3,
                'note',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Note'
                ]
            );
        }

        $table4 = 'riki_order_refund_log';

        if ($connection->isTableExists($table4) == true) {

            $connection->addColumn(
                $table4,
                'order_increment_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Order Increment Id'
                ]
            );

            $connection->addColumn(
                $table4,
                'note',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Note'
                ]
            );
        }
    }

    private function version116( $connection )
    {
        $table = 'riki_shipment_log';

        if ($connection->isTableExists($table) == true) {

            $connection->addColumn( $table, 'collected_amount', 'decimal(12,4) default NULL' );

            $connection->addColumn(
                $table,
                'collected_amount',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                    'nullable' => true,
                    'comment' => 'Nestle collected money date'
                ]
            );
        }
    }

    private function version117( $connection )
    {
        $table = 'riki_order_payment_status_log';

        if ($connection->isTableExists($table) == true) {

            $connection->addColumn(
                $table,
                'order_increment_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Order Increment Id'
                ]
            );
        }
    }

    private function version119( $connection ){

        $table = 'sales_shipment';
        $column = 'nestle_payment_receive_date';

        if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, $column)) {
            $connection->addColumn(
                $table, $column,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                    'nullable' => true,
                    'comment' => 'System date when Nestle payment receive date'
                ]
            );
        }

        $table2 = 'riki_shipment_log';
        $column2 = 'nestle_payment_date';

        if ($connection->isTableExists($table2)) {

            if( $connection->tableColumnExists($table2, $column2) ){
                $connection->changeColumn(
                    $table2, $column2, $column2,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Nestle payment date'
                    ]
                );
            }
        }
    }

    private function version120( $connection ){

        $table = 'magento_rma';
        $column1 = 'refund_amount';
        $newColumn1 = 'nestle_refund_amount';
        $column2 = 'refund_date';
        $newColumn2 = 'nestle_refund_date';
        $column3 = 'payment_reconciliation';
        $newColumn3 = 'nestle_payment_reconciliation';

        if ($connection->isTableExists($table)) {

            if( $connection->tableColumnExists($table, $column1) ){
                $connection->changeColumn(
                    $table, $column1, $newColumn1,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'nullable' => true,
                        'comment' => 'Nestle refund amount'
                    ]
                );
            }

            if( $connection->tableColumnExists($table, $column2) ){
                $connection->changeColumn(
                    $table, $column2, $newColumn2,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Nestle refund date'
                    ]
                );
            }

            if( $connection->tableColumnExists($table, $column3) ){
                $connection->changeColumn(
                    $table, $column3, $newColumn3,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'length' => 4,
                        'nullable' => false,
                        'default' => 0,
                        'comment' => 'Nestle payment reconciliation'
                    ]
                );
            }
        }

        $table2 = 'riki_rma_refund_log';
        $column21 = 'refund_amount';
        $newColumn21 = 'nestle_refund_amount';
        $column22 = 'refund_date';
        $newColumn22 = 'nestle_refund_date';
        $column23 = 'payment_reconciliation';
        $newColumn23 = 'nestle_payment_reconciliation';

        if ($connection->isTableExists($table2)) {

            if( $connection->tableColumnExists($table2, $column21) ){
                $connection->changeColumn(
                    $table2, $column21, $newColumn21,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'nullable' => true,
                        'comment' => 'Nestle refund amount'
                    ]
                );
            }

            if( $connection->tableColumnExists($table2, $column22) ){
                $connection->changeColumn(
                    $table2, $column22, $newColumn22,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Nestle refund date'
                    ]
                );
            }

            if( $connection->tableColumnExists($table2, $column23) ){
                $connection->changeColumn(
                    $table2, $column23, $newColumn23,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'length' => 4,
                        'nullable' => false,
                        'default' => 0,
                        'comment' => 'Nestle payment reconciliation'
                    ]
                );
            }
        }

        $table3 = 'sales_order';
        $column31 = 'collected_amount';
        $newColumn31 = 'nestle_payment_amount';
        $column32 = 'collected_date';
        $newColumn32 = 'nestle_payment_date';
        $column33 = 'payment_reconciliation';
        $newColumn33 = 'nestle_payment_reconciliation';
        $column34 = 'refunded_amount';
        $newColumn34 = 'nestle_refund_amount';
        $column35 = 'refunded_date';
        $newColumn35 = 'nestle_refund_date';

        if ($connection->isTableExists($table3)) {

            if( $connection->tableColumnExists($table3, $column31) ){
                $connection->changeColumn(
                    $table3, $column31, $newColumn31,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'nullable' => true,
                        'comment' => 'Nestle payment amount'
                    ]
                );
            }

            if( $connection->tableColumnExists($table3, $column32) ){
                $connection->changeColumn(
                    $table3, $column32, $newColumn32,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Nestle payment date'
                    ]
                );
            }

            if( $connection->tableColumnExists($table3, $column33) ){
                $connection->changeColumn(
                    $table3, $column33, $newColumn33,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'length' => 4,
                        'nullable' => false,
                        'default' => 0,
                        'comment' => 'Nestle payment reconciliation'
                    ]
                );
            }

            if( $connection->tableColumnExists($table3, $column34) ){
                $connection->changeColumn(
                    $table3, $column34, $newColumn34,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'nullable' => true,
                        'comment' => 'Nestle refund amount'
                    ]
                );
            }

            if( $connection->tableColumnExists($table3, $column35) ){
                $connection->changeColumn(
                    $table3, $column35, $newColumn35,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Nestle refund date'
                    ]
                );
            }
        }

        $table4 = 'riki_order_collected_log';
        $column41 = 'collected_amount';
        $newColumn41 = 'nestle_payment_amount';
        $column42 = 'collected_date';
        $newColumn42 = 'nestle_payment_date';
        $column43 = 'payment_reconciliation';
        $newColumn43 = 'nestle_payment_reconciliation';

        if ($connection->isTableExists($table4)) {

            if( $connection->tableColumnExists($table4, $column41) ){
                $connection->changeColumn(
                    $table4, $column41, $newColumn41,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'nullable' => true,
                        'comment' => 'Nestle payment amount'
                    ]
                );
            }

            if( $connection->tableColumnExists($table4, $column42) ){
                $connection->changeColumn(
                    $table4, $column42, $newColumn42,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Nestle payment date'
                    ]
                );
            }

            if( $connection->tableColumnExists($table4, $column43) ){
                $connection->changeColumn(
                    $table4, $column43, $newColumn43,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'length' => 4,
                        'nullable' => false,
                        'default' => 0,
                        'comment' => 'Nestle payment reconciliation'
                    ]
                );
            }
        }

        $table5 = 'riki_order_refund_log';
        $column51 = 'refunded_amount';
        $newColumn51 = 'nestle_refund_amount';
        $column52 = 'refunded_date';
        $newColumn52 = 'nestle_refund_date';
        $column53 = 'payment_reconciliation';
        $newColumn53 = 'nestle_payment_reconciliation';

        if ($connection->isTableExists($table5)) {

            if( $connection->tableColumnExists($table5, $column51) ){
                $connection->changeColumn(
                    $table5, $column51, $newColumn51,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'nullable' => true,
                        'comment' => 'Nestle refund amount'
                    ]
                );
            }

            if( $connection->tableColumnExists($table5, $column52) ){
                $connection->changeColumn(
                    $table5, $column52, $newColumn52,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Nestle refund date'
                    ]
                );
            }

            if( $connection->tableColumnExists($table5, $column53) ){
                $connection->changeColumn(
                    $table5, $column53, $newColumn53,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'length' => 4,
                        'nullable' => false,
                        'default' => 0,
                        'comment' => 'Nestle payment reconciliation'
                    ]
                );
            }
        }

        $table6 = 'sales_order_grid';
        $column6 = 'collected_date';

        if ($connection->isTableExists($table5) && $connection->tableColumnExists($table6, $column6) ) {
            $connection->dropColumn($table6, $column6);
        }
    }

    private function version121($connection)
    {
        $table1 = 'riki_shipment_log';
        $table2 = 'riki_rma_refund_log';
        $table3 = 'riki_order_collected_log';
        $table4 = 'riki_order_refund_log';

        $column = 'change_type';

        if ($connection->isTableExists($table1) && !$connection->tableColumnExists($table1, $column) ) {
            $connection->addColumn(
                $table1, $column,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'length' => 6,
                    'nullable' => false,
                    'default' => 3,
                    'comment' => 'Change type'
                ]
            );
        }

        if ($connection->isTableExists($table2) && !$connection->tableColumnExists($table2, $column) ) {
            $connection->addColumn(
                $table2, $column,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'length' => 6,
                    'nullable' => false,
                    'default' => 3,
                    'comment' => 'Change type'
                ]
            );
        }

        if ($connection->isTableExists($table3) && !$connection->tableColumnExists($table3, $column) ) {
            $connection->addColumn(
                $table3, $column,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'length' => 6,
                    'nullable' => false,
                    'default' => 3,
                    'comment' => 'Change type'
                ]
            );
        }

        if ($connection->isTableExists($table4) && !$connection->tableColumnExists($table4, $column) ) {
            $connection->addColumn(
                $table4, $column,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'length' => 6,
                    'nullable' => false,
                    'default' => 3,
                    'comment' => 'Change type'
                ]
            );
        }
    }

    /**
     * old version is 118
     *
     * @param $connection
     */
    private function version122($connection)
    {

        $table = 'riki_payment_ar_list';

        $column = 'transaction_id';

        if ($connection->isTableExists($table) && $connection->tableColumnExists($table, $column)) {
            $connection->changeColumn(
                $table, $column, $column,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'comment' => 'Transaction ID'
                ]
            );
        }

        $table2 = 'sales_shipment';
        $column2 = 'payment_reconciliation';
        $newColumns = 'nestle_payment_reconciliation';

        $column3 = 'collection_date';
        $column4 = 'nestle_payment_date';
        $column5 = 'nestle_payment_amount';

        if ($connection->isTableExists($table2)) {

            if( $connection->tableColumnExists($table2, $column2) ) {
                $connection->changeColumn(
                    $table2, $column2, $newColumns,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'size' => 4,
                        'nullable' => false,
                        'default' => 0,
                        'comment' => 'Nestle payment reconciliation'
                    ]
                );
            }

            if( !$connection->tableColumnExists($table2, $column3) ){
                $connection->addColumn(
                    $table2, $column3,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Actual payment collection date'
                    ]
                );
            }

            if( !$connection->tableColumnExists($table2, $column4) ){
                $connection->addColumn(
                    $table2, $column4,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Actual Nestle payment date'
                    ]
                );
            }

            if( !$connection->tableColumnExists($table2, $column5) ){
                $connection->addColumn( $table2, $column5, 'decimal(12,4) default NULL' );
                $connection->addColumn(
                    $table2, $column5,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => [12,4],
                        'nullable' => true,
                        'comment' => 'Nestle payment amount'
                    ]
                );
            }
        }
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     */
    private function version123($connection)
    {
        $table = $connection->getTableName('sales_shipment_track');

        $column = 'track_number';

        if ($connection->isTableExists($table) && $connection->tableColumnExists($table, $column)) {
            $connection->modifyColumn(
                $table, $column,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 100,
                    ['nullable' => true],
                    'comment' => 'Number'
                ]
            );

            $connection->addIndex(
                $table,
                $connection->getIndexName($table,['track_number']),
                ['track_number']
            );
        }
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     */
    private function version124($connection)
    {
        $table = $connection->getTableName('riki_order_collected_log');

        if (
            $connection->getTableName($table) &&
            $connection->tableColumnExists($table, 'order_id')
        ) {
            $connection->addIndex(
                $table,
                $connection->getIndexName($table, ['order_id']),
                ['order_id']
            );
        }
    }
}