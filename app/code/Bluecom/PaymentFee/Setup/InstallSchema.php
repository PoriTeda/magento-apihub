<?php
// @codingStandardsIgnoreFile
namespace Bluecom\PaymentFee\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var \Riki\ArReconciliation\Setup\SetupHelper
     */
    protected $_setupHelper;

    /**
     * InstallSchema constructor.
     * @param \Riki\ArReconciliation\Setup\SetupHelper $setupHelper
     */
    public function __construct(
        \Riki\ArReconciliation\Setup\SetupHelper $setupHelper
    ){
        $this->_setupHelper = $setupHelper;
    }
    /**
     * Install
     *
     * @param SchemaSetupInterface   $setup   setup
     * @param ModuleContextInterface $context context
     *
     * @throws \Zend_Db_Exception
     *
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $newTable = 'payment_fee';

        $connection = $this->_setupHelper->getSalesConnection();

        $table = $connection->newTable($newTable)
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity ID'
            )
            ->addColumn(
                'payment_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                250,
                ['nullable' => false],
                'Payment code'
            )
            ->addColumn(
                'payment_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                250,
                ['nullable' => false],
                'Payment Name'
            )
            ->addColumn(
                'fixed_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12, 2],
                ['nullable' => true, 'default' => '0.00'],
                'Fixed Amount'
            )
            ->addColumn(
                'active',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'Active'
            )
            ->setComment('Payment Fee Table.');

        $connection->createTable($table);

        $setup->endSetup();
    }
}