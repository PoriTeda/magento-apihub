<?php
// @codingStandardsIgnoreFile
namespace Bluecom\Paygent\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
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

    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface   $setup   SchemaSetupInterface
     * @param ModuleContextInterface $context ModuleContextInterface
     *
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();


        $tableName = $installer->getTable('sales_order_payment');
        //handle all possible upgrade versions

        if (version_compare($context->getVersion(), '2.0.1') < 0) {
            //code to upgrade to 2.0.1
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $installer->getConnection();
                $connection->addColumn(
                    $tableName,
                    'paygent_limit_date',
                    ['type' => Table::TYPE_TEXT, 'nullable' => true, 'default' => '', 'comment' => 'Paygent Limit Date']
                );
                $connection->addColumn(
                    $tableName,
                    'paygent_url',
                    ['type' => Table::TYPE_TEXT, 'nullable' => true, 'default' => '', 'comment' => 'Link to paygent']
                );
            }
        }

        if (version_compare($context->getVersion(), '2.0.5') < 0) {
            //code to upgrade to 2.0.5 ,add attribute ref_trading_id
            $tableName = $installer->getTable('sales_order');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $installer->getConnection();
                $connection->addColumn(
                    $tableName,
                    'ref_trading_id',
                    ['type' => Table::TYPE_TEXT, 'nullable' => true, 'default' => '', 'comment' => 'Paygent Reference Trading Id']
                );
            }
        }

        if (version_compare($context->getVersion(), '2.0.6') < 0) {
            $tableName = $installer->getTable('sales_order');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $installer->getConnection();
                $connection->addColumn(
                    $tableName,
                    'use_ivr',
                    ['type' => Table::TYPE_BOOLEAN, 'default' => 0, 'comment' => 'IVR Paygent Payment Method']
                );
            }
        }

        if (version_compare($context->getVersion(), '2.0.7') < 0) {
            $tableName = $installer->getTable('sales_order');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $installer->getConnection();
                $connection->addColumn(
                    $tableName,
                    'ivr_transaction',
                    ['type' => Table::TYPE_TEXT, 'nullable' => true, 'default' => '', 'comment' => 'IVR Transaction Id']
                );
            }
        }


        if (version_compare($context->getVersion(), '2.0.8') < 0) {
            /**
             * Create table 'riki_paygent_error_handling'
             */
            $table = $installer->getConnection()->newTable(
                $installer->getTable('riki_paygent_error_handling')
            )->addColumn(
                'error_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Error Id'
            )->addColumn(
                'error_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                100,
                [],
                'Response detail code'
            )->addColumn(
                'backend_message',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Backend message to store'
            )->addColumn(
                'email_message',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Message to display in the email'
            );
            $installer->getConnection()->createTable($table);
        }

        if (version_compare($context->getVersion(), '2.0.9') < 0) {
            $this->upgradeToVersion209($setup);
        }
        if (version_compare($context->getVersion(), '2.1.0') < 0) {
            $tableName = $installer->getTable('riki_authorization_timing');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $installer->getConnection();
                $connection->addColumn(
                    $tableName,
                    'authorized_number',
                    ['type' => Table::TYPE_INTEGER, 'nullable' => true, 'default' => 1, 'comment' => 'Number of authorize']
                );
            }
        }

        if (version_compare($context->getVersion(), '2.1.3') < 0) {
            /**
             * Create table 'riki_paygent_history'
             */
            $table = $installer->getConnection()->newTable(
                $installer->getTable('riki_paygent_history')
            )->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Id'
            )->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false,],
                'Customer Id'
            )->addColumn(
                'order_number',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                [],
                'Order Increment Id'
            )->addColumn(
                'profile_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false,],
                'Profile Id'
            )->addColumn(
                'trading_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false,],
                'Trading Id'
            )->addColumn(
                'used_date',
                Table::TYPE_DATETIME,
                null,
                ['unsigned' => true],
                'Used date'
            )->addColumn(
                'type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                100,
                [],
                'Type of used'
            );
            $installer->getConnection()->createTable($table);
        }

        if (version_compare($context->getVersion(), '2.1.4') < 0) {
            $tableName = $installer->getTable('riki_paygent_history');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $installer->getConnection();
                $connection->modifyColumn(
                    $tableName,
                    'trading_id',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 20,
                        'nullable' => false,
                        'comment' => 'Trading Id'
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '2.1.5') < 0) {
            /**
             * Create table 'riki_paygent_option'
             */
            $connection = $this->_setupHelper->getSalesConnection();

            $table = $connection->newTable('riki_paygent_option')
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'nullable' => false, 'primary' => true],
                    'Id'
                )->addColumn(
                    'customer_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false,],
                    'Customer Id'
                )->addColumn(
                    'option_checkout',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false, 'default' => 0],
                    'Option for redirect'
                )
                ->addColumn(
                    'link_redirect',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    ['nullable' => true,],
                    'Link redirect to paygent'
                );
            $connection->createTable($table);
        }

        if (version_compare($context->getVersion(), '2.1.6') < 0) {
            $connection = $this->_setupHelper->getSalesConnection();

            $tableName = $connection->getTableName('riki_authorization_timing');

            $connection->changeColumn(
                $tableName,
                'order_date',
                'order_date',
                [
                    'type' => Table::TYPE_TIMESTAMP
                ]
            );

            $connection->changeColumn(
                $tableName,
                're_authorization_date',
                're_authorization_date',
                [
                    'type' => Table::TYPE_TIMESTAMP
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.7') < 0) {

            $connection = $this->_setupHelper->getSalesConnection();

            $tableName = $connection->getTableName('riki_authorization_timing');

            if ($connection->isTableExists($tableName) == true) {
                $connection->addColumn(
                    $tableName,
                    'authorized_fail_number',
                    ['type' => Table::TYPE_INTEGER, 'nullable' => true, 'default' => 0, 'comment' => 'Number of authorize fail']
                );
            }
        }

        if (version_compare($context->getVersion(), '2.1.8') < 0) {

            $connection = $this->_setupHelper->getSalesConnection();

            $tableName = $connection->getTableName('riki_authorization_timing');

            if ($connection->isTableExists($tableName) == true && $connection->tableColumnExists($tableName,'authorized_fail_number')){

                $connection->dropColumn($tableName,'authorized_fail_number');
            }
        }

        if (version_compare($context->getVersion(), '2.1.9') < 0) {

            $connection = $this->_setupHelper->getSalesConnection();

            $connection->addIndex(
                'riki_paygent_history',
                $connection->getIndexName('riki_paygent_history', ['customer_id','profile_id' ], true),
                ['customer_id','profile_id']
            );
        }

        if (version_compare($context->getVersion(), '2.2.1') < 0) {
            $this->version221();
        }

        if (version_compare($context->getVersion(), '2.2.2') < 0) {
            $this->version222();
        }

        $installer->endSetup();
    }

    private function upgradeToVersion209($setup)
    {
        $table = $setup->getConnection()->newTable($setup->getTable('riki_authorization_timing'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'primary' => true, 'nullable' => false],
                'ID'
            )
            ->addColumn(
                'order_id',
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true],
                'Order ID'
            )
            ->addColumn(
                'order_date',
                Table::TYPE_DATETIME,
                null,
                ['unsigned' => true],
                'Order Date'
            )
            ->addColumn(
                'pre_order',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'default'=>'0'],
                'Order Id'
            )
            ->addColumn(
                'available_date_of_product',
                Table::TYPE_DATE,
                null,
                ['unsigned' => true],
                'Available for pre-product'
            )
            ->addColumn(
                're_authorization_date',
                Table::TYPE_DATETIME,
                null,
                ['unsigned' => true],
                'Re-authorization date'
            )
            ->addColumn(
                're_authorization_status',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => true,],
                'Re-authorization Status'
            )
            ->addForeignKey(
                $setup->getFkName('riki_authorization_timing','order_id','sales_order','entity_id'),
                'order_id',
                $setup->getTable('sales_order'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Paygent Re-authorization Timing'
            );

        $setup->getConnection()->createTable($table);
    }

    private function version221()
    {
        $connection = $this->_setupHelper->getSalesConnection();

        $table = $connection->getTableName('riki_paygent_option');

        $indexColumn = 'customer_id';

        if ($connection->tableColumnExists($table, $indexColumn)) {
            $connection->addIndex(
                $table,
                $connection->getIndexName($table,[$indexColumn]),
                [$indexColumn]
            );
        }
    }

    private function version222()
    {
        $connection = $this->_setupHelper->getSalesConnection();

        $table = $connection->getTableName('riki_paygent_history');

        $orderColumn = 'order_number';

        if ($connection->tableColumnExists($table, $orderColumn)) {
            $connection->addIndex(
                $table,
                $connection->getIndexName($table,[$orderColumn]),
                [$orderColumn]
            );

            $typeColumn = 'type';

            if ($connection->tableColumnExists($table, $typeColumn)) {
                $connection->addIndex(
                    $table,
                    $connection->getIndexName($table,[$orderColumn, $typeColumn]),
                    [$orderColumn, $typeColumn]
                );
            }
        }

        $paymentAgentColumn = 'payment_agent';

        if (!$connection->tableColumnExists($table, $paymentAgentColumn)) {
            $connection->addColumn(
                $table,
                $paymentAgentColumn,
                ['type' => Table::TYPE_TEXT, 'nullable' => true, 'default' => '', 'comment' => 'Paygent Agent']
            );
        }
    }

}