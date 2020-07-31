<?php
// @codingStandardsIgnoreFile
namespace Riki\ShipLeadTime\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class UpgradeSchema
 * @package Riki\ShipLeadTime\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var \Riki\ShipLeadTime\Model\ImportData
     */
    protected $dataImporter;

    /**
     * UpgradeSchema constructor.
     * @param \Riki\ShipLeadTime\Model\ImportData $importData
     */
    public function __construct(
        \Riki\ShipLeadTime\Model\ImportData $importData
    )
    {
        $this->dataImporter = $importData;
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        //handle all possible upgrade versions
        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            $installer->getConnection()->dropTable($installer->getTable('riki_shipleadtime'));
            $table = $installer->getConnection()
                ->newTable($installer->getTable('riki_shipleadtime'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Shippinglead ID'
                )
                ->addColumn(
                    'pref_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Prefecture ID'
                )
                ->addColumn(
                    'shipping_lead_time',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'default' => '0'],
                    'Shipping lead time'
                )
                ->addColumn(
                    'warehouse_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false],
                    'Ware House ID'
                )
                ->addColumn(
                    'delivery_type_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true],
                    'Delivery Type'
                )->addForeignKey(
                    $installer->getFkName('riki_shipleadtime',
                        'warehouse_id', 'pointofsale', 'place_id'),
                    'warehouse_id',
                    $installer->getTable('pointofsale'),
                    'place_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->setComment('Shipping Lead Time Table');
                $installer->getConnection()->createTable($table);
        }
        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            $installer->getConnection()->dropTable($installer->getTable('riki_shipleadtime'));
            $table = $installer->getConnection()
                ->newTable($installer->getTable('riki_shipleadtime'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Shippinglead ID'
                )
                ->addColumn(
                    'pref_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Prefecture ID'
                )
                ->addColumn(
                    'shipping_lead_time',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'default' => '0'],
                    'Shipping lead time'
                )
                ->addColumn(
                    'warehouse_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false],
                    'Ware House ID'
                )
                ->addColumn(
                    'delivery_type_code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    225,
                    ['nullable' => true],
                    'Delivery Type Code'
                )->addForeignKey(
                    $installer->getFkName('riki_shipleadtime',
                        'warehouse_id', 'pointofsale', 'place_id'),
                    'warehouse_id',
                    $installer->getTable('pointofsale'),
                    'place_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->setComment('Shipping Lead Time Table');
            $installer->getConnection()->createTable($table);
        }

        // Change type warehouse_id and pref_id
        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            $installer->getConnection()->dropTable($installer->getTable('riki_shipleadtime'));
            $table = $installer->getConnection()
                ->newTable($installer->getTable('riki_shipleadtime'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Shippinglead ID'
                )
                ->addColumn(
                    'pref_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Prefecture ID'
                )
                ->addColumn(
                    'shipping_lead_time',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'default' => '0'],
                    'Shipping lead time'
                )
                ->addColumn(
                    'warehouse_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Ware House ID'
                )
                ->addColumn(
                    'delivery_type_code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => true],
                    'Delivery Type Code'
                )->setComment('Shipping Lead Time Table');
            $installer->getConnection()->createTable($table);
        }

        if (version_compare($context->getVersion(), '1.1.0') < 0) {
            $table = $setup->getTable('riki_shipleadtime');

            if($setup->tableExists($table)){
                $setup->getConnection()->addColumn(
                    $table,
                    'is_active',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'nullable' => false,
                        'default' => 1,
                        'comment' => 'Is Active '
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.1.1') < 0) {
            $table = $setup->getTable('riki_shipleadtime');

            if($setup->tableExists($table)){
                $setup->getConnection()->addColumn(
                    $table,
                    'priority',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'nullable' => true,
                        'comment' => 'Warehouse priority'
                    ]
                );
            }
        }
        //data migration first version
        if (version_compare($context->getVersion(), '1.1.2') < 0) {
            $this->dataImporter->importData();
        }
        //data migration second version
        if (version_compare($context->getVersion(), '1.1.3') < 0) {
            $setup->getConnection()->query('TRUNCATE TABLE riki_shipleadtime');
            $this->dataImporter->importData();
        }
        $installer->endSetup();
    }
}