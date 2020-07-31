<?php
namespace Riki\CedynaInvoice\Setup;

use Magento\Framework\DB\Ddl\Table;

class InstallSchema extends \Riki\Framework\Setup\Version\Schema implements
    \Magento\Framework\Setup\InstallSchemaInterface
{
    public function version100()
    {
        $defineTable = [
            [
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
            ],
            [
                'import_month',
                Table::TYPE_TEXT,
                6,
                [
                    'nullable' => false
                ],
                'Import month'
            ],
            [
                'target_month',
                Table::TYPE_TEXT,
                6,
                [
                    'nullable' => false
                ],
                'Target month'
            ],
            [
                'business_code',
                Table::TYPE_TEXT,
                40,
                [
                    'nullable' => false
                ],
                'Business code'
            ],
            [
                'shipped_out_date',
                Table::TYPE_DATE,
                null,
                [
                    'nullable' => false
                ],
                'Shipped out date'
            ],
            [
                'data_type',
                Table::TYPE_TEXT,
                2,
                [
                    'nullable' => false
                ],
                'Data Type'
            ],
            [
                'row_total',
                Table::TYPE_DECIMAL,
                '12,4',
                [
                    'nullable' => false
                ],
                'Row total'
            ],
            [
                'shipment_increment_id',
                Table::TYPE_TEXT,
                50,
                [
                    'nullable' => true
                ],
                'Shipment number'
            ],
            [
                'product_line_name',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true
                ],
                'Product line name'
            ],
            [
                'unit_price',
                Table::TYPE_DECIMAL,
                '12,4',
                [
                    'nullable' => true
                ],
                'Unit price'
            ],
            [
                'qty',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Quantity'
            ],
            [
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['default' => Table::TIMESTAMP_INIT],
                'Creation Time'
            ],
            [
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'default' => Table::TIMESTAMP_INIT_UPDATE
                ],
                'Updated at'
            ]
        ];
        $this->createTable('riki_cedyna_invoice', $defineTable);
    }
}
