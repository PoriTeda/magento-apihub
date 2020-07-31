<?php
namespace Riki\MachineApi\Model\ResourceModel\B2CMachineSkus\Product;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init(
            \Riki\MachineApi\Model\B2CMachineSkus\Product::class,
            \Riki\MachineApi\Model\ResourceModel\B2CMachineSkus\Product::class
        );
    }
}
