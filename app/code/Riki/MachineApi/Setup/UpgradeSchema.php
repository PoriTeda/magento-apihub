<?php
// @codingStandardsIgnoreFile
namespace Riki\MachineApi\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
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

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            /*create new table machine_skus*/
            $tbl = $installer->getConnection()->newTable($installer->getTable('riki_machine_skus'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    10,
                    ['identity' => true, 'primary' => true, 'unsigned' => true, 'nullable' => false],
                    'Entity id'
                )
                ->addColumn(
                    'machine_type_code',
                    Table::TYPE_TEXT,
                    255,
                    ['unsigned' => true, 'nullable' => false],
                    'Machine type code'
                )
                ->addColumn(
                    'skus',
                    Table::TYPE_TEXT,
                    255,
                    ['unsigned' => true,'nullable' => false],
                    'Skus'
                )
                ->addColumn(
                    'priority',
                    Table::TYPE_INTEGER,
                    10,
                    ['unsigned' => true, 'nullable' => false],
                    'Priority'
                )
                ->addColumn(
                    'created_date',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Created date'
                )
                ->addColumn(
                    'updated_date',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Updated Date'
                );
            $installer->getConnection()->createTable($tbl);
            /*Create new table machine_customer */
            $tbl = $installer->getConnection()->newTable($installer->getTable('riki_machine_customer'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    10,
                    ['identity' => true, 'primary' => true, 'unsigned' => true, 'nullable' => false],
                    'Entity ID'
                )
                ->addColumn(
                    'customer_id',
                    Table::TYPE_TEXT,
                    255,
                    ['unsigned' => true, 'nullable' => false],
                    'Customer consumer DB '
                )
                ->addColumn(
                    'machine_type_code',
                    Table::TYPE_TEXT,
                    255,
                    ['unsigned' => true],
                    'Machine type code'
                )
                ->addColumn(
                    'sku',
                    Table::TYPE_TEXT,
                    255,
                    ['unsigned' => true, 'nullable' => false],
                    'SKU'
                )
                ->addColumn(
                    'status',
                    Table::TYPE_TEXT,
                    255,
                    ['unsigned' => true, 'nullable' => false],
                    'Status'
                )->addColumn(
                    'created_date',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Created date'
                )
                ->addColumn(
                    'updated_date',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Updated Date'
                );
            $installer->getConnection()->createTable($tbl);

        }
        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            $connection = $this->_setupHelper->getSalesConnection();
            $connection->changeColumn(
                'riki_machine_customer',
                'customer_id',
                'consumer_db_id',
                ['type' => Table::TYPE_TEXT, 'length'=>'255', 'default' => '', 'comment' => 'Customer consumer DB ']
            );
            $connection->changeColumn(
                'riki_machine_skus',
                'skus',
                'sku',
                ['type' => Table::TYPE_TEXT, 'length'=>'255', 'default' => '', 'comment' => 'Product SKU']
            );
            /*Create new table machine_condition */
            $tbl = $installer->getConnection()->newTable($installer->getTable('riki_machine_condition'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    10,
                    ['identity' => true, 'primary' => true, 'unsigned' => true, 'nullable' => false],
                    'Entity ID'
                )
                ->addColumn(
                    'machine_code',
                    Table::TYPE_TEXT,
                    255,
                    ['unsigned' => true],
                    'Machine type code'
                )
                ->addColumn(
                    'course_code',
                    Table::TYPE_TEXT,
                    null,
                    ['unsigned' => true, 'nullable' => false],
                    'Subscription course code'
                )
                ->addColumn(
                    'frequency',
                    Table::TYPE_TEXT,
                    null,
                    ['unsigned' => true, 'nullable' => false],
                    'Subscription frequency'
                )
                ->addColumn(
                    'category_id',
                    Table::TYPE_TEXT,
                    255,
                    ['unsigned' => true, 'nullable' => false],
                    'Category'
                )
                ->addColumn(
                    'qty_min',
                    Table::TYPE_INTEGER,
                    11,
                    ['unsigned' => true, 'nullable' => false],
                    'Minimum purchase quantity'
                )
                ->addColumn(
                    'threshold',
                    Table::TYPE_FLOAT,
                    11,
                    ['unsigned' => true, 'nullable' => false],
                    'Threshold'
                )
                ->addColumn(
                    'created_date',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Created date'
                )
                ->addColumn(
                    'updated_date',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Updated Date'
                );
            $installer->getConnection()->createTable($tbl);
        }

        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            $connection = $this->_setupHelper->getSalesConnection();
            $connection->addColumn('riki_machine_skus', 'wbs', [
                'type' => Table::TYPE_TEXT,
                'length'=>'255',
                'comment' => 'Wbs'
            ]);
        }

        //set index key for mm_order RIM-1876
        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            $connection = $this->_setupHelper->getSalesConnection();
            if($connection->tableColumnExists('sales_order', 'mm_order_id')){
                $connection->changeColumn(
                    'sales_order', 'mm_order_id', 'mm_order_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length'    =>  255,
                        'comment' => 'data webapi payment-information',
                    ]
                );
                $connection->addIndex(
                    'sales_order',
                    $connection->getIndexName('sales_order', ['mm_order_id' ], true),
                    ['mm_order_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                );
            }
        }

        //set index key - RIM-2254
        if (version_compare($context->getVersion(), '1.0.5') < 0) {
            $connection = $this->_setupHelper->getSalesConnection();

            $table = 'riki_machine_customer';
            $connection->dropIndex($table, $installer->getIdxName($table, ['consumer_db_id','machine_type_code' ]));
            $connection->dropIndex($table, $installer->getIdxName($table, ['consumer_db_id','status' ]));

            $connection->addIndex(
                'riki_machine_customer',
                $connection->getIndexName('riki_machine_customer', ['consumer_db_id','machine_type_code' ], true),
                ['consumer_db_id','machine_type_code'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
            );

            $connection->addIndex(
                'riki_machine_customer',
                $connection->getIndexName('riki_machine_customer', ['consumer_db_id','status' ], true),
                ['consumer_db_id','status'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
            );

        }
        //set index key - RIM-2254
        if (version_compare($context->getVersion(), '1.0.6') < 0) {
            $salesConnection = $this->_setupHelper->getSalesConnection();
            if ($salesConnection->tableColumnExists('riki_machine_skus', 'wbs')) {
                $salesConnection->dropColumn('riki_machine_skus', 'wbs');
            }
            $defaultConnection = $this->_setupHelper->getDefaultConnection();
            if (!$defaultConnection->tableColumnExists('riki_machine_condition', 'wbs')) {
                $defaultConnection->addColumn('riki_machine_condition', 'wbs', [
                    'type' => Table::TYPE_TEXT,
                    'length'=>'255',
                    'comment' => 'Wbs'
                ]);
            }

        }

        if (version_compare($context->getVersion(), '1.0.7') < 0) {
            $salesConnection = $this->_setupHelper->getSalesConnection();
            $tbl = $salesConnection->newTable($installer->getTable('subscription_course_machine_type'))
                ->addColumn(
                    'type_id',
                    Table::TYPE_INTEGER,
                    10,
                    ['identity' => true, 'primary' => true, 'unsigned' => true, 'nullable' => false],
                    'Entity id'
                )
                ->addColumn(
                    'type_code',
                    Table::TYPE_TEXT,
                    25,
                    ['unsigned' => true, 'nullable' => false],
                    'Subscription course machine type code'
                )
                ->addIndex(
                    $installer->getIdxName(
                        $installer->getTable('subscription_course_machine_type'),
                        ['type_code'],
                        \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    ['type_code'],
                    ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                )
                ->addColumn(
                    'type_name',
                    Table::TYPE_TEXT,
                    255,
                    ['unsigned' => true,'nullable' => false],
                    'Subscription course machine type name'
                )
                ->addColumn(
                    'category_error_message',
                    Table::TYPE_TEXT,
                    50,
                    ['unsigned' => true, 'nullable' => false],
                    'Category error message'
                )
                ->addColumn(
                    'created_date',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Created date'
                )
                ->addColumn(
                    'updated_date',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Updated Date'
                );
            $salesConnection->createTable($tbl);
            /*Create new table subscription_course_machine_type_product */
            $tbl = $salesConnection->newTable($installer->getTable('subscription_course_machine_type_product'))
                ->addColumn(
                    'type_id',
                    Table::TYPE_INTEGER,
                    10,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Subscription course machine type id'
                )
                ->addColumn(
                    'product_id',
                    Table::TYPE_INTEGER,
                    10,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'product entity id'
                )
                ->addColumn(
                    'is_free',
                    Table::TYPE_BOOLEAN,
                    10,
                    ['unsigned' => true],
                    'is free product'
                )
                ->addColumn(
                    'discount_percent',
                    Table::TYPE_DECIMAL,
                    '12,4',
                    ['nullable' => false, 'default' => '0.0000'],
                    'discount percent of product'
                )
                ->addColumn(
                    'wbs',
                    Table::TYPE_TEXT,
                    255,
                    ['unsigned' => true],
                    'wbs code'
                )
                ->addColumn(
                    'sort_order',
                    Table::TYPE_SMALLINT,
                    6,
                    ['unsigned' => true],
                    'sort order'
                )
            ;
            $salesConnection->createTable($tbl);
            $salesConnection->addForeignKey(
                'fk_subscription_machine_type_product',
                $installer->getTable('subscription_course_machine_type_product'),
                'type_id',
                $installer->getTable('subscription_course_machine_type'),
                'type_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );
            /*Create new table subscription_course_machine_type_link */
            $tbl = $salesConnection->newTable($installer->getTable('subscription_course_machine_type_link'))
                ->addColumn(
                    'course_id',
                    Table::TYPE_INTEGER,
                    10,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Subscription course id'
                )
                ->addColumn(
                    'machine_type_id',
                    Table::TYPE_INTEGER,
                    10,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false],
                    'Subscription course machine type id'
                );
            $salesConnection->createTable($tbl);
        }
        $installer->endSetup();
    }
}
