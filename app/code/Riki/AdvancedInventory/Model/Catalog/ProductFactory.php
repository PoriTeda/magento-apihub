<?php

namespace Riki\AdvancedInventory\Model\Catalog;

class ProductFactory extends \Magento\Catalog\Model\ProductFactory
{
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Riki\\AdvancedInventory\\Model\\Catalog\\Product')
    {
        parent::__construct($objectManager, $instanceName);
    }
}
