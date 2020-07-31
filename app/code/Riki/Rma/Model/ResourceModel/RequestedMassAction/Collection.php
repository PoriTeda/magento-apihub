<?php
namespace Riki\Rma\Model\ResourceModel\RequestedMassAction;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'Riki\Rma\Model\RequestedMassAction',
            'Riki\Rma\Model\ResourceModel\RequestedMassAction'
        );
    }
}
