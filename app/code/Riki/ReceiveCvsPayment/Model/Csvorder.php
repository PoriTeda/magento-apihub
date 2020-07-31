<?php
namespace Riki\ReceiveCvsPayment\Model;

class Csvorder extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\ReceiveCvsPayment\Model\ResourceModel\Csvorder');
    }
}