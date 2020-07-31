<?php
namespace Riki\SapIntegration\Setup;

use \Magento\Framework\Setup\UpgradeSchemaInterface;
use \Magento\Framework\Db\Ddl\Table;
use \Magento\Framework\Setup\SchemaSetupInterface;
use \Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema extends \Riki\Framework\Setup\Version\Schema implements \Magento\Framework\Setup\UpgradeSchemaInterface
{
	public function version100()
    {
        $this->addColumn('sales_order_item', 'distribution_channel',  [
            'type' => Table::TYPE_TEXT,
            'length' => '10',
            'comment' => 'Use for SAP',
            'nullable' => false,
            'default' => '14',
        ]);
        $this->addColumn('sales_shipment_item', 'distribution_channel',  [
            'type' => Table::TYPE_TEXT,
            'length' => '10',
            'comment' => 'Use for SAP',
            'nullable' => false,
            'default' => '14',
        ]);
    }

	public function version103()
    {
        $this->addColumn('magento_rma_item_entity', 'gps_price_ec', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Gps price ec'
        ]);
        $this->addColumn('magento_rma_item_entity', 'material_type', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Material type'
        ]);
        $this->addColumn('magento_rma_item_entity', 'sales_organization', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Sales organization'
        ]);
        $this->addColumn('magento_rma_item_entity', 'sap_interface_excluded', [
            'type' => Table::TYPE_BOOLEAN,
            'default' => 0,
            'comment' => 'SAP interface excluded flag'
        ]);

        $this->addColumn('sales_shipment_item', 'gps_price_ec', [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Gps price ec'
        ]);
        $this->addColumn('sales_shipment_item', 'material_type', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Material type'
        ]);
        $this->addColumn('sales_shipment_item', 'sales_organization', [
            'type' => Table::TYPE_TEXT,
            'length' => 255,
            'comment' => 'Sales organization'
        ]);
        $this->addColumn('sales_shipment_item', 'sap_interface_excluded', [
            'type' => Table::TYPE_BOOLEAN,
            'default' => 0,
            'comment' => 'SAP interface excluded flag'
        ]);
    }

    public function version105()
    {
        $tableName = 'riki_shipment_sap_exported';
        $def = [
            [
                'shipment_entity_id',
                Table::TYPE_INTEGER,
                10,
                [
                    'primary' => true,
                    'unsigned' => true,
                    'identity' => true,
                    'nullable' => false,
                ],
                'Shipment entity id'
            ],
            [
                'shipment_increment_id',
                Table::TYPE_TEXT,
                50,
                ['nullable' => false],
                'Shipment increment Id'
            ],
            [
                'order_id',
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true,'nullable' => false,],
                'Order entity id'
            ],
            [
                'is_exported_sap',
                Table::TYPE_SMALLINT,
                4,
                ['unsigned' => true, 'default' => 0],
                'Is exported SAP'
            ],
            [
                'export_sap_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null],
                'Export sap date'
            ],
            [
                'backup_file',
                Table::TYPE_TEXT,
                255,
                ['default' => null],
                'Backup file',
            ],
            [
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['default' => Table::TIMESTAMP_INIT],
                'Created at'
            ],
            [
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['default' => Table::TIMESTAMP_INIT_UPDATE],
                'Updated at'
            ],
        ];
        $this->createTable($tableName, $def, 'sales');

        $salesConnection = $this->getConnection($tableName);

        $shipmentTable = $salesConnection->getTableName('sales_shipment');
        $unUsageIndex = $salesConnection->getIndexName($shipmentTable, ['is_exported_sap', 'entity_id']);
        $salesConnection->dropIndex($shipmentTable, $unUsageIndex);

        $sapExportedTable = $salesConnection->getTableName($tableName);

        $salesConnection->addIndex(
            $sapExportedTable,
            $salesConnection->getIndexName($sapExportedTable, ['is_exported_sap']),
            ['is_exported_sap'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
        );
    }
}
