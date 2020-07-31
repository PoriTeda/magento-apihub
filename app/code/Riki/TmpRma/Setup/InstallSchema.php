<?php
// @codingStandardsIgnoreFile
namespace Riki\TmpRma\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema extends \Riki\Framework\Setup\Version\Schema implements InstallSchemaInterface
{
    public function version100()
    {
        $def = [
            [
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
            ],
            [
                'customer_name',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false
                ],
                'Customer Name'
            ],
            [
                'customer_address',
                Table::TYPE_TEXT,
                256,
                [
                    'nullable' => false
                ],
                'Customer Address'
            ],
            [
                'phone_number',
                Table::TYPE_TEXT,
                25,
                [
                    'nullable' => false
                ],
                'Home Phone Number'
            ],
            [
                'reason_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true
                ],
                'Reason ID'
            ]
        ];
        $this->createTable('riki_tmprma', $def);
        $this->addForeignKey('riki_tmprma', 'reason_id', 'riki_rma_reason', 'id', Table::ACTION_SET_NULL);

        $def = [
            [
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
            ],
            [
                'parent_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true, 'nullable' => false
                ],
                'Temp Rma ID'
            ],
            [
                'sku',
                Table::TYPE_TEXT,
                256,
                [
                    'nullable' => false
                ],
                'Product SKU'
            ],
            [
                'qty',
                Table::TYPE_DECIMAL,
                '12,4',
                [
                    'nullable' => false
                ],
                'Product Quantity'
            ],
            [
                'unit',
                Table::TYPE_BOOLEAN,
                null,
                [
                ],
                'Unit (case or single)'
            ],
        ];
        $this->createTable('riki_tmprma_item', $def);
        $this->addForeignKey('riki_tmprma_item', 'parent_id', 'riki_tmprma', 'id', Table::ACTION_CASCADE);
    }
}
