<?php
// @codingStandardsIgnoreFile
namespace Riki\CatalogRule\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    protected $_ruleSetup;

    public function __construct(
        \Riki\Rule\Setup\RuleSetup $ruleSetup
    )
    {
        $this->_ruleSetup = $ruleSetup;
    }

    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $table = $setup->getTable('catalogrule');
        $connection = $setup->getConnection();

        if ($connection->isTableExists($table)) {
            $this->_ruleSetup->addTimeColumns($table);
        }

        $setup->endSetup();
    }
}