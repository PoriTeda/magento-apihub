<?php

namespace Riki\SubscriptionProfileDisengagement\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface {

    protected $_profileResource;

    /**
     * @param \Riki\Subscription\Model\Profile\ResourceModel\Profile $profileResource
     */
    public function __construct(
        \Riki\Subscription\Model\Profile\ResourceModel\Profile $profileResource
    ){
        $this->_profileResource = $profileResource;
    }

    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context )
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $this->_profileResource->getConnection();
        
        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $profileTable = $setup->getTable('subscription_profile');
            $reasonTable = $setup->getTable('subscription_disengagement_reason');
            $connection->dropForeignKey(
                $profileTable,
                $setup->getFkName($profileTable, 'disengagement_reason', $reasonTable, 'id')
            );

            $connection->modifyColumn(
                $profileTable,
                'disengagement_reason',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    'unsigned' => true,
                    'comment' => 'Disengaged reason code'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            {
                $table =$setup->getTable('subscription_disengagement_reason');
                $connection = $setup->getConnection('default');
                if ($connection->isTableExists($table)) {
                    $connection->addColumn(
                        $table,
                        'visibility',
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                            'nullable' => false,
                            'default' => 1,
                            'comment' => 'Visibility'
                        ]
                    );
                }
            }
        }

        $setup->endSetup();
    }
}