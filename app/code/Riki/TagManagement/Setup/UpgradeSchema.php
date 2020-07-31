<?php
// @codingStandardsIgnoreFile
namespace Riki\TagManagement\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resourceConnection;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->_resourceConnection = $resourceConnection;
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();
        if (version_compare($context->getVersion(), '0.0.2') < 0) {
            // add column to table subscription_profile
            $table = $setup->getTable('core_config_data');
            if ($connection->isTableExists($table)) {
                $connection->delete($table, ['path = ?' => 'setting_tag/group_order_complete/script_manager_microdanaalyzecv']);
                $connection->delete($table, ['path = ?' => 'setting_tag/group_order_complete/gmo_spt']);
                $connection->delete($table, ['path = ?' => 'setting_tag/group_order_complete/gmo_ndg_nba']);
            }
        }
        if (version_compare($context->getVersion(), '0.0.3') < 0) {
            // add column to table subscription_profile
            $table = $setup->getTable('core_config_data');
            if ($connection->isTableExists($table)) {
                $connection->delete($table, ['path = ?' => 'setting_tag/group_order_complete/script_manager_affiliateordercompletebeneposite']);
            }
        }
        if (version_compare($context->getVersion(), '0.0.6') < 0) {
            // add column to table subscription_profile
            $table = $setup->getTable('core_config_data');
            if ($connection->isTableExists($table)) {
                $connection->delete($table, ['path = ?' => 'setting_tag/group_order_complete/line_tag_nba']);
                $connection->delete($table, ['path = ?' => 'setting_tag/group_order_complete/line_tag_ndg']);
                $connection->delete($table, ['path = ?' => 'setting_tag/group_order_complete/line_tag_spt']);            }
        }
        if (version_compare($context->getVersion(), '0.0.7') < 0) {
            // add column to table subscription_profile
            $table = $setup->getTable('core_config_data');
            if ($connection->isTableExists($table)) {
                $connection->delete($table, ['path = ?' => 'setting_tag/group_order_complete/line_tag_nba']);
                $connection->delete($table, ['path = ?' => 'setting_tag/group_order_complete/line_tag_ndg']);
                $connection->delete($table, ['path = ?' => 'setting_tag/group_order_complete/line_tag_spt']);            }
        }
        $setup->endSetup();
    }
}
