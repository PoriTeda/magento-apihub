<?php
namespace Riki\CatalogFreeShipping\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    protected $_checkoutConnection;

    protected $_salesConnection;

    /**
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item $quoteItemResource
     * @param \Magento\Sales\Model\ResourceModel\Order\Item $orderItemResource
     */
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote\Item $quoteItemResource,
        \Magento\Sales\Model\ResourceModel\Order\Item $orderItemResource
    ){
        $this->_checkoutConnection = $quoteItemResource->getConnection();
        $this->_salesConnection = $orderItemResource->getConnection();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {

            $this->_checkoutConnection->dropColumn($setup->getTable('quote'), 'free_delivery_wbs');
            $this->_salesConnection->dropColumn($setup->getTable('sales_order'), 'free_delivery_wbs');

            $this->_checkoutConnection->addColumn($setup->getTable('quote_item'),
                'free_delivery_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Free shipping fee WBS'
                ]
            );

            $this->_salesConnection->addColumn($setup->getTable('sales_order_item'),
                'free_delivery_wbs',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Free shipping fee WBS'
                ]
            );
        }

        $setup->endSetup();
    }

}
