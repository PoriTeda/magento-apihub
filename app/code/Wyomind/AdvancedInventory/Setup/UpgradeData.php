<?php

namespace Wyomind\AdvancedInventory\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{

    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {



        if (version_compare($context->getVersion(), '6.0.0') < 0) {
            $installer = $setup;
            $installer->startSetup();


            $installer->endSetup();
        }
    }
}
