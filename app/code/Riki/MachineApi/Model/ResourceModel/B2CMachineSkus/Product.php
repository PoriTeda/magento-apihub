<?php
namespace Riki\MachineApi\Model\ResourceModel\B2CMachineSkus;

class Product extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    public function _construct()
    {
        $this->_init('subscription_course_machine_type_product', 'type_id, product_id');
    }
}
