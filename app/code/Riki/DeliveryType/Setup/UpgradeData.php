<?php
// @codingStandardsIgnoreFile
namespace Riki\DeliveryType\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{

    const NORMAR = 'normal';
    const COOL = 'cool';
    const DM = 'direct_mail';
    const COLD = 'cold';
    const CHILLED = 'chilled';
    const COSMETIC = 'cosmetic';

   

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        


        $setup->endSetup();
    }
}
