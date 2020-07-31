<?php
// @codingStandardsIgnoreFile
namespace Riki\ShoppingPoint\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface {

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private $eavSetupFactory;
    private $eavSetup;

    public function __construct(EavSetupFactory $eavSetupFactory,ModuleDataSetupInterface $eavSetup )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavSetup = $eavSetup;
    }
    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context )
    {
        $installer = $setup;
        $installer->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->eavSetup]);
        $eavSetup->addAttribute(
            'customer',
            'reward_user_setting',
            [
                'type' => 'int',
                'visible' => 0,
                'required' => false,
                'visible_on_front' => 1,
                'is_user_defined' => 0,
                'is_system' => 1,
                'is_hidden' => 1,
                'label' => 'Reward user setting'
            ]
        );
        $eavSetup->addAttribute(
            'customer',
            'reward_user_redeem',
            [
                'type' => 'int',
                'visible' => 0,
                'required' => false,
                'visible_on_front' => 1,
                'is_user_defined' => 0,
                'is_system' => 1,
                'is_hidden' => 1,
                'label' => 'Reward user redeem Number'
            ]
        );
        $installer->endSetup();
    }
}