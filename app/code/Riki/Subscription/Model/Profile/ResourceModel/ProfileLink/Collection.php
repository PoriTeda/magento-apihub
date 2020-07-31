<?php

namespace Riki\Subscription\Model\Profile\ResourceModel\ProfileLink;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'link_id';

    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Profile\ProfileLink',
            'Riki\Subscription\Model\Profile\ResourceModel\ProfileLink');
    }
}