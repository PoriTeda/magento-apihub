<?php
// @codingStandardsIgnoreFile
namespace Riki\GiftWrapping\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * @var QuoteSetupFactory
     */
    protected $quoteSetupFactory;

    /**
     * @var ConfigInterface
     */
    protected $productTypeConfig;

    /**
     * @var CategorySetupFactory
     */
    protected $categorySetupFactory;

    protected $_config;

    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        QuoteSetupFactory $quoteSetupFactory,
        Config $config,
        CategorySetupFactory $categorySetupFactory,
        ConfigInterface $productTypeConfig
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
        $this->productTypeConfig = $productTypeConfig;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->_config = $config ;
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {

        $setup->startSetup();
        /**
         * Add gift wrapping attributes for catalog product entity
         */
        if (version_compare($context->getVersion(), '2.0.0', '<')) {
            $table = $setup->getTable('magento_giftwrapping');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->run("ALTER TABLE {$table} ADD COLUMN gift_code VARCHAR(255)
                NULL AFTER wrapping_id ");
                $setup->run("ALTER TABLE {$table} ADD COLUMN sap_code VARCHAR(255)
                NULL AFTER wrapping_id ");
                $setup->run("ALTER TABLE {$table} ADD COLUMN gift_name VARCHAR(255)
                NULL AFTER gift_code ");
            }
        }

        if (version_compare($context->getVersion(), '2.0.1') < 0) {
            $this->_config->saveConfig('sales/gift_options/wrapping_allow_order', '0','default', 0);
            $this->_config->saveConfig('sales/gift_options/wrapping_allow_items', '0', 'default', 0);
        }

        if (version_compare($context->getVersion(), '2.0.5') < 0) {

            $tableName = $setup->getTable('magento_giftwrapping');
            if ($setup->getConnection()->isTableExists($tableName) == true) {

                $connection = $setup->getConnection();

                $connection->addColumn(
                    $tableName,
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                    'Created At'
                );
                $connection->addColumn(
                    $tableName,
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Updated At'
                );
            }
        }


        if (version_compare($context->getVersion(), '2.0.6') < 0) {
            $tableName = $setup->getTable('magento_giftwrapping');
            $column = 'updated_at';

            $connection = $setup->getConnection();

            if ($connection->isTableExists($tableName) && $connection->tableColumnExists($tableName, $column)) {
                $connection->modifyColumn(
                    $tableName, $column,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null,
                        'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                        'comment' => 'Updated at'
                    ]
                );

                $connection->update(
                    $tableName, ['updated_at' => NULL], ['updated_at' => '0000-00-00 00:00:00']
                );
            }
        }

        $setup->endSetup();
    }
}