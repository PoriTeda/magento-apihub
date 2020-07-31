<?php
namespace Riki\Rma\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class UpgradeSchema extends \Riki\Framework\Setup\Version\Schema implements \Magento\Framework\Setup\UpgradeSchemaInterface
{
    public function version100()
    {
        $this->addColumn('magento_rma', 'reasoncode_id', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'comment' => 'Reason code (ref to riki_rma_reasoncode table)'
        ]);
        $this->addForeignKey('magento_rma', 'reasoncode_id', 'riki_rma_reasoncode', 'reasoncode_id');
    }

    public function version101()
    {
        $this->dropColumn('magento_rma', 'reasoncode_id');
        $this->dropTable('riki_rma_reasoncode');

        $def = [
            [
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
            ],
            [
                'code',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false
                ],
                'Reason Code'
            ],
            [
                'description',
                Table::TYPE_TEXT,
                256,
                [],
                'Description'
            ],
            [
                'due_to',
                Table::TYPE_INTEGER,
                null,
                [],
                'Due to'
            ],
        ];
        $this->createTable('riki_rma_reason', $def);
        $this->addColumn('riki_rma_reason', 'reason_id', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'comment' => 'Reason ID (ref to riki_rma_reason table)'
        ]);
        $this->addForeignKey('magento_rma', 'reason_id', 'riki_rma_reason', 'id');
        $this->addIndex('riki_rma_reason', ['code'], null, AdapterInterface::INDEX_TYPE_UNIQUE);
    }

    public function version110()
    {
        $this->addColumn('magento_rma', 'refund_method', [
            'type' => Table::TYPE_TEXT,
            'length' => 32,
            'comment' => 'Refund method (will be same dataset with payment method on sales_order_payment)'
        ]);
    }

    public function version111()
    {
        $this->addColumn('magento_rma_grid', 'order_type', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Order Type (SPOT, SUBSCRIPTION or AMBASSADOR)'
        ]);
    }

    public function version112()
    {
        $this->addColumn('magento_rma_grid', 'customer_group', [
            'type' => Table::TYPE_SMALLINT,
            'length' => 5,
            'comment' => 'Customer Group'
        ]);
    }

    public function version120()
    {
        $this->addColumn('magento_rma', 'refund_allowed', [
            'type' => Table::TYPE_BOOLEAN,
            'default'   =>  0,
            'comment' => 'Allow to refund?'
        ]);
    }

    public function version130()
    {
        $this->dropTable('riki_rma_refund_rule');
        $def = [
            [
                'rule_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'primary' => true,
                    'nullable' => false,
                    'unsigned' => true,
                    'identity' => true
                ],
                'Refund Rule'
            ],
            [
                'type',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Type of return order: full or partial'
            ],
            [
                'reason_code',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Reason codes'
            ],
            [
                'g_j',
                Table::TYPE_TEXT,
                16,
                [],
                'Shopping Point Usage (g) <-> Cash Customer Paid (j)'
            ],
            [
                'g_e',
                Table::TYPE_TEXT,
                16,
                [],
                'Shopping Point Usage (g) <-> Purchased Sub Total (e)'
            ],
            [
                'g_a',
                Table::TYPE_TEXT,
                16,
                [],
                'Shopping Point Usage (g) <-> Returned product (a)'
            ],
            [
                'refund_delivery_fee',
                Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true],
                'Refund Delivery Fee'
            ],
            [
                'additional_delivery_fee',
                Table::TYPE_DECIMAL,
                '10,2',
                ['unsigned' => true],
                'Additional Delivery Fee'
            ],
            [
                'refund_cash_delivery_fee',
                Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true],
                'Refund cash on delivery fee'
            ],
            [
                'give_back_point_retractable',
                Table::TYPE_TEXT,
                16,
                [],
                'Give Back Point: Case of Issued Points Retractable'
            ],
            [
                'give_back_point_not_retractable',
                Table::TYPE_TEXT,
                16,
                [],
                'Give Back Point: Case of Issued Points Not Retractable'
            ],
            [
                'refund_product_price_retractable',
                Table::TYPE_TEXT,
                16,
                [],
                'Refund Product Price: Case of Issued Points Retractable'
            ],
            [
                'refund_product_price_not_retractable',
                Table::TYPE_TEXT,
                16,
                [],
                'Refund Product Price: Case of Issued Points Not Retractable'
            ],
        ];
        $this->createTable('riki_rma_refund_rule', $def);
    }

    public function version131()
    {
        $this->changeColumn('magento_rma_grid', 'customer_group', 'customer_type', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Customer Type (membership)'
        ]);
    }

    public function version132()
    {
        $this->addColumn('riki_rma_reason', 'deleted', [
            'type' => Table::TYPE_BOOLEAN,
            'default'   =>  0,
            'comment' => 'Status (deleted?)'
        ]);
    }

    public function version133()
    {
        $this->addColumn('magento_rma', 'return_status', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'comment' => 'Return status'
        ]);
        $this->addColumn('magento_rma', 'refund_status', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'comment' => 'Refund status'
        ]);
    }

    public function version134()
    {
        $this->addColumn('magento_rma', 'returned_date', [
            'type' => Table::TYPE_DATE,
            'comment' => 'Returned date'
        ]);
        $this->addColumn('magento_rma', 'full_partial', [
            'type' => Table::TYPE_SMALLINT,
            'unsigned' => true,
            'comment' => 'Full or partial return'
        ]);
        $this->addColumn('magento_rma', 'substitution_order', [
            'type' => Table::TYPE_TEXT,
            'length' => '16',
            'comment' => 'Substitution Order'
        ]);
    }

    public function version135()
    {
        $this->addColumn('magento_rma', 'returned_warehouse', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'comment' => 'Returned date'
        ]);
        $this->addForeignKey('magento_rma', 'returned_warehouse', 'pointofsale', 'place_id', Table::ACTION_RESTRICT);
    }

    public function version136()
    {
        $this->addColumn('riki_rma_reason', 'sap_code', [
            'type' => Table::TYPE_TEXT,
            'length' => 16,
            'comment' => 'SAP code'
        ]);
    }

    public function version140()
    {
        $this->dropColumn('riki_rma_reason', 'description');
        $this->addColumn('riki_rma_reason', 'description_en', [
            'type' => Table::TYPE_TEXT,
            'comment' => 'Description EN'
        ]);
        $this->addColumn('riki_rma_reason', 'description_jp', [
            'type' => Table::TYPE_TEXT,
            'comment' => 'Description JP'
        ]);
    }

    public function version141()
    {
        $this->addColumn('magento_rma', 'rma_shipment_number', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Shipment number (Only if the Original order is using payment method "Cash on delivery"'
        ]);
        $this->addColumn('magento_rma', 'updated_at', [
            'type' => Table::TYPE_TIMESTAMP,
            'default' => Table::TIMESTAMP_INIT_UPDATE,
            'comment' => 'Last Update Timestamp'
        ]);
        $this->addColumn('magento_rma', 'approval_date', [
            'type' => Table::TYPE_TIMESTAMP,
            'comment' => 'Set when Supply chain approve the Return'
        ]);
        $this->addColumn('magento_rma_grid', 'returned_date', [
            'type' => Table::TYPE_DATE,
            'comment' => 'Returned date'
        ]);
        $this->addColumn('magento_rma_grid', 'return_status', [
            'type' => Table::TYPE_INTEGER,
            'comment' => 'Return status'
        ]);
        $this->addColumn('magento_rma_grid', 'reason_id', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'comment' => 'Reason ID (ref to riki_rma_reason table)'
        ]);
        $this->addColumn('magento_rma_grid', 'payment_status', [
            'type' => Table::TYPE_TEXT,
            'length' => 100,
            'comment' => 'Payment status of Order'
        ]);
        $this->addColumn('magento_rma_grid', 'refund_method', [
            'type' => Table::TYPE_TEXT,
            'length' => 32,
            'comment' => 'Refund method (will be same dataset with payment method on sales_order_payment)'
        ]);
    }

    public function version142()
    {
        $this->addColumn('magento_rma_item_entity', 'unit_case', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Unit Case'
        ]);
        $this->addColumn('magento_rma_item_entity', 'return_amount', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Refund amount'
        ]);
        $this->addColumn('magento_rma_item_entity', 'return_amount_adj', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Refund amount adj'
        ]);
        $this->addColumn('magento_rma_item_entity', 'return_wrapping', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Refund wrapping'
        ]);
        $this->addColumn('magento_rma_item_entity', 'return_wrapping_fee_adj', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Refund Wrapping adj'
        ]);
    }

    public function version143()
    {
        $this->changeColumn('magento_rma_item_entity', 'return_wrapping', 'return_wrapping_fee', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Refund wrapping'
        ]);
        $this->addColumn('magento_rma', 'total_cancel_point', [
            'type' => Table::TYPE_INTEGER,
            'comment' => 'Total cancel point'
        ]);
        $this->addColumn('magento_rma', 'total_cancel_point_adj', [
            'type' => Table::TYPE_INTEGER,
            'comment' => 'Total cancel point adjustment'
        ]);
        $this->addColumn('magento_rma', 'total_return_point', [
            'type' => Table::TYPE_INTEGER,
            'comment' => 'Total return point'
        ]);
        $this->addColumn('magento_rma', 'total_return_point_adj', [
            'type' => Table::TYPE_INTEGER,
            'comment' => 'Total return point adjustment'
        ]);
        $this->addColumn('magento_rma', 'return_shipping_fee', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Return shipping fee'
        ]);
        $this->addColumn('magento_rma', 'return_shipping_fee_adj', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Return shipping fee adjustment'
        ]);
        $this->addColumn('magento_rma', 'return_payment_fee', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Return payment fee'
        ]);
        $this->addColumn('magento_rma', 'return_payment_fee_adj', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Return payment fee adjustment'
        ]);
        $this->addColumn('magento_rma', 'total_return_amount', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Total return amount'
        ]);
        $this->addColumn('magento_rma', 'total_return_amount_adj', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Total return amount adjustment'
        ]);
    }

    public function version144()
    {
        $this->addColumn('magento_rma_grid', 'total_return_amount', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Total return amount'
        ]);
        $this->addColumn('magento_rma_grid', 'approval_date', [
            'type' => Table::TYPE_TIMESTAMP,
            'comment' => 'Set when Supply chain approve the Return'
        ]);
        $this->addColumn('magento_rma_grid', 'updated_at', [
            'type' => Table::TYPE_TIMESTAMP,
            'default' => Table::TIMESTAMP_INIT_UPDATE,
            'comment' => 'Last Update Timestamp'
        ]);
        $this->addColumn('magento_rma_grid', 'full_partial', [
            'type' => Table::TYPE_SMALLINT,
            'unsigned' => true,
            'comment' => 'Full or partial return'
        ]);
        $this->addColumn('magento_rma_grid', 'substitution_order', [
            'type' => Table::TYPE_TEXT,
            'length' => '16',
            'comment' => 'Substitution Order'
        ]);
        $this->addColumn('magento_rma_grid', 'rma_shipment_number', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Shipment number (Only if the Original order is using payment method "Cash on delivery"'
        ]);

        /*sync current data from magento rma to rma_grid for new columns*/
        $conn = $this->getConnection($this->getTable('magento_rma'));
        $select = $conn->select();
        $select->join(
            ['rma'=>$this->getTable('magento_rma')],
            'rma.entity_id = rma_grid.entity_id',
            [
                'total_return_amount' => 'total_return_amount',
                'approval_date' => 'approval_date',
                'updated_at' => 'updated_at',
                'full_partial' => 'full_partial',
                'substitution_order' => 'substitution_order',
                'rma_shipment_number' => 'rma_shipment_number'
            ]
        );
        $conn->query(
            $select->crossUpdateFromSelect(['rma_grid' => $this->getTable('magento_rma_grid')])
        );
    }

    public function version145()
    {
        $this->addColumn('magento_rma_grid', 'refund_status', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'comment' => 'Refund status'
        ]);
        /*sync current data from magento rma to rma_grid for new columns*/
        $conn = $this->getConnection($this->getTable('magento_rma'));
        $select = $conn->select();
        $select->join(
            ['rma' => $this->getTable('magento_rma')],
            'rma.entity_id = rma_grid.entity_id',
            ['refund_status' => 'refund_status']
        );
        $conn->query(
            $select->crossUpdateFromSelect(['rma_grid' => $this->getTable('magento_rma_grid')])
        );
    }

    public function version146()
    {
        $this->addColumn('magento_rma', 'is_exported_sap', [
            'type' => Table::TYPE_SMALLINT,
            'unsigned' => true,
            'comment' => 'Initially empty, will be filled when export to SAP'
        ]);
        $this->addColumn('magento_rma', 'total_cancel_point_adjusted', [
            'type' => Table::TYPE_INTEGER,
            'comment' => 'Final total cancel point adjusted (after calculate)'
        ]);
        $this->addColumn('magento_rma', 'total_return_point_adjusted', [
            'type' => Table::TYPE_INTEGER,
            'comment' => 'Final total return point adjusted (after calculate)'
        ]);
    }

    public function version147()
    {
        $this->addColumn('magento_rma', 'creditmemo_id', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'comment' => 'Credit memo entity id'
        ]);
        $this->addColumn('magento_rma', 'creditmemo_increment_id', [
            'type' => Table::TYPE_TEXT,
            'length' => 50,
            'comment' => 'Credit memo increment id'
        ]);
        $this->addColumn('magento_rma_grid', 'creditmemo_id', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'comment' => 'Credit memo entity id'
        ]);
        $this->addColumn('magento_rma_grid', 'creditmemo_increment_id', [
            'type' => Table::TYPE_TEXT,
            'length' => 50,
            'comment' => 'Credit memo increment id'
        ]);
    }

    public function version148()
    {
        $this->addColumn('sales_order_status_history', 'reason_id', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'comment' => 'Season id'
        ]);
    }

    public function version149()
    {
        $this->addColumn('magento_rma', 'export_sap_date', [
            'type' => Table::TYPE_DATETIME,
            'comment' => 'Export SAP Date'
        ]);
        $this->addColumn('magento_rma', 'sap_ren_flg', [
            'type' => Table::TYPE_BOOLEAN,
            'comment' => 'Used to export SAP',
            'default' => 0
        ]);
    }

    public function version150()
    {
        $this->addColumn('magento_rma_grid', 'is_exported_sap', [
            'type' => Table::TYPE_SMALLINT,
            'unsigned' => true,
            'comment' => 'Initially empty, will be filled when export to SAP'
        ]);
        $this->addColumn('magento_rma_grid', 'export_sap_date', [
            'type' => Table::TYPE_DATETIME,
            'comment' => 'Export SAP Date'
        ]);
        $this->addColumn('magento_rma_grid', 'comment', [
            'type' => Table::TYPE_TEXT,
            'comment' => 'Comment text of rma'
        ]);
        $this->addIndex('magento_rma_grid', ['comment'], null, AdapterInterface::INDEX_TYPE_FULLTEXT);
    }

    public function version151()
    {
        $this->addColumn('magento_rma', 'return_shipping_fee_adjusted', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Return Shipping Fee Adjusted'
        ]);
        $this->addColumn('magento_rma', 'return_payment_fee_adjusted', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Return Payment Fee Adjusted'
        ]);

        $this->modifyColumn('magento_rma', 'returned_date', [
            'type' => Table::TYPE_DATETIME,
            'comment' => 'Returned date'
        ]);
        $this->modifyColumn('magento_rma_grid', 'returned_date', [
            'type' => Table::TYPE_DATETIME,
            'comment' => 'Returned date'
        ]);
    }

    public function version152()
    {
        $this->modifyColumn('magento_rma', 'refund_allowed', [
            'type' => Table::TYPE_BOOLEAN,
            'comment' => 'Allow to refund?'
        ]);
    }

    public function version153()
    {
        $this->addColumn('magento_rma', 'total_return_amount_adjusted', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Total return amount adjusted'
        ]);
        $this->changeColumn('magento_rma_grid', 'total_return_amount', 'total_return_amount_adjusted', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Total return amount adjusted'
        ]);
    }

    public function version154()
    {
        $this->addColumn('quote_item', 'foc_wbs', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Free of charge order WBS'
        ]);

        $this->addColumn('sales_order_item', 'foc_wbs', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Free of charge order WBS'
        ]);

        $this->addColumn('quote', 'free_delivery_wbs', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Free shipping fee WBS'
        ]);

        $this->addColumn('sales_order', 'free_delivery_wbs', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Free shipping fee WBS'
        ]);

        $this->addColumn('magento_rma_item_entity', 'free_of_charge', [
            'type' => Table::TYPE_SMALLINT,
            'length' => 1,
            'comment' => 'Free of charge order (from order_item)'
        ]);
        $this->addColumn('magento_rma_item_entity', 'booking_wbs', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Product booking WBS (from order_item)'
        ]);
        $this->addColumn('magento_rma_item_entity', 'foc_wbs', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Free of charge order WBS (from order_item)'
        ]);
    }

    public function version155()
    {
        $this->addColumn('salesrule', 'ignore_warning_rma', [
            'type' => Table::TYPE_SMALLINT,
            'length' => 1,
            'comment' => 'Warning message on rma will ignore this rule or not',
            'default' => \Riki\Rma\Api\Data\SalesRule\IgnoreWarningRmaInterface::NO
        ]);
    }

    public function version157()
    {
        $this->addColumn('magento_rma_item_entity', 'gps_price_ec', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Gps price ec'
        ]);
        $this->addColumn('magento_rma_item_entity', 'material_type', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Material type'
        ]);
        $this->addColumn('magento_rma_item_entity', 'sales_organization', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Sales organization'
        ]);
        $this->addColumn('magento_rma_item_entity', 'sap_interface_excluded', [
            'type' => Table::TYPE_BOOLEAN,
            'default' => 0,
            'comment' => 'SAP interface excluded flag'
        ]);
    }

    public function version159()
    {
        $this->addColumn('magento_rma_grid', 'payment_agent', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Payment agent From(sales_order.payment_agent)'
        ]);
        $this->addColumn('magento_rma_grid', 'payment_date', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Payment date From(sales_order_shipment.payment_date)'
        ]);
        $this->addColumn('magento_rma_grid', 'consumer_db_id', [
            'type' => Table::TYPE_TEXT,
            'length' => 32,
            'comment' => 'Consumer db id From(catalog_customer_entity_*)'
        ]);
        $this->addColumn('magento_rma_grid', 'payment_method', [
            'type' => Table::TYPE_TEXT,
            'length' => 32,
            'comment' => 'Payment method From(sales_order_payment)'
        ]);
    }
  
    public function version158()
    {
        $this->addColumn('magento_rma', 'return_shipping_fee_adjusted', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Return Shipping Fee Adjusted'
        ]);
        $this->addColumn('magento_rma', 'return_payment_fee_adjusted', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Return Payment Fee Adjusted'
        ]);

        $this->modifyColumn('magento_rma', 'returned_date', [
            'type' => Table::TYPE_DATETIME,
            'comment' => 'Returned date'
        ]);
        $this->modifyColumn('magento_rma_grid', 'returned_date', [
            'type' => Table::TYPE_DATETIME,
            'comment' => 'Returned date'
        ]);
    }

    public function version161()
    {
        $this->addColumn('magento_rma', 'extension_data', [
            'type' => Table::TYPE_TEXT,
            'comment' => 'Extension data, will be stored by JSON type'
        ]);
    }

    public function version162()
    {
        $this->addColumn('magento_rma', 'is_bi_exported', [
            'type' => Table::TYPE_BOOLEAN,
            'default' => 0,
            'comment' => 'Is exported to Bi?'
        ]);
    }

    public function version163()
    {
        $this->addColumn('magento_rma_grid', 'comment', [
            'type' => Table::TYPE_TEXT,
            'comment' => 'Comment text of rma'
        ]);
    }

    public function version166()
    {
        $this->addColumn('magento_rma_item_entity', 'return_amount_excl_tax', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'default' => 0,
            'comment' => 'Return amount exclude tax'
        ]);
        $this->addColumn('magento_rma_item_entity', 'return_tax_amount', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'default' => 0,
            'comment' => 'Return tax amount'
        ]);
        $this->addColumn('magento_rma_item_entity', 'return_amount_adj_excl_tax', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'default' => 0,
            'comment' => 'Return amount adjustment exclude tax'
        ]);
        $this->addColumn('magento_rma_item_entity', 'return_tax_amount_adj', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'default' => 0,
            'comment' => 'Return tax amount adjustment'
        ]);
        $this->addColumn('magento_rma', 'return_amount_excl_tax', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'default' => 0,
            'comment' => 'Return amount exclude tax'
        ]);
        $this->addColumn('magento_rma', 'return_tax_amount', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'default' => 0,
            'comment' => 'Return tax amount'
        ]);
        $this->addColumn('magento_rma', 'return_amount_adj_excl_tax', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'default' => 0,
            'comment' => 'Return amount adjustment exclude tax'
        ]);
        $this->addColumn('magento_rma', 'return_tax_amount_adj', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'default' => 0,
            'comment' => 'Return tax amount adjustment'
        ]);
    }

    public function version167()
    {
        $this->addColumn('magento_rma_item_entity', 'commission_amount', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'default' => 0,
            'comment' => 'Return item commission amount'
        ]);
        $this->addColumn('magento_rma', 'commission_amount', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'default' => 0,
            'comment' => 'Return commission amount'
        ]);
    }

    public function version171()
    {
        $this->addColumn('magento_rma', 'refund_without_product', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'default' => 0,
            'comment' => 'Refund amount without product'
        ]);
    }

    public function version172()
    {
        $this->addColumn('magento_rma_grid', 'refund_allowed', [
            'type' => Table::TYPE_BOOLEAN,
            'default' => 0,
            'comment' => 'Is refund allowed?'
        ]);
    }

    public function version173()
    {
        $this->addColumn('magento_rma_grid', 'is_without_goods', [
            'type' => Table::TYPE_BOOLEAN,
            'default' => 0,
            'comment' => 'Is without goods?'
        ]);
    }

    public function version174()
    {
        $this->addColumn('magento_rma_item_entity', 'distribution_channel', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length' => 2,
            'nullable' => true,
            'comment' => 'Distribution channel'
        ]);
    }

    public function version175()
    {
        $connection = $this->getConnection('magento_rma');

        $connection->update($connection->getTableName('magento_rma'), [
            'returned_date' => new \Zend_Db_Expr('date_requested'),
        ], 'returned_date IS NULL');

        $connection->update($connection->getTableName('magento_rma_grid'), [
            'returned_date' => new \Zend_Db_Expr('date_requested'),
        ], 'returned_date IS NULL');

        $connection->addColumn($connection->getTableName('magento_rma'), 'trigger_cancel_point', [
            'type' => Table::TYPE_BOOLEAN,
            'default' => 0,
            'comment' => 'Trigger cancel used point of order'
        ]);

        $connection->addIndex(
            $connection->getTableName('magento_rma'),
            $connection->getIndexName($connection->getTableName('magento_rma'), ['order_id', 'trigger_cancel_point']),
            ['order_id', 'trigger_cancel_point']
        );

        $connection->addColumn($connection->getTableName('magento_rma'), 'customer_point_balance', [
            'type' => Table::TYPE_INTEGER,
            'comment' => 'Customer point balance'
        ]);

        $connection->addColumn($connection->getTableName('magento_rma'), 'earned_point', [
            'type' => Table::TYPE_INTEGER,
            'comment' => 'Earned point'
        ]);

        $connection->addColumn($connection->getTableName('magento_rma'), 'returnable_point_amount', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'default' => 0,
            'comment' => 'Returnable point amount'
        ]);
    }

    public function version180()
    {
        $reviewTable = $this->getTable('riki_rma_review_cc');

        $this->dropTable($reviewTable);

        $reviewTableDefine = [
            [
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
            ],
            [
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'The date time that call center clicks "Review by CC" button'
            ],
            [
                'executed_from',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [],
                'The date time that cron starts proceeding for returns'
            ],
            [
                'executed_to',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [],
                'The date time that cron finishes proceeding for returns'
            ],
            [
                'executed_by',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                40,
                ['nullable' => false],
                'The admin user that clicks "Review by CC" button'
            ],
            [
                'total_returns',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' =>  0],
                'Total returns found and met the conditions'
            ],
            [
                'total_success_returns',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' =>  0],
                'Total returns executed successfully'
            ],
            [
                'total_failed_returns',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' =>  0],
                'Total returns executed unsuccessfully'
            ],
            [
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                1,
                ['nullable' => false, 'default' =>  1],
                'Status (New, Running, Done)'
            ],
        ];

        $this->createTable($reviewTable, $reviewTableDefine);

        $reviewItemTable = $this->getTable('riki_rma_review_cc_item');

        $this->dropTable($reviewItemTable);

        $reviewItemTableDefine = [
            [
                'item_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
            ],
            [
                'review_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Review CC Id'
            ],
            [
                'rma_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'RMA Id'
            ],
            [
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                1,
                [],
                'Status (NULL, Success, Failed)'
            ],
        ];

        $this->createTable($reviewItemTable, $reviewItemTableDefine);

        $this->addForeignKey($reviewItemTable, 'review_id', $reviewTable, 'entity_id', \Magento\Framework\DB\Adapter\AdapterInterface::FK_ACTION_CASCADE);
        $this->addForeignKey($reviewItemTable, 'rma_id', $this->getTable('magento_rma'), 'entity_id', \Magento\Framework\DB\Adapter\AdapterInterface::FK_ACTION_CASCADE);
    }

    public function version190()
    {
        $connection = $this->getConnection('magento_rma');

        $tableName = $connection->getTableName('riki_rma_action_queue');

        if ($connection->isTableExists($tableName)) {
            return;
        }

        $table = $connection->newTable(
            $tableName
        )->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        )->addColumn(
            'rma_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'RMA Id'
        )->addColumn(
            'action',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Action requested'
        )->addColumn(
            'requested_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'The time that call center request massaction'
        )->addColumn(
            'executed_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [],
            'The time that cron starts proceeding for returns'
        )->addColumn(
            'requested_by',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            40,
            ['nullable' => false],
            'The admin user that request massaction'
        )->addColumn(
            'message',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Message'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            1,
            ['nullable' => false, 'default' =>  1],
            'Status (Waiting, Success, Failure)'
        )->addIndex(
            $connection->getIndexName(
                $tableName,
                ['rma_id', 'action'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['rma_id', 'action'],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        )->addIndex(
            $connection->getIndexName(
                $tableName,
                ['status']
            ),
            ['status']
        )->setComment(
            'Returns were requested by mass action'
        );

        $connection->createTable($table);

        $this->addForeignKey($tableName, 'rma_id', $this->getTable('magento_rma'), 'entity_id', \Magento\Framework\DB\Adapter\AdapterInterface::FK_ACTION_CASCADE);
    }

    public function version200()
    {
        $connection = $this->getConnection('magento_rma');

        $tables = [
            $connection->getTableName('magento_rma'),
            $connection->getTableName('magento_rma_grid')
        ];

        foreach ($tables as $table) {
            $connection->changeColumn(
                $table,
                'approval_date',
                'return_approval_date',
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'comment' => 'Approved return date'
                ],
                false
            );

            $connection->addColumn(
                $table,
                'refund_approval_date',
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'comment' => 'Approved refund date'
                ]
            );
        }
    }

    /**
     * Upgrade verion to 2.0.1
     */
    public function version201()
    {
        $this->modifyColumn('magento_rma', 'refund_allowed', [
            'type' => Table::TYPE_BOOLEAN,
            'default' => 0,
            'nullable' => false
        ]);
        $this->modifyColumn('magento_rma_grid', 'refund_allowed', [
            'type' => Table::TYPE_BOOLEAN,
            'default' => 0,
            'nullable' => false
        ]);
    }

    public function version212()
    {
        $connection = $this->getConnection('magento_rma');

        $connection->addColumn(
            $connection->getTableName('magento_rma_grid'),
            'shipment_status',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Status of shipment from rma shipment number column'
            ]
        );
    }

    public function version213()
    {
        $connection = $this->getConnection('magento_rma');

        $connection->dropColumn(
            $connection->getTableName('magento_rma_grid'),
            'shipment_status'
        );

        $connection->addColumn(
            $connection->getTableName('magento_rma_grid'),
            'is_shipments_rejected',
            [
                'type' => Table::TYPE_BOOLEAN,
                'default' => 0,
                'nullable' => false,
                'comment' => 'All of order shipments status are rejected?'
            ]
        );
    }

    public function version214()
    {
        $connection = $this->getConnection('magento_rma');

        $connection->addColumn(
            $connection->getTableName('riki_rma_action_queue'),
            'amounts_data',
            [
                'type' => Table::TYPE_TEXT,
                'comment' => 'Value of amount fields after run cron mass action'
            ]
        );
    }

    public function version220()
    {
        $connection = $this->getConnection('magento_rma');

        $connection->modifyColumn(
            $connection->getTableName('magento_rma'),
            'returned_date',
            [
                'type' => Table::TYPE_DATE,
                'comment' => 'Returned date'
            ]
        );

        $connection->modifyColumn(
            $connection->getTableName('magento_rma_grid'),
            'returned_date',
            [
                'type' => Table::TYPE_DATE,
                'comment' => 'Returned date'
            ]
        );
    }

    public function version230()
    {
        $connection = $this->getConnection('magento_rma');

        $connection->dropColumn(
            $connection->getTableName('magento_rma_grid'),
            'is_shipments_rejected'
        );
    }

    public function version240()
    {
        $this->addColumn('magento_rma_item_entity', 'bundle_item_earned_point', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            'nullable' => true,
            'default' => 0,
            'comment' => 'Bundle item earned point'
        ]);
    }
}
