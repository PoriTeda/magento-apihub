<?php

namespace Riki\Sales\Model\ResourceModel\Order;

class OrderAdditionalInformation extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sales_order_additional_information', 'order_id');
    }
}