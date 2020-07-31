<?php
// @codingStandardsIgnoreFile
namespace Riki\Prize\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class UpgradeSchema extends \Riki\Framework\Setup\Version\Schema implements UpgradeSchemaInterface
{
    public function version101()
    {
        $this->addColumn('sales_order_item', 'visible_user_account', [
            'type' => Table::TYPE_BOOLEAN,
            'default' => '1',
            'comment' => 'Visible user account'
        ]);
        $this->addColumn('quote_item', 'visible_user_account', [
            'type' => Table::TYPE_BOOLEAN,
            'default' => '1',
            'comment' => 'Visible user account'
        ]);
    }

    public function version102()
    {
        $this->dropForeignKey('riki_prize', [
            'riki_prize','customer_id','customer_entity','entity_id'
        ]);
        $this->changeColumn('riki_prize', 'customer_id', 'consumer_db_id', [
            'type' => Table::TYPE_INTEGER,
            'length' => 10,
            'unsigned' => true,
            'comment' => 'Consumer Db Id of customer'
        ]);
    }

    public function version103()
    {
        $this->modifyColumn('riki_prize', 'consumer_db_id', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'nullable' => false
        ]);
    }

    public function version104()
    {
        $this->addColumn('sales_order_item', 'prize_id', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'nullable' => true,
            'comment' => 'Prize id, this is flag to detect item is a prize, ref to prize_id from riki_prize'
        ]);
        $this->addForeignKey('sales_order_item', 'prize_id', 'riki_prize', 'prize_id', AdapterInterface::FK_ACTION_RESTRICT);

        $this->addColumn('quote_item', 'prize_id', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'comment' => 'Prize id, this is flag to detect item is a prize, ref to prize_id from riki_prize'
        ]);
        $this->addForeignKey('quote_item', 'prize_id', 'riki_prize', 'prize_id', AdapterInterface::FK_ACTION_RESTRICT);
    }

    public function version105()
    {
        $this->addColumn('riki_prize', 'orm_id', [
            'type' => Table::TYPE_INTEGER,
            'length' => 10,
            'unsigned' => true,
            'comment' => 'Row  ID'
        ]);
    }
}