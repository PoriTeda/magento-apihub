<?php
// @codingStandardsIgnoreFile
namespace Riki\Loyalty\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * InstallSchema constructor.
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        /**
         * edit table sales_order, add column used_point, bonus_point
         */
        $saleOrderTable = $installer->getTable('sales_order');
        if (!$installer->getConnection()->tableColumnExists($saleOrderTable, 'used_point')) {
            $installer->getConnection()->addColumn(
                $saleOrderTable,
                'used_point',
                [
                    'type' => Table::TYPE_INTEGER, 'unsigned' => true,
                    'nullable' => false, 'default' => 0, 'comment' => 'Used point'
                ]
            );
        }
        if (!$installer->getConnection()->tableColumnExists($saleOrderTable, 'bonus_point_amount')) {
            $installer->getConnection()->addColumn(
                $saleOrderTable,
                'bonus_point_amount',
                [
                    'type' => Table::TYPE_INTEGER, 'unsigned' => true,
                    'nullable' => false, 'default' => 0, 'comment' => 'Bonus point'
                ]
            );
        }
        /**
         * create riki_reward_point table
         */
        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('riki_reward_point'))
            ->addColumn(
                'reward_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Reward Id'
            )->addColumn(
                'website_id',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true],
                'Website Id'
            )->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Magento Customer ID'
            )->addColumn(
                'customer_code',
                Table::TYPE_TEXT,
                32,
                ['unsigned' => true, 'nullable' => false],
                'ConsumerDb ID'
            )
            ->addColumn(
                'action_date',
                Table::TYPE_DATE,
                null,
                [],
                'Date'
            )->addColumn(
                'expiry_period',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 730],
                'Expiry period (in days)'
            )->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0],
                'Point status'
            )
            ->addColumn(
                'point',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Point amount'
            )->addColumn(
                'point_type',
                Table::TYPE_SMALLINT,
                4,
                ['nullable' => false],
                'Point type'
            )->addColumn(
                'order_no',
                Table::TYPE_TEXT,
                32,
                ['nullable' => true, 'default' => NULL],
                'Order number (increment_id)'
            )->addColumn(
                'order_item_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true, 'default' => NULL],
                'Order item ID'
            )->addColumn(
                'serial_code',
                Table::TYPE_TEXT,
                32,
                ['nullable' => true, 'default' => NULL],
                'Serial code'
            )->addColumn(
                'wbs_code',
                Table::TYPE_TEXT,
                32,
                ['nullable' => true, 'default' => NULL],
                'WBS Code'
            )->addColumn(
                'account_code',
                Table::TYPE_TEXT,
                32,
                ['nullable' => true, 'default' => NULL],
                'Account number'
            )
            ->addColumn(
                'description',
                Table::TYPE_TEXT,
                '64k',
                ['nullable' => true, 'default' => NULL],
                'Comment'
            )->addIndex(
                $installer->getIdxName('riki_reward_point', ['reward_id']),
                ['reward_id']
            )->addIndex(
                $installer->getIdxName('riki_reward_point', ['customer_id']),
                ['customer_id']
            )->addIndex(
                $installer->getIdxName('riki_reward_point', ['website_id']),
                ['website_id']
            )->addForeignKey(
                $installer->getFkName('riki_reward_point', 'website_id', 'store_website', 'website_id'),
                'website_id',
                $installer->getTable('store_website'),
                'website_id',
                Table::ACTION_CASCADE
            );
        $installer->getConnection()->createTable($table);

        /**
         * create riki_reward_quote table
         */
        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('riki_reward_quote'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Primary key'
            )
            ->addColumn(
                'quote_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Quote ID'
            )
            ->addColumn(
                'points_delta',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Point amount'
            )->addColumn(
                'points_refund',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Point refund'
            )->addIndex($installer->getIdxName('riki_reward_quote', ['quote_id']), 'quote_id');
        $installer->getConnection()->createTable($table);
        
        $installer->endSetup();
    }
}