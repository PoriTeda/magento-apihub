<?php

namespace Riki\RmaWithoutGoods\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        $setup->startSetup();

        $connection = $setup->getConnection();

        $table = $setup->getTable('magento_rma');

        $connection->addColumn($table, 'is_without_goods', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            'default'   =>  0,
            'comment' => 'Is return without goods?',
        ]);

        $setup->endSetup();
    }

}
