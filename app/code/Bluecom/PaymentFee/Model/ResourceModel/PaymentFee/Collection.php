<?php

namespace Bluecom\PaymentFee\Model\ResourceModel\PaymentFee;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Entity
     *
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Initialization here
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Bluecom\PaymentFee\Model\PaymentFee',
            'Bluecom\PaymentFee\Model\ResourceModel\PaymentFee'
        );
    }
}