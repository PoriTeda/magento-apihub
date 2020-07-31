<?php
// @codingStandardsIgnoreFile
namespace Riki\ArReconciliation\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var \Riki\ArReconciliation\Setup\SetupHelper
     */
    protected $_setupHelper;

    /**
     * InstallSchema constructor.
     * @param SetupHelper $setupHelper
     */
    public function __construct(
        \Riki\ArReconciliation\Setup\SetupHelper $setupHelper
    ){
        $this->_setupHelper = $setupHelper;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $salesConnection = $this->_setupHelper->getSalesConnection();

        $table = $salesConnection->newTable('riki_payment_ar_list')
            ->addColumn(
                'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,10,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true,'auto_increment' => true],
                'ID'
            )->addColumn(
                'transaction_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,null,
                [ 'nullable' => false],
                'Transaction ID'
            )->addColumn(
                'amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,null,
                ['nullable' => false],
                'Amount'
            )->addColumn(
                'payment_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,null,
                ['nullable' => false ],
                'TYPE_DATETIME'
            )->addColumn(
                'status_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,10,
                ['nullable' => TRUE],
                'If Status Code = 60 Amount would be negative and this data should be matched against order type return.'
            )->addColumn(
                'payment_from',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,null,
                ['nullable' => true],
                'Mark which payment get from'
            );

        $salesConnection->createTable($table) ;

        $setup->endSetup();

    }
}
