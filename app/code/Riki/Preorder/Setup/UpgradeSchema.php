<?php
namespace Riki\Preorder\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema extends \Riki\Framework\Setup\Version\Schema implements UpgradeSchemaInterface
{
    public function version101()
    {
        $this->addColumn('riki_preorder_order_item_preorder', 'is_confirmed', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            'default' => 0,
            'comment' => 'send mail?'
        ]);
    }

    public function version106()
    {
        $this->addIndex('riki_preorder_order_item_preorder', ['is_preorder']);
    }
}
