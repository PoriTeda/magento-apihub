<?php
// @codingStandardsIgnoreFile
namespace Riki\Rma\Setup;

use Magento\Framework\DB\Ddl\Table;

class InstallSchema extends \Riki\Framework\Setup\Version\Schema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    public function version100()
    {
        $def = [
            [
                'reasoncode_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'primary' => true,
                    'nullable' => false,
                    'unsigned' => true
                ],
                'Reason Code'
            ],
            [
                'description',
                Table::TYPE_TEXT,
                256,
                [
                    'nullable' => true
                ],
                'Description'
            ],
            [
                'due_to',
                Table::TYPE_TEXT,
                32,
                [
                    'nullable' => true
                ],
                'Due to'
            ]
        ];
        $this->createTable('riki_rma_reasoncode', $def);

    }
}
