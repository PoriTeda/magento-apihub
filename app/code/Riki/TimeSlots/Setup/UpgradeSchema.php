<?php
// @codingStandardsIgnoreFile
namespace Riki\TimeSlots\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Riki\Subscription\Model\Constant;
use Riki\Subscription\Model\ProductCart\ProductCart;
use Riki\Subscription\Model\Profile\Profile as Profile;
use Riki\SubscriptionCourse\Model\Course;


class UpgradeSchema implements UpgradeSchemaInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {

        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $table = $setup->getTable('riki_timeslots');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->run("ALTER TABLE {$table} ADD COLUMN appointed_time_slot  VARCHAR(255)
                NOT NULL AFTER id");
            }
        }

        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            $setup->getConnection()->dropColumn($setup->getTable('riki_timeslots'),'delivery_type_code');
        }

        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            $setup->getConnection()->modifyColumn(
                $setup->getTable('riki_timeslots'),
                'position',
                ['type'=> Table::TYPE_INTEGER]
            );
        }

        $setup->endSetup();
    }
}