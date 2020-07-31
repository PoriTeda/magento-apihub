<?php
namespace Riki\Customer\Model;

class ConsumerDB extends \Magento\Framework\Model\AbstractModel
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Customer\Model\ResourceModel\ConsumerDB');
    }
}