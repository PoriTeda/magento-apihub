<?php
namespace Riki\Sales\Model\ResourceModel\OrderPayshipStatus;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Riki\Sales\Model\OrderPayshipStatus', 'Riki\Sales\Model\ResourceModel\OrderPayshipStatus');
    }
}
