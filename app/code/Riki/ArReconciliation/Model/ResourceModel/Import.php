<?php
namespace Riki\ArReconciliation\Model\ResourceModel;

class Import extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_init('riki_payment_ar_list', 'id');
    }
}