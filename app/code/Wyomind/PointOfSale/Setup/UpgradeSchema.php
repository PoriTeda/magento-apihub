<?php

namespace Wyomind\PointOfSale\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {

        // $context->getVersion() = version du module actuelle
        // 10.0.0 = version en cours d'installation
        if (version_compare($context->getVersion(), '6.0.0') < 0) { // mise a jour vers 10.0.0
            $installer = $setup;
            $installer->startSetup();
            // do what you have to do

            $installer->endSetup();
        }
    }
}
