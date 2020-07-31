<?php
// @codingStandardsIgnoreFile
namespace Riki\Prize\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema extends \Riki\Framework\Setup\Version\Schema implements InstallSchemaInterface
{
    public function version100()
    {
        $def = [
            [
                'prize_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'primary' => true, 'nullable' => false],
                'Prize ID'
            ],
            [
                'customer_id',
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true],
                'Customer ID'
            ],
            [
                'sku',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Product Sku'
            ],
            [
                'wbs',
                Table::TYPE_TEXT,
                255,
                [],
                'WBS'
            ],
            [
                'qty',
                Table::TYPE_INTEGER,
                11,
                ['unsigned' => true],
                'Quantity'
            ],
            [
                'status',
                Table::TYPE_BOOLEAN,
                null,
                ['unsigned' => true],
                '0:waiting (Mean the gift is to be added to the next shipment) 1:done (Mean already sent by the sytem) 2:done by manual (Mean already sent offline) 3:stock shortage error (Mean can\'t be sent because there was no stock)'
            ],
            [
                'winning_date',
                Table::TYPE_DATE,
                null,
                [],
                'Winning Date'
            ],
            [
                'order_no',
                Table::TYPE_INTEGER,
                11,
                ['unsigned' => true],
                'Order Id'
            ],
            [
                'campaign_code',
                Table::TYPE_TEXT,
                255,
                [],
                'Campaign'
            ],
            [
                'mail_send_date',
                Table::TYPE_DATE,
                null,
                [],
                'Mail send date: Date when the out of stock notification was sent'
            ]
        ];
        $this->createTable('riki_prize', $def);
        $this->addForeignKey('riki_prize','customer_id','customer_entity','entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE);

    }
}