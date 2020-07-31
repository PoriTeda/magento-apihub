<?php
// @codingStandardsIgnoreFile
namespace Riki\Questionnaire\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /** @var false|\Magento\Framework\DB\Adapter\AdapterInterface */
    protected $_salesConnection;

    /**
     * @param \Riki\Questionnaire\Model\ResourceModel\Questionnaire $questionnaireResourceModel
     */
    public function __construct(
        \Riki\Questionnaire\Model\ResourceModel\Questionnaire $questionnaireResourceModel
    ) {
        $this->_salesConnection = $questionnaireResourceModel->getConnection();
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $connection = $setup->getConnection();
        $installer->startSetup();

        if (version_compare($context->getVersion(), '0.0.2') < 0) {
            $table = $setup->getTable('riki_enquete_answer_reply');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->modifyColumn(
                    $table,
                    'choice_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'default' => null,
                        'unsigned' => true,
                        'nullable' => true,
                        'comment' => 'Choice ID'
                    ]
                );
            }
        }


        if (version_compare($context->getVersion(), '0.0.3') < 0) {
            $table = $setup->getTable('riki_enquete_answer_reply');
            if ($connection->isTableExists($table) == true) {
                $connection->addColumn(
                    $table,
                    'question_title',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Question Title (from question table)',
                    ]
                );
            }
            $table = $setup->getTable('riki_enquete_answer');
            if ($connection->isTableExists($table) == true) {
                $connection->addColumn(
                    $table,
                    'enquete_name',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Enquete Name (from enquete table)',
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '0.0.4') < 0) {
            $connection = $installer->getConnection();
            $table = $setup->getTable('riki_enquete_question');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $connection->addColumn(
                    $table,
                    'enquete_question_no',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => '255',
                        'default' => null,
                        'unsigned' => true,
                        'nullable' => true,
                        'comment' => 'enquete question no'
                    ]
                );
            }
            $table = $setup->getTable('riki_enquete_question_choice');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $connection->addColumn(
                    $table,
                    'enquete_choices_no',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => '255',
                        'default' => null,
                        'unsigned' => true,
                        'nullable' => true,
                        'comment' => 'enquete choices no'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '0.0.5') < 0) {
            $table = $setup->getTable('riki_enquete_answer');
            if ($connection->isTableExists($table) == true) {
                $connection->changeColumn(
                    $table,
                    'customer_code',
                    'customer_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'nullable' => true,
                        'comment' => 'Customer ID',
                    ]
                );
                $connection->changeColumn(
                    $table,
                    'order_increment_id',
                    'order_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'nullable' => true,
                        'comment' => 'Order ID',
                    ]
                );
                $connection->addIndex(
                    $table,
                    $connection->getIndexName($table, ['customer_id', 'order_id']),
                    ['customer_id', 'order_id']);

            }
        }

        if (version_compare($context->getVersion(), '0.0.6') < 0) {
            $table = $setup->getTable('riki_enquete');
            if ($connection->isTableExists($table) == true) {
                $connection->changeColumn(
                    $table,
                    'visible_on_page',
                    'visible_on_checkout',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'default' => 0,
                        'comment' => 'Visible on checkout',
                    ]
                );

                $connection->addColumn(
                    $table,
                    'visible_on_order_success_page',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'default' => 0,
                        'comment' => 'Visible on order success page'
                    ]
                );

            }
        }

        if (version_compare($context->getVersion(), '0.0.7') < 0) {
            $table = $setup->getTable('riki_enquete_answer_reply');
            if ($connection->isTableExists($table) == true) {
                $connection->dropColumn(
                    $table,
                    'question_title'
                );
            }
            $table = $setup->getTable('riki_enquete_answer');
            if ($connection->isTableExists($table) == true) {
                $connection->dropColumn(
                    $table,
                    'enquete_name'
                );
            }
        }
        if (version_compare($context->getVersion(), '0.0.8') < 0) {
            $table = $setup->getTable('riki_enquete');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->modifyColumn(
                    $table,
                    'priority',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'default' => null,
                        'nullable' => true,
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '0.0.9') < 0) {

            $table = $setup->getTable('riki_enquete_answer_reply');

            $fKeyName = $setup->getFkName('riki_enquete_answer_reply', 'choice_id', 'riki_enquete_question_choice',
                'choice_id');

            $this->_salesConnection->dropForeignKey(
                $table,
                $fKeyName
            );

            $this->_salesConnection->addForeignKey(
                $fKeyName,
                $table,
                'choice_id',
                $setup->getTable('riki_enquete_question_choice'),
                'choice_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            );
        }

        if (version_compare($context->getVersion(), '1.0.0') < 0) {

            $table = $setup->getTable('riki_enquete_answer_reply');

            $this->_salesConnection->modifyColumn(
                $table,
                'question_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'Question ID'
                ]
            );

            $fKeyName = $setup->getFkName('riki_enquete_answer_reply', 'question_id', 'riki_enquete_question',
                'question_id');

            $this->_salesConnection->dropForeignKey(
                $table,
                $fKeyName
            );

            $this->_salesConnection->addForeignKey(
                $fKeyName,
                $table,
                'question_id',
                $setup->getTable('riki_enquete_question'),
                'question_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            );
        }

        if (version_compare($context->getVersion(), '1.0.1') < 0) {

            $tables = [
                $setup->getTable('riki_enquete_question'),
                $setup->getTable('riki_enquete_question_choice')
            ];

            foreach ($tables as $table) {
                if ($this->_salesConnection->isTableExists($table)) {
                    $this->_salesConnection->addColumn(
                        $table,
                        'created_at',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                            'nullable' => false,
                            'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT,
                            'comment' => 'Created date'
                        ]
                    );

                    $this->_salesConnection->addColumn(
                        $table,
                        'updated_at',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                            'nullable' => false,
                            'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                            'comment' => 'Updated date'
                        ]
                    );
                }
            }
        }
        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            {
                $table = $setup->getTable('riki_enquete_question_choice');
                if ($this->_salesConnection->isTableExists($table)) {

                    $this->_salesConnection->addColumn(
                        $table,
                        'hide_delete',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                            'length' => '1',
                            'nullable' => true,
                            'comment' => 'Hide to show on checkout',
                            'default' => '0',
                        ]
                    );
                }
            }
        }

        $this->upgradeSchemaNED2761($setup, $context);

        $setup->endSetup();
    }

    private function upgradeSchemaNED2761(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            {
                $table = $setup->getTable('riki_enquete');
                if ($this->_salesConnection->isTableExists($table)) {
                    $this->_salesConnection->addColumn(
                        $table,
                        'enquete_type',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                            'length' => '1',
                            'nullable' => false,
                            'comment' => 'Enquete Questionnaire Type',
                            'default' => '0',
                            'after' => 'enquete_id'
                        ]
                    );
                }

                $table = $setup->getTable('riki_enquete_question');
                if ($this->_salesConnection->isTableExists($table) &&
                    $this->_salesConnection->tableColumnExists($table, 'enquete_question_no')) {

                    $this->_salesConnection->changeColumn(
                        $table,
                        'enquete_question_no',
                        'legacy_enquete_question_no',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                            'length' => '255',
                            'default' => null,
                            'unsigned' => true,
                            'nullable' => true,
                            'comment' => 'Enquete question number'
                        ]
                    );
                }

                $table = $setup->getTable('riki_enquete_question_choice');
                if ($this->_salesConnection->isTableExists($table) &&
                    $this->_salesConnection->tableColumnExists($table, 'enquete_choices_no')) {

                    $this->_salesConnection->changeColumn(
                        $table,
                        'enquete_choices_no',
                        'legacy_enquete_choices_no',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                            'length' => '255',
                            'default' => null,
                            'unsigned' => true,
                            'nullable' => true,
                            'comment' => 'Choice number'
                        ]
                    );
                }

                $table = $setup->getTable('riki_enquete_answer');
                if ($this->_salesConnection->isTableExists($table) &&
                    $this->_salesConnection->tableColumnExists($table, 'order_id')) {
                    $this->_salesConnection->changeColumn(
                        $table,
                        'order_id',
                        'entity_id',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                            'nullable' => true,
                            'comment' => 'Entity Id',
                        ]
                    );
                }

                $table = $setup->getTable('riki_enquete_answer');
                if ($this->_salesConnection->isTableExists($table)) {

                    $this->_salesConnection->addColumn(
                        $table,
                        'entity_type',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                            'length' => '1',
                            'default' => '0',
                            'comment' => 'Entity type',
                            'after' => 'entity_id'
                        ]
                    );
                }
            }
        }
    }
}