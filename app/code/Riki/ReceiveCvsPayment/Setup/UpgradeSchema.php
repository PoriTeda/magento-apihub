<?php
/**
 * Receive CVS Payment
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ReceiveCvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\ReceiveCvsPayment\Setup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class UpgradeSchema
 *
 * @category  RIKI
 * @package   Riki\ReceiveCvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class UpgradeSchema implements UpgradeSchemaInterface {

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private $eavSetupFactory;
    /**
     * @var ModuleDataSetupInterface
     */
    private $eavSetup;

    /**
     * UpgradeSchema constructor.
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $eavSetup
     */
    public function __construct(EavSetupFactory $eavSetupFactory,ModuleDataSetupInterface $eavSetup )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavSetup = $eavSetup;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context )
    {
        $installer = $setup;
        $installer->startSetup();
        if( version_compare($context->getVersion(), '1.0.1') < 0)
        {
            $tableName = 'receive_cvs_payment';
            if(!$setup->tableExists($installer->getTable($tableName)))
            {
                $table = $installer->getConnection()
                    ->newTable($installer->getTable($tableName))
                    ->addColumn(
                        'upload_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                        null,
                        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                        'Index Id'
                    )->addColumn(
                        'csv_file',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        250,
                        ['nullable' => true],
                        'CSV file'
                    )->addColumn(
                        'messages',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        '64k',
                        ['nullable' => true],
                        'import log'
                    )->addColumn(
                        'status',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        ['nullable' => true],
                        'imported status'
                    )->addColumn(
                        'created',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                        'Created At'
                    )->addColumn(
                        'updated',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                        'Updated At'
                    )->addColumn(
                        'imported',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => true,'default'=> null ],
                        'imported At'
                    );
                $installer->getConnection()->createTable($table);
            }

            //table csv_order
            $tableCsvOrderName = 'receive_cvs_order';
            if(!$setup->tableExists($setup->getTable($tableCsvOrderName)))
            {
                $tablecsvOrder = $installer->getConnection()
                    ->newTable($installer->getTable($tableCsvOrderName))
                    ->addColumn(
                        'order_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                        null,
                        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                        'Index Id'
                    )->addColumn(
                        'csv_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                        250,
                        ['nullable' => true],
                        'CSV '
                    )->addColumn(
                        'order_increment',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        '50',
                        ['nullable' => true],
                        'order increment'
                    )->addColumn(
                        'status',
                        \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        null,
                        ['nullable' => true],
                        'status'
                    );
                $installer->getConnection()->createTable($tablecsvOrder);
            }
        }
        if( version_compare($context->getVersion(), '1.0.2') < 0)
        {
            $tableCsvOrderName = 'receive_cvs_order';
            $fieldName = 'payment_date';
            if($setup->tableExists($setup->getTable($tableCsvOrderName)))
            {
                if(!$setup->getConnection()->tableColumnExists($tableCsvOrderName,$fieldName))
                {
                    $setup->getConnection()->addColumn
                    (
                        $tableCsvOrderName,
                        $fieldName,
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                            'length' => 100,
                            'nullable' => true,
                            'comment' => 'Payment Date in CSV'
                        ]
                    );
                }
            }
        }
        $installer->endSetup();

    }
}