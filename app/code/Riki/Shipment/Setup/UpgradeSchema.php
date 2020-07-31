<?php
// @codingStandardsIgnoreFile

/**
 * Shipment Upgrade Schema
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Shipment\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Shipment\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Email\Model\TemplateFactory as TemplateFactory;
use Magento\Email\Model\ResourceModel\Template\Collection as TemplateCollection;

/**
 * Class upgrade schema
 *
 * @category  RIKI
 * @package   Riki\Shipment\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var TemplateFactory
     */
    protected $templateEmail;
    /**
     * @var TemplateCollection
     */
    protected $templateCollection;
    /**
     * @var \Riki\Sales\Helper\ConnectionHelper
     */
    protected $_setupHelper;
    /**
     * @var false|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $salesConnection;

    /**
     * UpgradeSchema constructor.
     * @param TemplateFactory $template
     * @param TemplateCollection $collection
     * @param \Riki\Sales\Helper\ConnectionHelper $setupHelper
     * @param \Magento\Sales\Model\ResourceModel\Order $orderResource
     */
    public function __construct(
        TemplateFactory $template,
        TemplateCollection $collection,
        \Riki\Sales\Helper\ConnectionHelper $setupHelper,
        \Magento\Amqp\Model\Topology $topology
    ) {
        $this->templateEmail = $template;
        $this->templateCollection = $collection;
        $this->_setupHelper = $setupHelper;
        $this->salesConnection = $setupHelper->getSalesConnection();
        $this->topology = $topology;
    }

    /**
     * Upgrade function
     *
     * @param   SchemaSetupInterface $setup
     * @param   ModuleContextInterface $context
     * @throws \Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();
        $table_shipment = $setup->getTable('sales_shipment');
        $table_shipment_grid = $setup->getTable('sales_shipment_grid');
        if (version_compare($context->getVersion(), '0.0.3') < 0) {
            //remove unused columns
            $unused_columns = array(
                'ship_status',
                'ship_status_1501',
                'ship_status_1502',
                'ship_status_1503',
                'ship_status_1504',
                'ship_status_1505',
                'shipped_out_date',
                'delivery_complete_date',
                'nestle_payment_date',
                'nestle_payment_amount',
                'shipment_status',
                'shipment_date',
                'payment_date',
                'payment_status'
            );
            foreach ($unused_columns as $_column) {
                if ($connection->tableColumnExists($table_shipment, $_column)) {
                    $connection->dropColumn($table_shipment, $_column);
                }
            }
            //for sales_shipment_grid
            if ($connection->tableColumnExists($table_shipment_grid, 'shipment_status')) {
                $connection->dropColumn($table_shipment_grid, 'shipment_status');
            }
            //add more column
            $add_columns = array(
                'ship_zsim' => array(
                    'data_type' => [
                        'type' => Table::TYPE_SMALLINT,
                        'unsigned' => true,
                        'nullable' => true,
                        'default' => 0,
                        'comment' => 'Shipment ZSIM'
                    ],
                    'shipment_grid' => 0

                ),
                'export_date' => array(
                    'data_type' => [
                        'type' => Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Export date',
                    ],
                    'shipment_grid' => 1
                ),
                'delivery_date' => array(
                    'data_type' => [
                        'type' => Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Delivery date',
                    ],
                    'shipment_grid' => 1
                ),
                'timeslot' => array(
                    'data_type' => [
                        'type' => Table::TYPE_TEXT,
                        'length' => 5,
                        'nullable' => true,
                        'default' => '',
                        'comment' => 'Time slot '
                    ],
                    'shipment_grid' => 0
                ),

                'amount_total' => array(
                    'data_type' => [
                        'type' => Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Shipment amount'
                    ]
                ,
                    'shipment_grid' => 1
                ),
                'amount_collected' => array(
                    'data_type' => [
                        'type' => Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'collected amount'
                    ],
                    'shipment_grid' => 1
                ),
                'shipment_status' => array(
                    'data_type' => [
                        'type' => Table::TYPE_TEXT,
                        'length' => 100,
                        'nullable' => true,
                        'default' => '',
                        'comment' => 'Shipment Status'
                    ],
                    'shipment_grid' => 1
                ),
                'shipment_date' => array(
                    'data_type' => [
                        'type' => Table::TYPE_DATETIME,
                        'nullable' => true,
                        'comment' => 'Shipment Status date'
                    ],
                    'shipment_grid' => 1
                ),
                'payment_status' => array(
                    'data_type' => [
                        'type' => Table::TYPE_TEXT,
                        'length' => 100,
                        'nullable' => true,
                        'comment' => 'Payment Status'
                    ],
                    'shipment_grid' => 1
                ),
                'payment_date' => array(
                    'data_type' => [
                        'type' => Table::TYPE_DATETIME,
                        'nullable' => true,
                        'comment' => 'Payment Status date'
                    ],
                    'shipment_grid' => 1
                ),
                'is_exported' => array(
                    'data_type' => [
                        'type' => Table::TYPE_SMALLINT,
                        'unsigned' => true,
                        'nullable' => true,
                        'default' => 0,
                        'comment' => 'Mark exported shipment'
                    ],
                    'shipment_grid' => 1
                ),
                'flag_shipment_complete' => array(
                    'data_type' => [
                        'type' => Table::TYPE_SMALLINT,
                        'unsigned' => true,
                        'nullable' => true,
                        'default' => 0,
                        'comment' => 'Mark complete shipment'
                    ],
                    'shipment_grid' => 0
                ),
            );
            //table sales_shipment
            foreach ($add_columns as $_key => $_column) {
                //add column to table sales_shipment
                if (!$connection->tableColumnExists($table_shipment, $_key)) {
                    $connection->addColumn(
                        $table_shipment,
                        $_key,
                        $_column['data_type']
                    );
                }
            }//end foreach
            //table sales_shipment_grid
            foreach ($add_columns as $_key => $_column) {
                //add column to table sales_shipment
                if ($_column['shipment_grid'] && !$connection->tableColumnExists($table_shipment_grid, $_key)) {
                    $connection->addColumn(
                        $table_shipment_grid,
                        $_key,
                        $_column['data_type']
                    );
                }
            }//end foreach

            //create trigger to update table grid
            $trigger_table = $setup->getTable('sales_shipment');
            $trigger_table_target = $setup->getTable('sales_shipment_grid');

            $setup->run("
            CREATE TRIGGER auto_update_order_shipment_grid AFTER UPDATE ON $trigger_table
                FOR EACH ROW
                  UPDATE $trigger_table_target
                     SET 
                         shipment_status = NEW.shipment_status,
                         shipment_date = NEW.shipment_date,
                         payment_status = NEW.payment_status,
                         payment_date = NEW.payment_date,
                         amount_total = NEW.amount_total,
                         amount_collected = NEW.amount_collected,
                         export_date = NEW.export_date,
                         delivery_date = NEW.delivery_date
                   WHERE entity_id = NEW.entity_id;

            ");

            // add table riki_shipment_shipping_history
            $tableName = 'riki_shipment_shipping_history';
            if (!$connection->isTableExists($setup->getTable($tableName))) {
                $tbl = $connection->newTable($setup->getTable($tableName))
                    ->addColumn(
                        'shipment_status_id',
                        Table::TYPE_BIGINT,
                        null,
                        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                        'Shipment Status ID'
                    )
                    ->addColumn(
                        'shipment_id',
                        Table::TYPE_INTEGER,
                        null,
                        ['unsigned' => true, 'primary' => false, 'nullable' => false],
                        'Shipment ID'
                    )->addColumn(
                        'shipment_status',
                        Table::TYPE_TEXT,
                        20,
                        ['nullable' => true],
                        'Shipment status'
                    )->addColumn(
                        'shipment_date',
                        Table::TYPE_DATETIME,
                        null,
                        ['nullable' => true],
                        'Shipment status date time'
                    )->setComment(
                        'Riki Shipment status history'
                    );
                $connection->createTable($tbl);
            }
            //add table riki_shipment_payment_history
            $tableName = 'riki_shipment_payment_history';
            if (!$connection->isTableExists($setup->getTable($tableName))) {
                $tbl = $connection->newTable($setup->getTable($tableName))
                    ->addColumn(
                        'payment_status_id',
                        Table::TYPE_BIGINT,
                        null,
                        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                        'Payment Status ID'
                    )
                    ->addColumn(
                        'shipment_id',
                        Table::TYPE_INTEGER,
                        null,
                        ['unsigned' => true, 'primary' => false, 'nullable' => false],
                        'Shipment ID'
                    )->addColumn(
                        'payment_status',
                        Table::TYPE_TEXT,
                        20,
                        ['nullable' => true],
                        'Payment status'
                    )->addColumn(
                        'payment_amount',
                        Table::TYPE_DECIMAL,
                        '12,4',
                        ['nullable' => true],
                        'collected payment amount'
                    )->addColumn(
                        'payment_date',
                        Table::TYPE_DATETIME,
                        null,
                        ['nullable' => true],
                        'Payment status date time'
                    )->setComment(
                        'Riki Shipment payment status history'
                    );
                $connection->createTable($tbl);
            }
        }
        //for sales_shipment_grid
        if (version_compare($context->getVersion(), '0.0.4') < 0) {
            if ($connection->tableColumnExists($table_shipment_grid, 'shipment_status')) {
                $connection->dropColumn($table_shipment_grid, 'shipment_status');
            }

            $connection->addColumn(
                $table_shipment_grid,
                'shipment_status',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 100,
                    'nullable' => true,
                    'default' => '',
                    'comment' => 'Shipment Status'
                ]
            );
        }
        //remove trigger because grid collection will be updated via di.xml
        if (version_compare($context->getVersion(), '0.0.5') < 0) {
            $setup->run("DROP TRIGGER IF EXISTS `auto_update_order_shipment_grid`");
            $setup->run("DROP TRIGGER IF EXISTS `auto_update_order_shipment_grid_insert`");
            $setup->run("DROP TRIGGER IF EXISTS `auto_update_payship_order_grid`");
        }

        if (version_compare($context->getVersion(), '0.0.6') < 0) {
            $tableName = $setup->getTable('sales_order');
            $fieldName = 'payment_status';
            if ($connection->tableColumnExists($tableName, $fieldName)) {
                $connection->modifyColumn
                (
                    $tableName,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => '50',
                        'nullable' => true,
                        'comment' => 'Payment Status'
                    ]
                );
            }
        }
        //setup email for tracking code email

        if (version_compare($context->getVersion(), '0.0.7') < 0) {

            $email_code = 'shipment_tracking';
            $email_subject = 'Shipment tracking code';
            $email_content = '
            {{template config_path="design/email/header_template"}}
            
            Dear {{var customerName}} <br><br>
            
            This is shipment tracking code : {{var trackingCode}}.<br><br>
            
            You can follow this url : {{var trackingUrl}} to see where is shipped products.<br><br>
            
            Thanks<br><br>
            
            Riki Team
            
            {{template config_path="design/email/footer_template"}}
            ';

            $email_type = 2; // 2 : html, 1 : text

            $collection = $this->templateCollection
                ->addFieldToFilter('template_code', $email_code)->load();
            if (!$collection->getSize()) {
                try {
                    $emailModel = $this->templateEmail->create();
                    $emailModel->setTemplateCode($email_code)
                        ->setTemplateType($email_type)
                        ->setTemplateSubject($email_subject)
                        ->setTemplateText($email_content)
                        ->save();
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }
        //reset default of processing states

        if (version_compare($context->getVersion(), '0.0.8') < 0) {
            $table = $setup->getTable('sales_shipment');

            if (!$connection->tableColumnExists($table, 'base_amount_total')) {
                $connection->addColumn($table,
                    'base_amount_total',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '(12,4)',
                        'nullable' => true,
                        'default' => 0,
                        'after' => 'amount_total',
                        'comment' => 'Base amount total'
                    ]
                );
            }

            if (!$connection->tableColumnExists($table, 'shipment_fee')) {
                $connection->addColumn($table,
                    'shipment_fee',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '(12,4)',
                        'nullable' => true,
                        'default' => 0,
                        'after' => 'amount_collected',
                        'comment' => 'Delivery fee'
                    ]
                );
            }

            if (!$connection->tableColumnExists($table, 'base_shipment_fee')) {
                $connection->addColumn($table,
                    'base_shipment_fee',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '(12,4)',
                        'nullable' => true,
                        'default' => 0,
                        'after' => 'shipment_fee',
                        'comment' => 'Base Delivery fee'
                    ]
                );
            }

            if (!$connection->tableColumnExists($table, 'payment_fee')) {
                $connection->addColumn($table,
                    'payment_fee',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '(12,4)',
                        'nullable' => true,
                        'default' => 0,
                        'after' => 'base_shipment_fee',
                        'comment' => 'Surcharge fee'
                    ]
                );
            }

            if (!$connection->tableColumnExists($table, 'base_payment_fee')) {
                $connection->addColumn($table,
                    'base_payment_fee',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '(12,4)',
                        'nullable' => true,
                        'default' => 0,
                        'after' => 'payment_fee',
                        'comment' => 'Base Surcharge fee'
                    ]
                );
            }

            if (!$connection->tableColumnExists($table, 'discount_amount')) {
                $connection->addColumn($table,
                    'discount_amount',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '(12,4)',
                        'nullable' => true,
                        'default' => 0,
                        'after' => 'base_payment_fee',
                        'comment' => 'Discount amount'
                    ]
                );
            }

            if (!$connection->tableColumnExists($table, 'base_discount_amount')) {
                $connection->addColumn($table,
                    'base_discount_amount',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '(12,4)',
                        'nullable' => true,
                        'default' => 0,
                        'after' => 'discount_amount',
                        'comment' => 'Base Discount amount'
                    ]
                );
            }

            if (!$connection->tableColumnExists($table, 'shopping_point_amount')) {
                $connection->addColumn($table,
                    'shopping_point_amount',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '(12,4)',
                        'nullable' => true,
                        'default' => 0,
                        'after' => 'base_discount_amount',
                        'comment' => 'Shopping point amount'
                    ]
                );
            }

            if (!$connection->tableColumnExists($table, 'base_shopping_point_amount')) {
                $connection->addColumn($table,
                    'base_shopping_point_amount',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '(12,4)',
                        'nullable' => true,
                        'default' => 0,
                        'after' => 'shopping_point_amount',
                        'comment' => 'Base Shopping point amount'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '0.1.0') < 0) {

            $table = $setup->getTable('sales_shipment');

            if (!$connection->tableColumnExists($table, 'tax_amount')) {
                $connection->addColumn($table,
                    'tax_amount',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '(12,4)',
                        'nullable' => true,
                        'default' => 0,
                        'comment' => 'Tax amount'
                    ]
                );
            }

            if (!$connection->tableColumnExists($table, 'base_tax_amount')) {
                $connection->addColumn($table,
                    'base_tax_amount',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '(12,4)',
                        'nullable' => true,
                        'default' => 0,
                        'after' => 'tax_amount',
                        'comment' => 'Base Tax amount'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '0.1.1') < 0) {

            $table = $setup->getTable('sales_shipment');

            if (!$connection->tableColumnExists($table, 'gw_price')) {
                $connection->addColumn($table,
                    'gw_price',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '(12,4)',
                        'nullable' => true,
                        'default' => 0,
                        'comment' => 'Gift wrapping amount'
                    ]
                );
            }

            if (!$connection->tableColumnExists($table, 'gw_base_price')) {
                $connection->addColumn($table,
                    'gw_base_price',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '(12,4)',
                        'nullable' => true,
                        'default' => 0,
                        'after' => 'gw_price',
                        'comment' => 'Base Gift wrapping amount'
                    ]
                );
            }

            $table = $setup->getTable('sales_order_status_state');
            $sql1 = "UPDATE $table set is_default = 0 where `status` = 'preparing_for_shipping' ;";
            $sql2 = "UPDATE $table set is_default = 1 where `status` = 'waiting_for_shipping' ;";

            $setup->run($sql1);
            $setup->run($sql2);

            //insert email template for tracking code
            $emails = [
                'shipment_tracking_spot' => [
                    'subject' => 'Notification of Completion of Shipping Out',
                    'content' => '',
                    'type' => 1
                ],
                'shipment_tracking_subscription' => [
                    'subject' => 'Notification of Completion of Shipping Out',
                    'content' => '',
                    'type' => 1

                ],
                'shipment_tracking_hanpukai' => [
                    'subject' => 'Notification of Completion of Shipping Out',
                    'content' => '',
                    'type' => 1

                ],

            ];

            $emailCollections = $this->templateCollection
                ->addFieldToFilter(
                    'template_code', array(
                        'in' => array(
                            'shipment_tracking_spot',
                            'shipment_tracking_hanpukai',
                            'shipment_tracking_subscription'
                        )
                    )
                )->load();
            foreach ($emailCollections as $_etemp) {
                $_etemp->delete();
            }

            foreach ($emails as $ekey => $mail) {
                $model = $this->templateEmail->create();
                $model->setTemplateSubject($mail['subject']);
                $model->setTemplateCode($ekey);
                $model->setTemplateType($mail['type']);
                $model->setTemplateText($mail['content']);
                try {
                    $model->save();
                } catch (\Exception $e) {
                    throw $e;
                }

            }

        }
        if (version_compare($context->getVersion(), '0.1.1') < 0) {
            //update spot
            $emailModel = $this->templateEmail->create()->load('shipment_tracking_spot', 'template_code');
            $emailModel->setTemplateText("")->save();
            //Hanpukai
            $emailModel = $this->templateEmail->create()->load('shipment_tracking_hanpukai', 'template_code');
            $emailModel->setTemplateText("")->save();
            //Subscription
            $emailModel = $this->templateEmail->create()->load('shipment_tracking_subscription', 'template_code');
            $emailModel->setTemplateText("")->save();

        }

        if (version_compare($context->getVersion(), '0.1.2') < 0) {

            $table = $setup->getTable('sales_shipment');

            if (!$connection->tableColumnExists($table, 'gw_tax_amount')) {
                $connection->addColumn($table,
                    'gw_tax_amount',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '(12,4)',
                        'nullable' => true,
                        'default' => 0,
                        'comment' => 'Gift wrapping tax amount'
                    ]
                );
            }

            if (!$connection->tableColumnExists($table, 'gw_base_tax_amount')) {
                $connection->addColumn($table,
                    'gw_base_tax_amount',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '(12,4)',
                        'nullable' => true,
                        'default' => 0,
                        'after' => 'gw_tax_amount',
                        'comment' => 'Base Gift wrapping tax amount'
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '0.1.3') < 0) {
            //delete email template
            $templates = [
                'shipment_tracking_spot',
                'shipment_tracking_hanpukai',
                'shipment_tracking_subscription'
            ];
            foreach ($templates as $template) {
                $templateModel = $this->templateEmail->create()->load($template, 'template_code');
                if ($templateModel->getId()) {
                    try {
                        $templateModel->delete();
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
            }
        }

        if (version_compare($context->getVersion(), '0.1.4') < 0) {
            $table = $setup->getTable('sales_shipment');
            $connection->addColumn(
                $table,
                'subscription_profile_id',
                [
                    'type' => Table::TYPE_INTEGER,
                    'comment' => 'Subscription Profile Main ID',
                ]
            );

            $gridTable = $setup->getTable('sales_shipment_grid');
            $connection->addColumn(
                $gridTable,
                'created_by',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' => 'User'
                ]
            );
            $connection->addColumn(
                $gridTable,
                'shosha_business_code',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' => 'Shosha customer code'
                ]
            );
            $connection->addColumn(
                $gridTable,
                'customer_membership',
                [
                    'type' => Table::TYPE_TEXT,
                    'comment' => 'Customer membership'
                ]
            );
            $connection->addColumn(
                $gridTable,
                'warehouse',
                [
                    'type' => Table::TYPE_TEXT,
                    'comment' => 'Warehouse'
                ]
            );
            $connection->addColumn(
                $gridTable,
                'free_of_charge',
                [
                    'type' => Table::TYPE_BOOLEAN,
                    'default' => 0,
                    'comment' => 'Free of charge'
                ]
            );
            $connection->addColumn(
                $gridTable,
                'mm_order_id',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' => 'Machine Maintenance Order ID'
                ]
            );
            $connection->addColumn(
                $gridTable,
                'subscription_course_id',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' => 'Subscription Code'
                ]
            );
            $connection->addColumn(
                $gridTable,
                'subscription_course_name',
                [
                    'type' => Table::TYPE_TEXT,
                    null,
                    'comment' => 'Subscription Name',
                ]
            );
            $connection->addColumn(
                $gridTable,
                'payment_transaction_id',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' => 'Transaction ID of Paygent'
                ]
            );
        }
        //Flag to export into SAP
        if (version_compare($context->getVersion(), '0.1.5') < 0) {
            $tables = [$setup->getTable('sales_shipment'), $setup->getTable('sales_shipment_grid')];
            foreach ($tables as $table) {
                $connection->addColumn(
                    $table,
                    'is_exported_sap',
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'default' => 0,
                        'length' => 4,
                        'comment' => 'Is exported SAP'
                    ]
                );
                $connection->addColumn(
                    $table,
                    'export_sap_date',
                    [
                        'type' => Table::TYPE_DATETIME,
                        'comment' => 'Export SAP date'
                    ]
                );
                $connection->addColumn(
                    $table,
                    'sap_ren_flg',
                    [
                        'type' => Table::TYPE_BOOLEAN,
                        'default' => 0,
                        'comment' => 'Mark used to export to SAP'
                    ]
                );
            }
        }


        // add shipped out date and delivery complete date
        if (version_compare($context->getVersion(), '2.0.1') < 0) {

            $table = $setup->getTable('sales_shipment_grid');
            $field = 'shipped_out_date';

            $connection = $this->_setupHelper->getSalesConnection();

            if (!$connection->tableColumnExists($table, $field)) {
                $connection->addColumn(
                    $table,
                    $field,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Shipped out date',

                    ]
                );
            }

            $field = 'delivery_complete_date';
            if (!$connection->tableColumnExists($table, $field)) {
                $connection->addColumn(
                    $table,
                    $field,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Delivery complete date',
                    ]
                );
            }
        }
        // add delivery type and warehouse for shipment
        if (version_compare($context->getVersion(), '2.0.2') < 0) {
            $tables = [];
            $tables[] = $setup->getTable('sales_shipment');
            $tables[] = $setup->getTable('sales_shipment_grid');
            foreach ($tables as $table) {
                $field = 'delivery_type';
                $connection = $this->_setupHelper->getSalesConnection();
                if (!$connection->tableColumnExists($table, $field)) {
                    $connection->addColumn(
                        $table,
                        $field,
                        [
                            'type' => Table::TYPE_TEXT,
                            'length' => 255,
                            'comment' => 'Delivery Type Group'

                        ]
                    );
                }
            }

        }
        // add shipping name kana for shipment
        if (version_compare($context->getVersion(), '2.0.3') < 0) {
            $tableName = $setup->getTable('sales_shipment_grid');
            $fieldName = 'shipping_name_kana';
            $connection = $this->_setupHelper->getSalesConnection();
            if (!$connection->tableColumnExists($tableName, $fieldName)) {
                $connection->addColumn(
                    $tableName,
                    $fieldName,
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'Shipping name kana'

                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '2.0.4.1') < 0) {
            $fieldName = 'grand_total';
            $connection = $this->_setupHelper->getSalesConnection();
            $tableShipment = $setup->getTable('sales_shipment');
            $tableShipmentGrid = $setup->getTable('sales_shipment_grid');

            if (!$connection->tableColumnExists($tableShipment, $fieldName)) {
                $connection->addColumn(
                    $tableShipment,
                    $fieldName,
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Grand total amount (Tax included) '
                    ]
                );
            }

            if (!$connection->tableColumnExists($tableShipmentGrid, $fieldName)) {
                $connection->addColumn(
                    $tableShipmentGrid,
                    $fieldName,
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Grand total amount (Tax included) '
                    ]
                );
            }
        }

        //install schema for WBS
        if (version_compare($context->getVersion(), '2.0.5') < 0) {
            $tableName = "sales_shipment";
            $table = $setup->getTable($tableName);
            //field : free_delivery_wbs
            $fieldName = "free_delivery_wbs";
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($table,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Free shipping fee WBS'
                    ]
                );
            }

            //field : booking_wbs
            $fieldName = "booking_wbs";
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($table,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Get from product attribute'
                    ]
                );
            }
            //field : free_of_charge
            $fieldName = "free_of_charge";
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($table,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'nullable' => true,
                        'default' => false,
                        'comment' => 'Free of charge'
                    ]
                );
            }

            //field : free_payment_wbs
            $fieldName = "free_payment_wbs";
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($table,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Free payment fee WBS'
                    ]
                );
            }
        }

        //install schema for WBS in shipment items
        if (version_compare($context->getVersion(), '2.0.6') < 0) {
            //  sales_shipment_item.booking_wbs
            // free_of_charge
            $tableName = "sales_shipment_item";
            $this->salesConnection->getTableName($tableName);
            //field : booking_wbs
            $fieldName = "booking_wbs";
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($table,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Get from product attribute and store in shipment item'
                    ]
                );
            }
            //field : free_of_charge
            $fieldName = "free_of_charge";
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($table,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'nullable' => true,
                        'default' => false,
                        'comment' => 'Free of charge in shipment Item'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '2.0.7') < 0) {
            $tableName = "sales_shipment_item";
            $tableName = $this->salesConnection->getTableName($tableName);

            $fieldName = "commission_amount";
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($tableName,
                    $fieldName,
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Riki commission'
                    ]

                );
            }
        }
        //add Billing Address and Shipping Address to Sales_Shipment Table
        if (version_compare($context->getVersion(), '2.0.8') < 0) {
            $tableName = $this->salesConnection->getTableName('sales_shipment');
            $fieldName = 'billing_address';
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($tableName,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 1000,
                        'nullable' => true,
                        'comment' => 'Billing Address'
                    ]
                );
            }
            $fieldName = 'shipping_address';
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($tableName,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 1000,
                        'nullable' => true,
                        'comment' => 'Shipping Address'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '2.0.9') < 0) {
            $tableName = "sales_shipment_item";
            $tableName = $this->salesConnection->getTableName($tableName);

            $fieldName = "commission_amount";
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($tableName,
                    $fieldName,
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Riki commission'
                    ]

                );
            }
        }

        // Install sales shipment item
        if (version_compare($context->getVersion(), '2.0.10') < 0) {
            $tableName = $this->salesConnection->getTableName('sales_shipment_item');
            $fieldName = 'gps_price_ec';
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($tableName, $fieldName, [
                    'type' => Table::TYPE_DECIMAL,
                    'length' => '12,4',
                    'nullable' => true,
                    'comment' => 'Gps price ec'
                ]);
            }

            $fieldName = 'material_type';
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($tableName, $fieldName, [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Material type'
                ]);
            }

            $fieldName = 'sales_organization';
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($tableName, $fieldName, [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Sales organization'
                ]);
            }

            $fieldName = 'sap_interface_excluded';
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($tableName, $fieldName, [
                    'type' => Table::TYPE_BOOLEAN,
                    'default' => 0,
                    'comment' => 'SAP interface excluded flag'
                ]);
            }
        }

        // Set flag invoice sales shipment
        if (version_compare($context->getVersion(), '2.1.0') < 0) {
            $table = $this->salesConnection->getTableName('sales_shipment');
            $fieldName = 'flag_export_invoice_sales_shipment';
            if ($this->salesConnection->isTableExists($table)) {
                if (!$this->salesConnection->tableColumnExists($table, $fieldName)) {
                    $this->salesConnection->addColumn(
                        $table, $fieldName, [
                            'type' => Table::TYPE_BOOLEAN,
                            'default' => 0,
                            'comment' => 'Flag export sales shipment'
                        ]
                    );
                }
            }
        }
        // add Shipping Address ID and new_shipping_name
        if (version_compare($context->getVersion(), '2.1.1') < 0) {
            $table = $this->salesConnection->getTableName('sales_shipment');
            $fieldName = 'shipping_address_name';
            if ($this->salesConnection->isTableExists($table)) {
                if (!$this->salesConnection->tableColumnExists($table, $fieldName)) {
                    $this->salesConnection->addColumn(
                        $table, $fieldName, [
                            'type' => Table::TYPE_TEXT,
                            'length' => 255,
                            'nullable' => true,
                            'comment' => 'Shipping Address Name'
                        ]
                    );
                }
            }

        }
        // add Shipping Address ID and new_shipping_name
        if (version_compare($context->getVersion(), '2.1.2') < 0) {
            $table = $this->salesConnection->getTableName('sales_shipment_grid');
            $fieldName = 'shipping_address_name';
            if ($this->salesConnection->isTableExists($table)) {
                if (!$this->salesConnection->tableColumnExists($table, $fieldName)) {
                    $this->salesConnection->addColumn(
                        $table, $fieldName, [
                            'type' => Table::TYPE_TEXT,
                            'length' => 255,
                            'nullable' => true,
                            'comment' => 'Shipping Address Name'
                        ]
                    );
                }
            }

        }

        if (version_compare($context->getVersion(), '2.1.3') < 0) {
            $table = $this->salesConnection->getTableName('sales_shipment_grid');
            $fieldName = 'shipping_address_newid';
            if ($this->salesConnection->isTableExists($table)) {
                if (!$this->salesConnection->tableColumnExists($table, $fieldName)) {
                    $this->salesConnection->addColumn(
                        $table, $fieldName, [
                            'type' => Table::TYPE_INTEGER,
                            'default' => 0,
                            'comment' => 'Shipping Address New Id'
                        ]
                    );
                }
            }
            $table = $this->salesConnection->getTableName('sales_shipment');
            $fieldName = 'shipping_address_newid';
            if ($this->salesConnection->isTableExists($table)) {
                if (!$this->salesConnection->tableColumnExists($table, $fieldName)) {
                    $this->salesConnection->addColumn(
                        $table, $fieldName, [
                            'type' => Table::TYPE_INTEGER,
                            'default' => 0,
                            'comment' => 'Shipping Address New Id'
                        ]
                    );
                }
            }
        }
        //add  free_delivery_wbs for sales shipment item
        if (version_compare($context->getVersion(), '2.1.4') < 0) {
            $tableName = "sales_shipment_item";
            $table = $this->salesConnection->getTableName($tableName);
            //field : free_delivery_wbs
            $fieldName = "free_delivery_wbs";
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($table,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Free shipping fee WBS on Shipment Item'
                    ]
                );
            }
        }
        // add publish_message queue to order
        if (version_compare($context->getVersion(), '2.1.5') < 0) {
            $tableName = $this->salesConnection->getTableName('sales_order');
            if ($this->salesConnection->isTableExists($tableName)) {
                $this->salesConnection->addColumn(
                    $tableName, 'published_message',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'default' => 0,
                        'comment' => 'Published to message queue',
                        'nullable' => true
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '2.1.6') < 0) {
            $table = $setup->getTable('queue');
            if ($setup->tableExists($table)) {
                $setup->run("DELETE FROM {$table} WHERE name = 'sender_queue_shipment_creator' ");
                $setup->getConnection('default')->insert
                (
                    $table,
                    ['name' => 'sender_queue_shipment_creator']
                );
            }

        }
        if (version_compare($context->getVersion(), '2.1.7') < 0) {
            $fieldName = 'payment_date';
            $table = $this->salesConnection->getTableName('sales_shipment');
            if ($this->salesConnection->tableColumnExists($table, $fieldName)) {
                $this->salesConnection->modifyColumn(
                    $table,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Payment collection date'
                    ]
                );

            }
            $tableGrid = $this->salesConnection->getTableName('sales_shipment_grid');
            if ($this->salesConnection->tableColumnExists($tableGrid, $fieldName)) {
                $this->salesConnection->modifyColumn(
                    $tableGrid,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'Payment collection date'
                    ]
                );

            }

        }
        if (version_compare($context->getVersion(), '2.1.8') < 0) {
            $tableName = "sales_shipment_grid";
            $tableName = $this->salesConnection->getTableName($tableName);

            $fieldName = "shipment_fee";
            if (!$this->salesConnection->tableColumnExists($tableName, $fieldName)) {
                $this->salesConnection->addColumn($tableName,
                    $fieldName,
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'default' => '0.0000',
                        'comment' => 'Shipment Fee'
                    ]

                );
            }
        }

        if (version_compare($context->getVersion(), '2.1.9') < 0) {
            $this->topology->install();
        }

        if (version_compare($context->getVersion(), '2.2.0') < 0) {
            $table = $this->salesConnection->getTableName('sales_shipment');

            $indexName = $this->salesConnection->getIndexName('sales_shipment', ['shipping_address_id']);

            $this->salesConnection->dropIndex($table, $indexName);

            $this->salesConnection->addIndex($table, $indexName, ['shipping_address_id']);
        }

        if (version_compare($context->getVersion(), '2.2.1') < 0) {
            $table = $this->salesConnection->getTableName('sales_shipment');

            $this->salesConnection->addIndex(
                $table,
                $connection->getIndexName(
                    $table,
                    ['is_exported_sap', 'entity_id']
                ),
                ['is_exported_sap', 'entity_id']
            );
        }

        if (version_compare($context->getVersion(), '2.3.0') < 0) {
            $this->salesConnection->addColumn($this->salesConnection->getTableName('sales_shipment'),
                'total_case_qty',
                [
                    'type' => Table::TYPE_DECIMAL,
                    'length' => '12,4',
                    'default' => '0.0000',
                    'comment' => 'Total qty base case'
                ]
            );
            $this->salesConnection->addColumn($this->salesConnection->getTableName('sales_shipment_grid'),
                'total_case_qty',
                [
                    'type' => Table::TYPE_DECIMAL,
                    'length' => '12,4',
                    'default' => '0.0000',
                    'comment' => 'Total qty base case'
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.3.1') < 0) {
            $table = $this->salesConnection->getTableName('sales_shipment');

            $this->salesConnection->addIndex(
                $table,
                $connection->getIndexName(
                    $table,
                    ['is_reconciliation_exported']
                ),
                ['is_reconciliation_exported']
            );
        }
        if (version_compare($context->getVersion(), '2.3.2') < 0) {
            $this->salesConnection->addColumn($this->salesConnection->getTableName('sales_shipment'),
                'is_chirashi',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'default' => 0,
                    'nullable' => true,
                    'comment' => 'Shipment Chirashi'
                ]
            );
        }

        /**
         *  To have work-around in the ticket RIM-3727
         *  Accessing order view page on BE will get the shipment information by order_id key
         */
        if (version_compare($context->getVersion(), '2.3.3') < 0)// Perf tunning in the ticket #RIM-3727
        {
            /* add Index key for column order_id in shipment grid table */
            $table = $this->salesConnection->getTableName('sales_shipment_grid'); // get sales_shipment_grid table name from OMS DB
            $this->salesConnection->addIndex(
                $table,
                $connection->getIndexName($table, ['order_id']),
                ['order_id']
            );
        }
        if (version_compare($context->getVersion(), '2.3.4') < 0) {
            $table = $this->salesConnection->getTableName('sales_order');
            if ($this->salesConnection->isTableExists($table) && !$this->salesConnection->tableColumnExists($table,
                    'published_date')) {
                $this->salesConnection->addColumn(
                    $table, 'published_date',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                        'comment' => 'Published datetime',
                        'nullable' => true,
                        'after' => 'published_message'
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '2.3.5') < 0) {
            $fieldName = 'stock_point_delivery_bucket_id';
            $table = $this->salesConnection->getTableName('sales_shipment');
            if ($this->salesConnection->isTableExists($table) && !$this->salesConnection->tableColumnExists($table,
                    $fieldName)) {
                $this->salesConnection->addColumn(
                    $table, $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'comment' => 'Stock point delivery Id',
                        'nullable' => true
                    ]
                );
            }
            //add field into table grid
            $table = $this->salesConnection->getTableName('sales_shipment_grid');
            if ($this->salesConnection->isTableExists($table) && !$this->salesConnection->tableColumnExists($table,
                    $fieldName)) {
                $this->salesConnection->addColumn(
                    $table, $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'comment' => 'Stock point delivery Id',
                        'nullable' => true
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '2.3.8') < 0) {
            if ($this->salesConnection->tableColumnExists($table_shipment, 'shipment_status')) {
                $this->salesConnection->query("DROP TRIGGER IF EXISTS auto_update_order_shipment_grid;");
                $this->salesConnection->query("
                CREATE TRIGGER auto_update_order_shipment_grid AFTER UPDATE ON $table_shipment
                    FOR EACH ROW
                      UPDATE $table_shipment_grid
                         SET 
                             shipment_status = NEW.shipment_status,
                             shipment_date = NEW.shipment_date,
                             payment_status = NEW.payment_status,
                             payment_date = NEW.payment_date,
                             amount_total = NEW.amount_total,
                             amount_collected = NEW.amount_collected,
                             export_date = NEW.export_date,
                             delivery_date = NEW.delivery_date
                       WHERE entity_id = NEW.entity_id;
                ");
            }
        }

        $setup->endSetup();
    }

    /**
     * @param $shipId
     * @param $status
     * @param $date
     */
    public function updateShipment($shipId, $status, $date)
    {
        $table = $this->salesConnection->getTableName('sales_shipment');
        $tableGrid = $this->salesConnection->getTableName('sales_shipment_grid');
        $sql = "UPDATE $table set `payment_status` = '$status', `payment_date` = '$date' WHERE entity_id = $shipId";
        $this->salesConnection->query($sql);
        $sql1 = "UPDATE $table set `payment_status` = '$tableGrid', `payment_date` = '$date' WHERE entity_id = $shipId";
        $this->salesConnection->query($sql1);
    }

}

