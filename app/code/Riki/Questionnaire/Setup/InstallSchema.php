<?php
// @codingStandardsIgnoreFile
namespace Riki\Questionnaire\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class InstallSchema
 * @package Riki\Questionnaire\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Install Data
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $tbl = $installer->getConnection()->newTable($installer->getTable('riki_enquete'))
            ->addColumn(
                'enquete_id',
                Table::TYPE_INTEGER,
                null,
                array('identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true),
                'Enquete Id'
            )->addColumn(
                'code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Enquete Code'
            )->addColumn(
                'name',
                Table::TYPE_TEXT,
                255,
                [],
                'Enquete Name'
            )->addColumn(
                'start_date',
                Table::TYPE_DATE,
                null,
                [],
                'Enquete start date'
            )->addColumn(
                'end_date',
                Table::TYPE_DATE,
                null,
                [],
                'Enquete end date'
            )->addColumn(
                'priority',
                Table::TYPE_INTEGER,
                null,
                ['default' => 0],
                'Priority'
            )->addColumn(
                'is_enabled',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false],
                'Status Enable / Disable'
            )->addColumn(
                'linked_product_sku',
                Table::TYPE_TEXT,
                255,
                [],
                'SKU product code'
            )->addColumn(
                'visible_on_page',
                Table::TYPE_SMALLINT,
                2,
                [],
                'Visible on page'
            )->addColumn(
                'is_available_backend_only',
                Table::TYPE_BOOLEAN,
                null,
                [],
                'Available backend only'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Enquete create datetime'
            )->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Enquete updated datetime'
            )->setComment(
                'Enquete'
            );
        $installer->getConnection()->createTable($tbl);

        $tbl = $installer->getConnection()->newTable($installer->getTable('riki_enquete_question'))
            ->addColumn(
                'question_id',
                Table::TYPE_INTEGER,
                null,
                array('identity' => true, 'nullable' => false, 'unsigned' => true, 'primary' => true),
                'Enquete Question Number'
            )->addColumn(
                'enquete_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Enquete ID'
            )->addColumn(
                'is_required',
                Table::TYPE_BOOLEAN,
                null,
                [],
                'Is required'
            )->addColumn(
                'type',
                Table::TYPE_SMALLINT,
                2,
                ['unsigned' => true],
                'Question type'
            )->addColumn(
                'title',
                Table::TYPE_TEXT,
                null,
                [],
                'Question content'
            )->addColumn(
                'sort_order',
                Table::TYPE_INTEGER,
                null,
                ['default' => 0],
                'Sort order'
            )->addIndex(
                $installer->getIdxName('riki_enquete_question', ['question_id']),
                ['question_id']
            )->addForeignKey(
                $installer->getFkName('riki_enquete_question', 'enquete_id', 'riki_enquete', 'enquete_id'),
                'enquete_id',
                $installer->getTable('riki_enquete'),
                'enquete_id',
                Table::ACTION_CASCADE
            )->setComment(
                'Enquete Question'
            );
        $installer->getConnection()->createTable($tbl);

        $tbl = $installer->getConnection()->newTable($installer->getTable('riki_enquete_question_choice'))
            ->addColumn(
                'choice_id',
                Table::TYPE_INTEGER,
                null,
                array('identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true),
                'Choice Number'
            )->addColumn(
                'question_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Enquete Question Id'
            )->addColumn(
                'label',
                Table::TYPE_TEXT,
                255,
                [],
                'Choices'
            )->addColumn(
                'sort_order',
                Table::TYPE_INTEGER,
                null,
                ['default' => 0],
                'Sort order'
            )->addColumn(
                'parent_choice_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Parent choice id'
            )->addIndex(
                $installer->getIdxName('riki_enquete_question_choice', ['choice_id']),
                ['choice_id']
            )->addForeignKey(
                $installer->getFkName('riki_enquete_question_choice', 'question_id', 'riki_enquete_question', 'question_id'),
                'question_id',
                $installer->getTable('riki_enquete_question'),
                'question_id',
                Table::ACTION_CASCADE
            )->setComment(
                'Enquete Question Choices'
            );
        $installer->getConnection()->createTable($tbl);

        $tbl = $installer->getConnection()->newTable($installer->getTable('riki_enquete_answer'))
            ->addColumn(
                'answer_id',
                Table::TYPE_INTEGER,
                null,
                array('identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true),
                'Id'
            )->addColumn(
                'enquete_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Enquete ID'
            )->addColumn(
                'customer_code',
                Table::TYPE_TEXT,
                255,
                [],
                'Customer ID'
            )->addColumn(
                'order_increment_id',
                Table::TYPE_TEXT,
                255,
                ['unsigned' => true],
                'Order number'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Answer create datetime'
            )->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Answer updated datetime'
            )->addIndex(
                $installer->getIdxName('riki_enquete_answer', ['answer_id']),
                ['answer_id']
            )->addForeignKey(
                $installer->getFkName('riki_enquete_answer', 'enquete_id', 'riki_enquete', 'enquete_id'),
                'enquete_id',
                $installer->getTable('riki_enquete'),
                'enquete_id',
                Table::ACTION_CASCADE
            )->setComment(
                'Enquete answer'
            );
        $installer->getConnection()->createTable($tbl);

        $tbl = $installer->getConnection()->newTable($installer->getTable('riki_enquete_answer_reply'))
            ->addColumn(
                'reply_id',
                Table::TYPE_INTEGER,
                null,
                array('identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true),
                'Id'
            )->addColumn(
                'answer_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Answer ID'
            )->addColumn(
                'question_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Question ID'
            )->addColumn(
                'choice_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Choice id'
            )->addColumn(
                'content',
                Table::TYPE_TEXT,
                null,
                [],
                'Content reply'
            )->addIndex(
                $installer->getIdxName('riki_enquete_answer_reply', ['reply_id']),
                ['reply_id']
            )->addForeignKey(
                $installer->getFkName('riki_enquete_answer_reply', 'answer_id', 'riki_enquete_answer', 'answer_id'),
                'answer_id',
                $installer->getTable('riki_enquete_answer'),
                'answer_id',
                Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName('riki_enquete_answer_reply', 'question_id', 'riki_enquete_question', 'question_id'),
                'question_id',
                $installer->getTable('riki_enquete_question'),
                'question_id',
                Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName('riki_enquete_answer_reply', 'choice_id', 'riki_enquete_question_choice', 'choice_id'),
                'choice_id',
                $installer->getTable('riki_enquete_question_choice'),
                'choice_id',
                Table::ACTION_CASCADE
            )->setComment(
                'Enquete answer reply'
            );
        $installer->getConnection()->createTable($tbl);

        $installer->endSetup();
    }
}