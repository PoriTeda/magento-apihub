<?php
namespace Riki\ReceiveCvsPayment\Model\ResourceModel;

class Importing extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_init('receive_cvs_payment', 'upload_id');
    }
}