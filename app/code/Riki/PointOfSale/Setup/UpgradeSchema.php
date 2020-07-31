<?php
// @codingStandardsIgnoreFile
namespace Riki\PointOfSale\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var \Riki\PointOfSale\Model\DataMigration
     */
    protected $migrateData;

    /**
     * UpgradeSchema constructor.
     * @param \Riki\PointOfSale\Model\DataMigration $dataMigration
     */
    public function __construct(
        \Riki\PointOfSale\Model\DataMigration $dataMigration
    )
    {
        $this->migrateData = $dataMigration;
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $table = $setup->getTable('pointofsale');
        if (version_compare($context->getVersion(), '2.0.1') < 0) {
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'holyday_setting_saturday_enable',
                    ['type' => Table::TYPE_SMALLINT, 5, 'default' => 0, 'comment' => 'Holidays on Saturdays']
                );
                $setup->getConnection()->addColumn(
                    $table, 'holyday_setting_sundays_enable',
                    ['type' => Table::TYPE_SMALLINT, 5, 'default' => 0, 'comment' => 'Holidays on Sundays']
                );
                $setup->getConnection()->addColumn(
                    $table, 'specific_holidays',
                    ['type' => Table::TYPE_TEXT, 'default' => null, 'comment' => 'Specific Holidays']
                );
            }
        }
        if (version_compare($context->getVersion(), '2.0.2') < 0) {
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'deliverytype_enable_list',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'Dellivery Type Setting']
                );
            }
        }
        if (version_compare($context->getVersion(), '2.0.3') < 0) {
            $setup->getConnection()->addColumn(
                $table, 'sap_code',
                ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'SAP Code']
            );
        }
        if (version_compare($context->getVersion(), '2.0.4') < 0) {
            $table = 'pointofsale';
            $column = 'inventory_assignation_rules';
            $connection = $setup->getConnection();

            if($connection->isTableExists($table)){
                if($connection->tableColumnExists($table,$column)){
                    $connection->dropColumn($table,$column);
                }
            }
        }
        if (version_compare($context->getVersion(), '2.0.5') < 0) {

            $this->migrateData->importData();
        }
        $setup->endSetup();
    }
}