<?php
namespace Riki\Rma\Model\ResourceModel\Item;

class Collection extends \Magento\Rma\Model\ResourceModel\Item\Collection
{
    protected function _construct()
    {
        $this->_init('Riki\Rma\Model\Item', 'Magento\Rma\Model\ResourceModel\Item');
    }
}