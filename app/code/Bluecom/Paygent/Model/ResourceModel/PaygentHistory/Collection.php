<?php

namespace Bluecom\Paygent\Model\ResourceModel\PaygentHistory;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init('Bluecom\Paygent\Model\PaygentHistory', 'Bluecom\Paygent\Model\ResourceModel\PaygentHistory');
    }
}