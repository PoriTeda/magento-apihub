<?php
// @codingStandardsIgnoreFile
namespace Riki\DeliveryType\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    const NORMAR = 'normal';
    const COOL = 'cool';
    const DM = 'direct_mail';
    const COLD = 'cold';
    const CHILLED = 'chilled';
    const COSMETIC = 'cosmetic';

    protected $_deliFactory;
    public function __construct(
        \Riki\DeliveryType\Model\DelitypeFactory $deliFactory
    )
    {
        $this->_deliFactory = $deliFactory;
    }
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '2.0.3') < 0)
        {
            if ($setup->getConnection()->isTableExists('riki_delivery_type') == true) {
                $installer->getConnection()->dropTable($installer->getTable('riki_delivery_type'));

                $table = $installer->getConnection()->newTable($installer->getTable('riki_delivery_type'))
                    ->addColumn(
                        'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 10,
                        [
                            'identity' => true,
                            'unsigned' => true,
                            'nullable' => false,
                            'primary' => true,
                            'auto_increment' => true
                        ],
                        'ID'
                    )->addColumn(
                        'code',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
                        ['nullable' => false],
                        'Code'
                    )->addColumn(
                        'name',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
                        ['nullable' => false],
                        'Name'
                    )->addColumn(
                        'shipping_fee',
                        \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT, 9,
                        ['nullable' => false, 'default' => 0],
                        'Shipping Fee per Delivery Type'
                    )->addColumn(
                        'sync_code',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
                        ['nullable' => false],
                        '3PL Sync Code'
                    )->addColumn(
                        'description',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
                        ['nullable' => true],
                        'Description'
                    );
                $installer->getConnection()->createTable($table);
            }
                $deli = $this->_deliFactory->create();
                $deli->setCode(self::COOL);
                $deli->setName('Cool');
                $deli->setShippingFee(650);
                $deli->setSyncCode(100);
                $deli->setDescription('Description');
                $deli->save();

                $deli = $this->_deliFactory->create();
                $deli->setCode(self::NORMAR);
                $deli->setName('Normal');
                $deli->setShippingFee(450);
                $deli->setSyncCode('000');
                $deli->setDescription('Description Normal');
                $deli->save();

                $deli = $this->_deliFactory->create();
                $deli->setCode(self::DM);
                $deli->setName('DM');
                $deli->setShippingFee(120);
                $deli->setSyncCode(9999);
                $deli->setDescription('Description DM');
                $deli->save();

                $deli = $this->_deliFactory->create();
                $deli->setCode(self::COLD);
                $deli->setName('Cold');
                $deli->setShippingFee(650);
                $deli->setSyncCode(4001);
                $deli->setDescription('Description Cold');
                $deli->save();

                $deli = $this->_deliFactory->create();
                $deli->setCode(self::CHILLED);
                $deli->setName('Chilled');
                $deli->setShippingFee(650);
                $deli->setSyncCode(100);
                $deli->setDescription('Description Chilled');
                $deli->save();

                $deli = $this->_deliFactory->create();
                $deli->setCode(self::COSMETIC);
                $deli->setName('Cosmetic');
                $deli->setShippingFee(450);
                $deli->setSyncCode(100);
                $deli->setDescription('Description Cosmetic');
                $deli->save();

        }

        if (version_compare($context->getVersion(), '2.0.4') < 0) {
            $tableName = $installer->getTable('quote_item');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $installer->getConnection();
                $connection->addColumn(
                    $tableName,
                    'delivery_type',
                    ['type' => Table::TYPE_TEXT,'nullable' => true, 'default' => '','comment' => 'Delivery Type']
                );
            }

            $tableName = $installer->getTable('sales_order_item');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $installer->getConnection();
                $connection->addColumn(
                    $tableName,
                    'delivery_type',
                    ['type' => Table::TYPE_TEXT,'nullable' => true, 'default' => '','comment' => 'Delivery Type']
                );
            }
        }

        if (version_compare($context->getVersion(), '2.0.5') < 0) {

            $tableName = $installer->getTable('sales_order_item');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $installer->getConnection();
                $connection->addColumn(
                    $tableName,
                    'delivery_date',
                    ['type' => Table::TYPE_TEXT,'nullable' => true, 'default' => '','comment' => 'Delivery Date']
                );
                $connection->addColumn(
                    $tableName,
                    'delivery_time',
                    ['type' => Table::TYPE_TEXT,'nullable' => true, 'default' => '','comment' => 'Delivery Time']
                );
            }
        }
        if (version_compare($context->getVersion(), '2.0.6') < 0) {

            $tableName = $installer->getTable('sales_shipment');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $installer->getConnection();
                $connection->addColumn(
                    $tableName,
                    'delivery_date',
                    ['type' => Table::TYPE_TEXT,'nullable' => true, 'default' => '','comment' => 'Delivery Date']
                );
                $connection->addColumn(
                    $tableName,
                    'delivery_time',
                    ['type' => Table::TYPE_TEXT,'nullable' => true, 'default' => '','comment' => 'Delivery Time']
                );
            }
        }
        if (version_compare($context->getVersion(), '2.0.7') < 0) {

            $tableName = $installer->getTable('quote_item');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $installer->getConnection();
//                $connection->addColumn(
//                    $tableName,
//                    'is_free_shipping',
//                    ['type' => Table::TYPE_INTEGER,'nullable' => true, 'default' => '0','comment' => 'Is free shipping']
//                );
            }
        }
        if (version_compare($context->getVersion(), '2.0.8') < 0) {

            $tableName = $installer->getTable('quote_item');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $installer->getConnection();
//                $connection->modifyColumn(
//                    $tableName,
//                    'is_free_shipping',
//                    ['type' => Table::TYPE_TEXT,'length' => 1, 'nullable' => true, 'default' => '0','comment' => 'Is free shipping']
//                );
            }
        }

        if (version_compare($context->getVersion(), '2.0.9') < 0) {

            $tableName = $installer->getTable('quote_item');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $installer->getConnection();
                $connection->addColumn(
                    $tableName,
                    'delivery_date',
                    ['type' => Table::TYPE_TEXT,'nullable' => true, 'default' => '','comment' => 'Delivery Date']
                );
                $connection->addColumn(
                    $tableName,
                    'delivery_time',
                    ['type' => Table::TYPE_TEXT,'nullable' => true, 'default' => '','comment' => 'Delivery Time']
                );
            }
        }
        if (version_compare($context->getVersion(), '2.1.0') < 0) {

            if ($setup->getConnection()->isTableExists('riki_delivery_date') == true) {
                $installer->getConnection()->dropTable($installer->getTable('riki_delivery_date'));
            }
        }


        $installer->endSetup();
    }
}