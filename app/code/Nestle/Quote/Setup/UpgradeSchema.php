<?php
// @codingStandardsIgnoreFile
namespace Nestle\Quote\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resourceConnection;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->_resourceConnection = $resourceConnection;
    }
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.0') < 0) {
            $checkoutConnection = $this->_resourceConnection->getConnection('checkout');
            if ($checkoutConnection->tableColumnExists('quote_address', 'lastname')) {
                $checkoutConnection->modifyColumn(
                    $setup->getTable('quote_address'),
                    'lastname',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'default' => null
                    ]
                );
            }
        }

        $setup->endSetup();
    }
}