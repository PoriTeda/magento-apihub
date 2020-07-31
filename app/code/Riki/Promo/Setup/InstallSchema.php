<?php
// @codingStandardsIgnoreFile
namespace Riki\Promo\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Add new fields to table 'amasty_ampromo_rule'
         */

        $connection = $setup->getConnection();

        if($connection->isTableExists('amasty_ampromo_rule')){
            $table = $setup->getTable('amasty_ampromo_rule');

            if(!$connection->tableColumnExists($table, 'att_visible_cart')){
                $connection->addColumn($table, 'att_visible_cart', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    null,
                    'default'   =>  1,
                    'comment' => 'Visible free gift in cart?',
                ]);
            }

            if(!$connection->tableColumnExists($table, 'att_visible_user_account')){
                $connection->addColumn($table, 'att_visible_user_account', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    null,
                    'default'   =>  1,
                    'comment' => 'Visible free gift in user account?',
                ]);
            }
        }

        $table = $setup->getTable('sales_shipment_item');

        if(!$connection->tableColumnExists($table, 'visible_user_account')){
            $connection->addColumn($table, 'visible_user_account', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                'default'   =>  1,
                'comment' => 'Visible in user account?',
            ]);
        }

        $table = $setup->getTable('sales_shipment');

        if(!$connection->tableColumnExists($table, 'visible_user_account')){
            $connection->addColumn($table, 'visible_user_account', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                'default'   =>  1,
                'comment' => 'Visible in user account?',
            ]);
        }

        $table = $setup->getTable('sales_shipment_grid');

        if(!$connection->tableColumnExists($table, 'visible_user_account')){
            $connection->addColumn($table, 'visible_user_account', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                'default'   =>  1,
                'comment' => 'Visible in user account?',
            ]);
        }

        $installer->endSetup();
    }
}
