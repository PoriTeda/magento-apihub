<?php
// @codingStandardsIgnoreFile
namespace Riki\CsvOrderMultiple\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
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
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $salesConnection = $this->_setupHelper->getSalesConnection();
        $checkoutConnection = $this->_setupHelper->getCheckoutConnection();

        if (version_compare($context->getVersion(), '1.0.0') < 0) {
            if (!$salesConnection->tableColumnExists('sales_order', 'original_unique_id')) {
                $salesConnection->addColumn(
                    'sales_order',
                    'original_unique_id',
                    [
                        'type' => Table::TYPE_TEXT,
                        'default' => null,
                        'length'  =>  50,
                        'comment' => 'Map to original_unique_id field from CSV',
                    ]
                );
            }

            if (!$salesConnection->tableColumnExists('sales_order_grid', 'original_unique_id')) {
                $salesConnection->addColumn(
                    'sales_order_grid',
                    'original_unique_id',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length'=>50,
                        'default' => null,
                        'comment' => 'Map to original_unique_id field from CSV',
                    ]
                );
            }

            if (!$checkoutConnection->tableColumnExists('quote', 'original_unique_id')) {
                $checkoutConnection->addColumn(
                    'quote',
                    'original_unique_id',
                    [
                        'type' => Table::TYPE_TEXT,
                        'default' => null,
                        'length'  =>  50,
                        'comment' => 'Map to original_unique_id field from CSV',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $salesConnection->addIndex(
                $installer->getTable('riki_csv_order_import_history'),
                $installer->getIdxName($installer->getTable('riki_csv_order_import_history'), ['status']),
                ['status']
            );
        }

        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            $this->createHistoryDownloadTable();
        }

        $installer->endSetup();
    }

    /**
     * Create HistoryDownload table
     * @throws \Zend_Db_Exception
     */
    private function createHistoryDownloadTable()
    {
        $salesConnection = $this->_setupHelper->getSalesConnection();
        $table = $salesConnection->newTable('riki_csv_order_import_history_download')
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
                'upload_datetime',
                Table::TYPE_TIMESTAMP,
                null,
                ['default' => Table::TIMESTAMP_INIT],
                'Upload Datetime'
            )->addColumn(
                'upload_by',
                Table::TYPE_TEXT,
                100,
                [],
                'Upload User'
            )->addColumn(
                'file_name',
                Table::TYPE_TEXT,
                null,
                [],
                'Uploaded file name'
            );

        $salesConnection->createTable($table);
    }
}
