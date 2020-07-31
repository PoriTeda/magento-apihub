<?php
// @codingStandardsIgnoreFile
namespace Riki\CsvOrderMultiple\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

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
    )
    {
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

        $table = $salesConnection->newTable('riki_csv_order_import_history')
            ->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                10,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true,
                    'auto_increment' => true
                ],
                'ID'
            )->addColumn(
                'original_unique_id',
                Table::TYPE_TEXT,
                100,
                [],
                'Original Unique ID'
            )->addColumn(
                'uploaded_by',
                Table::TYPE_TEXT,
                100,
                [],
                'Upload User'
            )->addColumn(
                'upload_datetime',
                Table::TYPE_TIMESTAMP,
                null,
                ['default' => Table::TIMESTAMP_INIT],
                'Upload Datetime'
            )->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                1,
                [],
                '1=>Waiting, 2=>Failure, 3=>Success'
            )->addColumn(
                'error_description',
                Table::TYPE_TEXT,
                null,
                [],
                'Error Description'
            )->addColumn(
                'payment_method',
                Table::TYPE_TEXT,
                50,
                [],
                'Payment Method'
            )->addColumn(
                'consumer_name',
                Table::TYPE_TEXT,
                255,
                [],
                'Consumer full name'
            )->addColumn(
                'business_code',
                Table::TYPE_TEXT,
                50,
                [],
                'Business Code'
            )->addColumn(
                'data_json_order',
                Table::TYPE_TEXT,
                null,
                [],
                'Data json for create order'
            );

        $salesConnection->createTable($table);

        $setup->endSetup();

    }
}
