<?php
namespace Riki\AdvancedInventory\Model\Catalog;

class Product extends \Magento\Catalog\Model\Product
{
    public function getResourceCollection()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->create('Riki\AdvancedInventory\Model\ResourceModel\Catalog\Product\Collection');
    }
}
