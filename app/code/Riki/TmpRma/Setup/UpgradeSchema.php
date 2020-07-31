<?php
// @codingStandardsIgnoreFile
namespace Riki\TmpRma\Setup;

use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema extends \Riki\Framework\Setup\Version\Schema implements \Magento\Framework\Setup\UpgradeSchemaInterface
{
    /**
     * @var false|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_rmaConnection;

    /**
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Riki\TmpRma\Model\ResourceModel\Rma $rmaResource
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Riki\TmpRma\Model\ResourceModel\Rma $rmaResource
    ){

        $this->_rmaConnection = $rmaResource->getConnection();

      parent::__construct(
          $functionCache,
          $logger,
          $resourceConnection,
          $deploymentConfig
      );
    }

    public function version110()
    {
        $this->addColumn('riki_tmprma', 'status', [
            'type' => Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'comment' => 'Status (1:Requested, 2:Rejected, 4:Approved, 8:Closed)'
        ]);
        $this->addColumn('riki_tmprma', 'comment', [
            'type' => Table::TYPE_TEXT,
            'comment' => 'Give order id here if Call Center found exist order'
        ]);
    }

    public function version111()
    {
        $this->addColumn('riki_tmprma', 'returned_warehouse', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
            'comment' => 'Returned date'
        ]);
        $this->addForeignKey('riki_tmprma', 'returned_warehouse', 'pointofsale', 'place_id', Table::ACTION_RESTRICT);
    }

    public function version112()
    {
        $this->dropTable('riki_tmprma_comment');
        $def = [
            [
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'nullable' => false,
                    'unsigned' => true,
                    'primary' => true
                ],
                'Entity Id'
            ],
            [
                'parent_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true,],
                'Parent Id'
            ],
            [
                'is_customer_notified',
                Table::TYPE_INTEGER,
                null,
                [],
                'Is Customer Notified'
            ],
            [
                'is_visible_on_front',
                Table::TYPE_INTEGER,
                5,
                ['unsigned' => true, 'default' => 0],
                'Is Visible On Front'
            ],
            [
                'comment',
                Table::TYPE_TEXT,
                null,
                [],
                'Comment'
            ],
            [
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['default' => Table::TIMESTAMP_INIT],
                'Create At'
            ],
        ];
        $this->createTable('riki_tmprma_comment', $def);
        $this->addIndex('riki_tmprma_comment', ['created_at']);
        $this->addForeignKey('riki_tmprma_comment', 'parent_id', 'riki_tmprma', 'id');
        $this->dropColumn('riki_tmprma', 'comment');
        $this->modifyColumn('riki_tmprma_item', 'qty', [
            'type' => Table::TYPE_INTEGER,
            'unsigned' => true,
        ]);
    }

    public function version113()
    {
        $table = $this->getTable('riki_tmprma');

        $this->modifyColumn($table,
            'returned_warehouse',
            [
                'type' => Table::TYPE_INTEGER,
                'unsigned' => true,
                'comment' => 'Returned Warehouse'
            ]
        );

        $this->addColumn($table, 'returned_date', [
            'type' => Table::TYPE_TIMESTAMP,
            'default' => Table::TIMESTAMP_INIT,
            'comment' => 'Returned Date'
        ]);
    }
}
