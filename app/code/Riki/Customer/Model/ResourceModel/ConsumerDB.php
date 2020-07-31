<?php
namespace Riki\Customer\Model\ResourceModel;

/**
 * Consumer DB mysql resource
 */
class ConsumerDB extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_customer_consumerdb', 'id');
    }


}