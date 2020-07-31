<?php
// @codingStandardsIgnoreFile
namespace Riki\Fraud\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;


class UpgradeSchema implements UpgradeSchemaInterface {

    /**
     * @var \Riki\ArReconciliation\Setup\SetupHelper
     */
    private $connectionHelper;

    /**
     * UpgradeSchema constructor.
     * @param \Riki\ArReconciliation\Setup\SetupHelper $connectionHelper
     */
    public function __construct(
        \Riki\ArReconciliation\Setup\SetupHelper $connectionHelper
    ) {
        $this->connectionHelper = $connectionHelper;
    }

    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context )
    {
        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '0.0.1') < 0) {
            $this->version001( $setup );
        }

        if (version_compare($context->getVersion(), '0.0.2') < 0) {
            $this->version002( $setup );
        }

        if (version_compare($context->getVersion(), '0.0.3') < 0) {
            $this->version003( $setup );
        }

        if (version_compare($context->getVersion(), '0.0.4') < 0) {
            $this->version004( $setup );
        }


        if (version_compare($context->getVersion(), '0.0.5') < 0) {
            $this->version005($setup);
        }

        if (version_compare($context->getVersion(), '0.0.6') < 0) {
            $this->version006($setup);
        }

        if (version_compare($context->getVersion(), '0.0.7') < 0) {
            $this->version007($setup);
        }

        if (version_compare($context->getVersion(), '0.0.8') < 0) {
            $this->version008($setup);
        }

        if (version_compare($context->getVersion(), '0.0.9') < 0) {
            $this->version009($setup);
        }


        $installer->endSetup();
    }

    private function version001( $setup )
    {
        $tableName = $setup->getTable('mst_fraud_check_rule');

        if ($setup->getConnection()->isTableExists($tableName) == true) {

            $connection = $setup->getConnection();

            if(!$connection->tableColumnExists($tableName,'send_email_to'))
            {
                $connection->addColumn(
                    $tableName,
                    'send_email_to',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Send Email To'
                    ]
                );
            }

            if(!$connection->tableColumnExists($tableName,'warning_message'))
            {
                $connection->addColumn(
                    $tableName,
                    'warning_message',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Warning message'
                    ]
                );
            }

            if(!$connection->tableColumnExists($tableName,'accumulated_type'))
            {
                $connection->addColumn(
                    $tableName,
                    'accumulated_type',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Accumulated during'
                    ]
                );
            }

            if(!$connection->tableColumnExists($tableName,'duration'))
            {
                $connection->addColumn(
                    $tableName,
                    'duration',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'nullable' => true,
                        'comment' => 'Duration'
                    ]
                );
            }
        }
    }

    private function version002( $setup )
    {
        $tableName = $setup->getTable('sales_order_grid');

        if ($setup->getConnection()->isTableExists($tableName) == true) {

            $connection = $setup->getConnection();

            if(!$connection->tableColumnExists($tableName,'fraud_score'))
            {
                $connection->addColumn(
                    $tableName,
                    'fraud_score',
                    [
                        'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment'  => 'Fraud Check Score Calculation'
                    ]
                );
            }

            if(!$connection->tableColumnExists($tableName,'fraud_status'))
            {
                $connection->addColumn(
                    $tableName,
                    'fraud_status',
                    [
                        'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment'  => 'Fraud Status'
                    ]
                );
            }

            $select = $setup->getConnection()->select();

            $select->join(
                array('order'=>$setup->getTable('sales_order')),//alias=>table_name
                $setup->getConnection()->quoteInto(
                    'order.entity_id = order_grid.entity_id', ''
                ),//join clause
                array('fraud_score' => 'fraud_score', 'fraud_status' => 'fraud_status')//fields to get
            );
            $setup->getConnection()->query(
                $select->crossUpdateFromSelect(
                    array('order_grid' => $setup->getTable('sales_order_grid'))
                )
            );
        }
    }

    private function version003( $setup )
    {
        $tableName = $setup->getTable('mst_fraud_check_rule');

        if ($setup->getConnection()->isTableExists($tableName) == true) {

            $connection = $setup->getConnection();

            if(!$connection->tableColumnExists($tableName,'email_template'))
            {
                $connection->addColumn(
                    $tableName,
                    'email_template',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'nullable' => true,
                        'comment' => 'Email template'
                    ]
                );
            }
        }
    }

    private function version004( $installer )
    {
        $table = $installer->getConnection()->newTable( $installer->getTable('riki_order_cedyna_threshold') )
            ->addColumn(
                'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,11,
                ['identity' => true, 'unsigned' => true, 'nullable' => false,
                    'primary' => true,'auto_increment' => true],
                'ID'
            )->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,11,
                [ 'nullable' => false],
                'Order Id'
            )->addColumn(
                'order_increment_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 225,
                ['nullable' => false],
                'Order Incremenet ID'
            )->addColumn(
                'order_created_from',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,255,
                ['nullable' => false ],
                'Order Created From'
            )->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,11,
                ['nullable' => false],
                'Customer Id'
            )->addColumn(
                'order_cedyna_value',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, array(12,4),
                ['nullable' => false],
                'Order Cedyna Value'
            )->addColumn(
                'return_cedyna_value',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, array(12,4),
                ['nullable' => true, 'default' => 0],
                'Order Cedyna Value'
            )->addColumn(
                'month',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 4,
                ['nullable' => false,],
                'Order Month'
            )->addColumn(
                'year',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 4,
                ['nullable' => false],
                'Order Year'
            )->addColumn(
                'is_actived',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 2,
                ['nullable' => true, 'default' => 1],
                'Is actived'
            )->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addColumn(
                'update_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                'Created At'
            )
            ->addIndex($installer->getIdxName('riki_order_cedyna_threshold', ['order_id']), 'order_id');

        $installer->getConnection()->createTable($table) ;
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function version005($setup)
    {
        $tableName = 'riki_suspected_fraud_order';
        /*@var \Magento\Framework\DB\Adapter\AdapterInterface $connection*/
        $connection = $this->connectionHelper->getSalesConnection();
        $table = $connection->newTable( $connection->getTableName($tableName) )
            ->addColumn(
                'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,11,
                ['identity' => true, 'unsigned' => true, 'nullable' => false,
                    'primary' => true,'auto_increment' => true],
                'ID'
            )->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,11,
                [ 'nullable' => false],
                'Order Id'
            )->addColumn(
                'order_increment_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 225,
                ['nullable' => false],
                'Order Incremenet ID'
            )->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,11,
                ['nullable' => true ],
                'Customer Id'
            )->addColumn(
                'customer_email',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 225,
                ['nullable' => true],
                'Customer email'
            )->addColumn(
                'change_status_suspicious',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 1,
                ['nullable' => false, 'default' => 0],
                'Change status to suspicious'
            )->addColumn(
                'send_email',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 1,
                ['nullable' => false, 'default' => 0],
                'Send warning email'
            )->addColumn(
                'user_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 11,
                ['nullable' => true],
                'User id'
            )->addColumn(
                'user_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255,
                ['nullable' => true],
                'User name'
            )->addColumn(
                'approval_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true],
                'Approval Date'
            )->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addColumn(
                'updated_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                'Updated At'
            )
            ->addIndex($setup->getIdxName($tableName, ['order_id']), 'order_id');

        $connection->createTable($table);

        $select = $connection->select()->from(
            $connection->getTableName('sales_order_grid')
        )->where("`status` = 'suspicious'");

        $order = $connection->query($select);

        while ($orderRow = $order->fetch()) {
            $bind = [
                'order_id' => $orderRow['entity_id'],
                'order_increment_id' => $orderRow['increment_id'],
                'customer_id' => $orderRow['customer_id'],
                'customer_email' => $orderRow['customer_email'],
                'change_status_suspicious' => 1,
                'send_email' => 1
            ];
            $connection->insert($connection->getTableName($tableName), $bind);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function version006($setup)
    {
        /*@var \Magento\Framework\DB\Adapter\AdapterInterface $connection*/
        $connection = $setup->getConnection();

        $tableName = $setup->getTable('riki_order_cedyna_threshold');

        if ($connection->isTableExists($tableName) == true) {

            $addColumn = 'shosha_id';
            $addColumn2 = 'is_cancelled';
            $deleteColumn = 'return_cedyna_value';
            $modifyColumn = 'order_cedyna_value';

            if (!$connection->tableColumnExists($tableName,$addColumn)) {
                $connection->addColumn(
                    $tableName, $addColumn,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'nullable' => false,
                        'comment' => 'Shosha business code',
                        'after' => 'customer_id'
                    ]
                );
            }

            if (!$connection->tableColumnExists($tableName,$addColumn2)) {
                $connection->addColumn(
                    $tableName, $addColumn2,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'nullable' => true,
                        'default' => 0,
                        'comment' => 'is Order cancelled?',
                        'after' => 'is_actived'
                    ]
                );
            }

            if ($connection->tableColumnExists($tableName,$deleteColumn)) {
                $connection->dropColumn($tableName, $deleteColumn);
            }

            if ($connection->tableColumnExists($tableName,$modifyColumn)) {
                $connection->modifyColumn($tableName, $modifyColumn, [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'nullable' => false,
                    'comment' => 'Order Cedyna Value'
                ]);
            }
        }

        $newTable = 'riki_rma_cedyna_threshold';
        $table = $connection->newTable( $connection->getTableName($newTable) )
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,11,
                ['identity' => true, 'unsigned' => true, 'nullable' => false,
                    'primary' => true,'auto_increment' => true],
                'ID'
            )->addColumn(
                'rma_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,11,
                [ 'nullable' => false],
                'Rma Entity Id'
            )->addColumn(
                'rma_increment_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 225,
                ['nullable' => false],
                'Rma Incremenet ID'
            )->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,11,
                [ 'nullable' => false],
                'Order Id'
            )->addColumn(
                'order_increment_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 225,
                ['nullable' => false],
                'Order Incremenet ID'
            )->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,11,
                ['nullable' => true ],
                'Customer Id'
            )->addColumn(
                'total_return_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, 10,
                ['nullable' => false, 'default' => 0 ],
                'Total Return Amount'
            )->addColumn(
                'total_return_amount_previous',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, 10,
                ['nullable' => false, 'default' => 0 ],
                'Total Return Amount - Previous'
            )->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addColumn(
                'updated_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                'Updated At'
            )
            ->addIndex( $setup->getIdxName( $newTable, ['rma_id']), 'rma_id');
        $connection->createTable($table);
    }

    public function version007($setup)
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection*/
        $connection = $setup->getConnection();
        $tableName = $connection->getTableName('riki_rma_cedyna_threshold');
        if ($connection->isTableExists($tableName)) {
            if ($connection->tableColumnExists($tableName, 'total_return_amount')) {
                $connection->changeColumn(
                    $tableName,
                    'total_return_amount',
                    'rma_cedyna_value',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'comment' => 'Cedyna value which will be sub to cedyna_counter of shosha'
                    ]
                );
            }
            $connection->dropColumn($tableName, 'total_return_amount_previous');
        }
    }

    public function version008($setup)
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection*/
        $connection = $setup->getConnection();
        $tableName = $connection->getTableName('mst_fraud_check_rule');
        if ($connection->isTableExists($tableName)) {
            if ($connection->tableColumnExists($tableName, 'is_active')) {
                $connection->addIndex(
                    $tableName,
                    $connection->getIndexName($tableName, ['is_active']),
                    ['is_active']
                );
            }
        }
    }

    private function version009($setup)
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection*/
        $connection = $setup->getConnection();

        $tableName = $connection->getTableName('mst_fraud_check_rule');

        if ($connection->isTableExists($tableName)) {

            $conditionsColumn = 'conditions_serialized';

            $actionsColumn = 'actions_serialized';

            if ($connection->tableColumnExists($tableName, $conditionsColumn)) {
                $connection->modifyColumn(
                    $tableName, $conditionsColumn, [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => '2M',
                        'nullable' => false,
                        'comment' => 'Conditions Serialized'
                    ]
                );
            }

            if ($connection->tableColumnExists($tableName, $actionsColumn)) {
                $connection->modifyColumn(
                    $tableName, $actionsColumn, [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => '2M',
                        'nullable' => false,
                        'comment' => 'Actions Serialized'
                    ]
                );
            }
        }
    }
}