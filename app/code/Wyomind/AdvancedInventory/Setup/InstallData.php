<?php

namespace Wyomind\AdvancedInventory\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{

    protected $_coreHelper = null;

    public function __construct(
        \Wyomind\Core\Helper\Data $coreHelper
    ) {
        $this->_coreHelper = $coreHelper;
    }

    /**
     * @version 6.0.0
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {

        unset($context);

        $installer = $setup;
        $installer->startSetup();
        $installer->endSetup();
    }
}
