<?php
namespace Riki\Rma\Model\ResourceModel\ReviewCc;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'Riki\Rma\Model\ReviewCc',
            'Riki\Rma\Model\ResourceModel\ReviewCc'
        );
    }
}
