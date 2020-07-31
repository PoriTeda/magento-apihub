<?php

namespace Riki\AdvancedInventory\Model\ResourceModel\Catalog\Product;

class CollectionFactory extends \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
{
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Riki\\AdvancedInventory\\Model\\ResourceModel\\Catalog\\Product\\Collection')
    {
        parent::__construct($objectManager, $instanceName);
    }
}
