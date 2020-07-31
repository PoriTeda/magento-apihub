<?php

namespace Bluecom\Paygent\Model\ResourceModel\Reauthorize;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init('Bluecom\Paygent\Model\Reauthorize', 'Bluecom\Paygent\Model\ResourceModel\Reauthorize');
    }
}