<?php
// @codingStandardsIgnoreFile
namespace Riki\AutomaticallyShipment\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.0') < 0) {            
            $installer->run(
                "
                  UPDATE `sales_order_status_state` SET is_default = 1 , visible_on_front = 1 WHERE status = 'pending_payment' AND state = 'pending_payment'
                "
            );
            $installer->run(
                "
                  UPDATE `sales_order_status_state` SET is_default = 0  WHERE status = 'processing' AND state = 'processing';
                "
            );
            $installer->run(
                "
                  INSERT INTO `sales_order_status`(`status`, `label`) VALUES ('preparing_for_shipping', 'Preparing for Shipping')
                "
            );
            $installer->run(
                "
                  INSERT INTO `sales_order_status`(`status`, `label`) VALUES ('waiting_for_shipping', 'Waiting for Shipping')
                "
            );
            $installer->run(
                "
                  INSERT INTO `sales_order_status`(`status`, `label`) VALUES ('shipped_out', 'Shipped Out')
                "
            );
            $installer->run(
                "
                  INSERT INTO `sales_order_status`(`status`, `label`) VALUES ('delivery_completed', 'Delivery Completed')
                "
            );
            $installer->run(
                "
                  INSERT INTO `sales_order_status`(`status`, `label`) VALUES ('delivered', 'Delivered')
                "
            );

            $installer->run(
                "
                  INSERT INTO `sales_order_status_state`(`status`, `state`, `is_default`, `visible_on_front`) VALUES ('preparing_for_shipping', 'processing', 1, 1)
                "
            );
            $installer->run(
                "
                  INSERT INTO `sales_order_status_state`(`status`, `state`, `is_default`, `visible_on_front`) VALUES ('waiting_for_shipping', 'processing', 0, 1)
                "
            );
            $installer->run(
                "
                  INSERT INTO `sales_order_status_state`(`status`, `state`, `is_default`, `visible_on_front`) VALUES ('shipped_out', 'processing', 0, 1)
                "
            );
            $installer->run(
                "
                  INSERT INTO `sales_order_status_state`(`status`, `state`, `is_default`, `visible_on_front`) VALUES ('delivery_completed', 'processing', 0, 1)
                "
            );
            $installer->run(
                "
                  INSERT INTO `sales_order_status_state`(`status`, `state`, `is_default`, `visible_on_front`) VALUES ('delivered', 'processing', 0, 1)
                "
            );
        }

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $tableName = $installer->getTable('sales_shipment');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $installer->getConnection();
                $connection->addColumn(
                    $tableName,
                    'warehouse',
                    ['type' => Table::TYPE_TEXT,'nullable' => true, 'default' => '','comment' => 'WareHouse']
                );
            }
        }
        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            $tableName = $installer->getTable('sales_order_status_state');
            $installer->run(
                "
                  UPDATE ".$tableName." SET  visible_on_front = 1 WHERE status = 'suspicious' 
                "
            );
        }

        

        $installer->endSetup();
    }
}