<?php
// @codingStandardsIgnoreFile
namespace Riki\Quote\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Customer\Model\Customer;
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
            $table = $setup->getTable('sales_order');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'mm_order_id',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'data webapi payment-information']
                );
                $setup->getConnection()->addColumn(
                    $table, 'substitution',
                    ['type' => Table::TYPE_BOOLEAN, 'comment' => 'data webapi payment-information']
                );

                $setup->getConnection()->addColumn(
                    $table, 'mm_packing',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'data webapi payment-information']
                );
                $setup->getConnection()->addColumn(
                    $table, 'mm_cushioning',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'data webapi payment-information']
                );

                $setup->getConnection()->addColumn(
                    $table, 'mm_broken_sku',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'data webapi payment-information']
                );
                $setup->getConnection()->addColumn(
                    $table, 'mm_broken_reason_code',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'data webapi payment-information']
                );

                $setup->getConnection()->addColumn(
                    $table, 'mm_repair_company_name',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'data webapi payment-information']
                );
                $setup->getConnection()->addColumn(
                    $table, 'mm_repair_company_postal_code',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'data webapi payment-information']
                );

                $setup->getConnection()->addColumn(
                    $table, 'mm_repair_company_prefecture',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'data webapi payment-information']
                );
                $setup->getConnection()->addColumn(
                    $table, 'mm_repair_company_address',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'data webapi payment-information']
                );

                $setup->getConnection()->addColumn(
                    $table, 'mm_repair_phone_number',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'data webapi payment-information']
                );
                $setup->getConnection()->addColumn(
                    $table, 'delivery_date_period',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'data webapi payment-information']
                );

                $setup->getConnection()->addColumn(
                    $table, 'delivery_time',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'data webapi payment-information']
                );

                 $setup->getConnection()->addColumn(
                     $table, 'order_date',
                     ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'data webapi payment-information']
                 );
                $setup->getConnection()->addColumn(
                    $table, 'prior_phone_call_flg',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'data webapi payment-information']
                );

                $setup->getConnection()->addColumn(
                    $table, 'caution_for_couterior',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'data webapi payment-information']
                );

            }
        }
        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $checkoutConnection = $this->_resourceConnection->getConnection('checkout');
            if(!$checkoutConnection->tableColumnExists('quote','point_for_trial'))
            {
                $checkoutConnection->addColumn($setup->getTable('quote'),
                    'point_for_trial',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        'length' => 1,
                        'nullable' => true,
                        'comment' => 'Shopping point for trial'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            $checkoutConnection = $this->_resourceConnection->getConnection('checkout');
            if ($checkoutConnection->tableColumnExists('quote', 'point_for_trial')) {
                $checkoutConnection->modifyColumn(
                    $setup->getTable('quote'),
                    'point_for_trial',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'length' => 11,
                        'comment' => 'Shopping point for trial',
                        'default' => null
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.1.0') < 0) {
            $checkoutConnection = $this->_resourceConnection->getConnection('checkout');

            $checkoutConnection->addColumn(
                $checkoutConnection->getTableName('quote'),
                'allow_choose_delivery_date',
                [
                    'type' => Table::TYPE_BOOLEAN,
                    'nullable' => false,
                    'comment' => 'Allow choose delivery date on checkout',
                    'default'  => 1
                ]
            );
        }

        $setup->endSetup();
    }
}