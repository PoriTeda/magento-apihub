<?php
namespace Riki\MachineApi\Model\B2CMachineSkus;

use Magento\Framework\Model\Context;
use Riki\CatalogRule\Api\ProductRepositoryInterface;

class Product extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Riki\MachineApi\Model\ResourceModel\B2CMachineSkus\Product::class);
    }
}
