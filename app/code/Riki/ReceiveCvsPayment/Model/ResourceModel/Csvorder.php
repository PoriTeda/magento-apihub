<?php
namespace Riki\ReceiveCvsPayment\Model\ResourceModel;

class Csvorder extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_init('receive_cvs_order', 'order_id');
    }
}