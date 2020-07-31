<?php

namespace Riki\Loyalty\Model\ResourceModel\RewardQuote;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';
    
    /**
     * Initialize resource model for collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Riki\Loyalty\Model\RewardQuote', 'Riki\Loyalty\Model\ResourceModel\RewardQuote');
    }
}