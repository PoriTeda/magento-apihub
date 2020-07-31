<?php
namespace Riki\ReceiveCvsPayment\Model;

class Importing extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\ReceiveCvsPayment\Model\ResourceModel\Importing');
    }
}