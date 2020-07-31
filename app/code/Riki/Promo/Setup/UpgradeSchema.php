<?php
// @codingStandardsIgnoreFile
namespace Riki\Promo\Setup;


use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Quote\Setup\QuoteSetupFactory;

class UpgradeSchema implements UpgradeSchemaInterface {

    protected $_salesCollection;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ){
        $this->_salesCollection = $resourceConnection->getConnection('sales');
    }

    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context )
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();
        
        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            if($connection->isTableExists('amasty_ampromo_rule')){
                $table = $setup->getTable('amasty_ampromo_rule');

                $connection->addColumn($table, 'att_visible_cart', [
                    'type' => Table::TYPE_BOOLEAN,
                    null,
                    'default'   =>  1,
                    'comment' => 'Visible free gift in cart?',
                ]);

                $connection->addColumn($table, 'att_visible_user_account', [
                    'type' => Table::TYPE_BOOLEAN,
                    null,
                    'default'   =>  1,
                    'comment' => 'Visible free gift in user account?',
                ]);
            }

            $table = $setup->getTable('sales_shipment_item');

            $this->_salesCollection->addColumn($table, 'visible_user_account', [
                'type' => Table::TYPE_BOOLEAN,
                null,
                'default'   =>  1,
                'comment' => 'Visible in user account?',
            ]);

            $table = $setup->getTable('sales_shipment');

            $this->_salesCollection->addColumn($table, 'visible_user_account', [
                'type' => Table::TYPE_BOOLEAN,
                null,
                'default'   =>  1,
                'comment' => 'Visible in user account?',
            ]);

            $table = $setup->getTable('sales_shipment_grid');

            $this->_salesCollection->addColumn($table, 'visible_user_account', [
                'type' => Table::TYPE_BOOLEAN,
                null,
                'default'   =>  1,
                'comment' => 'Visible in user account?',
            ]);
        }

        $setup->endSetup();
    }
}