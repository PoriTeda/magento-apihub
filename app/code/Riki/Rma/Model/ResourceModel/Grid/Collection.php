<?php
namespace Riki\Rma\Model\ResourceModel\Grid;

class Collection extends \Magento\Rma\Model\ResourceModel\Grid\Collection
{
    protected function _construct()
    {
        $this->_init('Riki\Rma\Model\Grid', 'Magento\Rma\Model\ResourceModel\Grid');
    }
}