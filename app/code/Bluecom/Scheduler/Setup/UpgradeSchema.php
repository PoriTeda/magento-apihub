<?php
// @codingStandardsIgnoreFile
/**
 * Upgrade Schema
 *
 * PHP version 7
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Bluecom\Scheduler\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Bluecom\Scheduler\Model\Sync;
/**
 * Class UpgradeSchema
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var Sync
     */
    protected $syscData;

    /**
     * UpgradeSchema constructor.
     * @param Sync $sync
     */
    public function __construct(
       Sync $sync
    )
    {
        $this->syscData = $sync;
    }
    /**
     * Upgrading process
     *
     * @param SchemaSetupInterface   $setup   Setup Object
     * @param ModuleContextInterface $context Context Object
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            //create  new table if exist
            $tableName = 'mast_scheduler_jobs';
            if(!$setup->getConnection()->isTableExists($tableName)) {
                $table = $installer->getConnection()
                    ->newTable($installer->getTable($tableName))
                    ->addColumn(
                        'job_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                        null,
                        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                        'Job Id'
                    )->addColumn(
                        'job_code',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        250,
                        ['nullable' => true],
                        'Job code'
                    )->addColumn(
                        'expression',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        200,
                        ['nullable' => true],
                        'Cron expression'
                    )->addColumn(
                        'default_expression',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        200,
                        ['nullable' => true],
                        'Default expression'
                    )->addColumn(
                        'last_expression',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        200,
                        ['nullable' => true],
                        'Last Expression'
                    )->addColumn(
                        'model_path',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        250,
                        ['nullable' => true],
                        'Model Path'
                    )->addColumn(
                        'method_execute',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        250,
                        ['nullable' => true],
                        'Method Execute'
                    )->addColumn(
                        'group_name',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        250,
                        ['nullable' => true],
                        'Group Name'
                    );
                $installer->getConnection()->createTable($table);
            }
        }
        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $tableName = 'cron_schedule';
            $fieldName = 'pid';
            if(!$setup->getConnection()->tableColumnExists($tableName,$fieldName))
            {
                $setup->getConnection()->addColumn(
                    $tableName,
                    $fieldName,
                    [
                        'type' =>\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'unsigned' => true,
                        'nullable' => true,
                        'default' => 0,
                        'comment' => 'Process ID'
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '1.0.4', '<')) {
            $tableName = 'mast_scheduler_jobs';
            $fieldName = 'active';
            if(!$setup->getConnection()->tableColumnExists($tableName,$fieldName))
            {
                $setup->getConnection()->addColumn(
                    $tableName,
                    $fieldName,
                    [
                        'type' =>\Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'unsigned' => true,
                        'nullable' => true,
                        'default' => 0,
                        'comment' => 'Enable or disable cron job'
                    ]
                );
            }
        }
        $this->syscData->SyncAllJobs();
        $installer->endSetup();
    }
}
