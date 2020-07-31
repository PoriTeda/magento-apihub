<?php
// @codingStandardsIgnoreFile
/**
 * Riki Shipment Importer
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShipmentImporter\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ShipmentImporter\Setup;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class UpgradeSchema
 *
 * @category  RIKI
 * @package   Riki\ShipmentImporter\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    protected $statusFactory;

    /**
     * UpgradeSchema constructor.
     * @param \Magento\Sales\Model\Order\StatusFactory $statusFactory
     */
    public function __construct(
        \Magento\Sales\Model\Order\StatusFactory $statusFactory
    )
    {
        $this->statusFactory = $statusFactory;
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.0.2') < 0) {

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_shipment'),
                'ship_status',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 100,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Shipment Status'
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_shipment'),
                'ship_zsim',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Shipment ZSIM'
                ]
            );
        }
        $orderStatus = $this->statusFactory->create();
        if (version_compare($context->getVersion(), '1.0.3') < 0) {

            if(!$orderStatus->load('capture_failed'))
            {
                $setup->run("INSERT INTO `sales_order_status`(`status`, `label`) VALUES ('capture_failed', 'Failed Capture - Delivered')");
                $setup->run("INSERT INTO `sales_order_status_state`(`status`, `state`, `is_default`, `visible_on_front`) VALUES ('capture_failed', 'processing', 0, 0)");

            }
        }
        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            if(!$orderStatus->load('delivery_completed_pg'))
            {
                $setup->run("INSERT INTO `sales_order_status`(`status`, `label`) VALUES ('delivery_completed_pg', 'Delivery Completed')");
                $setup->run("INSERT INTO `sales_order_status_state`(`status`, `state`, `is_default`, `visible_on_front`) VALUES ('delivery_completed_pg', 'complete', 0, 0)");

            }
        }
        if (version_compare($context->getVersion(), '1.0.5') < 0) {
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_shipment'),
                'ship_status_1501',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 100,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Shipment Status 1501'
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_shipment'),
                'ship_status_1502',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 100,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Shipment Status 1502'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.6') < 0) {
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_shipment'),
                'ship_status_1503',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 100,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Shipment Status 1503'
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_shipment'),
                'ship_status_1504',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 100,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Shipment Status 1504'
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_shipment'),
                'ship_status_1505',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 100,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Shipment Status 1505'
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_shipment'),
                'is_exported',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'exported shipment'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_shipment'),
                'export_date',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                    'nullable' => true,
                    'comment' => 'Export date',

                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.7') < 0) {
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'),
                'shipment_created',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'created shipment or not '
                ]
            );

        }
        if (version_compare($context->getVersion(), '1.0.8') < 0) {
            //add shipment status and payment status in Order Grid
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'),
                'shipment_status',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 100,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Shipment Status'
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'),
                'payment_status',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 100,
                    'nullable' => false,
                    'default' => 'not_applicable',
                    'comment' => 'Payment Status'
                ]
            );
            /*-----------------------------------------*/
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order_grid'),
                'shipment_status',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 100,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Shipment Status'
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order_grid'),
                'payment_status',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 100,
                    'nullable' => false,
                    'default' => 'not_applicable',
                    'comment' => 'Payment Status'
                ]
            );
        }
        // add shipped out date and delivery complete date
        if (version_compare($context->getVersion(), '1.0.9') < 0) {
            $table = $setup->getTable('sales_shipment');
            $field1 = 'shipped_out_date';
            $connection = $setup->getConnection();
            if(!$connection->tableColumnExists($table,$field1)){
                $connection->addColumn(
                    $table,
                    $field1,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Shipped out date',

                    ]
                );
            }
            $field2 = 'delivery_complete_date';
            if(!$connection->tableColumnExists($table, $field2)){
                $connection->addColumn(
                    $table,
                    $field2,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Delivery complete date',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.1.0') < 0) {

            $table = $setup->getTable('sales_shipment');

            $connection = $setup->getConnection();

            $field1 = 'shipment_date';
            $field2 = 'payment_date';
            $field3 = 'payment_status';

            if(!$connection->tableColumnExists($table,$field1)){
                $connection->addColumn(
                    $table,
                    $field1,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Shipment date'
                    ]
                );
            }
            if(!$connection->tableColumnExists($table,$field2)){
                $connection->addColumn(
                    $table,
                    $field2,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Payment date'
                    ]
                );
            }
            if(!$connection->tableColumnExists($table,$field3)){
                $connection->addColumn(
                    $table,
                    $field3,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'payment status'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.1.2') < 0) {

            $table = $setup->getTable('sales_shipment');

            $connection = $setup->getConnection();

            $field4 = 'shipment_status';

            if($connection->tableColumnExists($table, $field4)){
                $connection->dropColumn( $table, $field4 );
            }

            $connection->addColumn(
                $table,
                $field4,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Shipment status'
                ]
            );
        }

        $setup->endSetup();
    }

}