<?php
namespace Riki\Rma\Model\ResourceModel\Rma;

class Collection extends \Magento\Rma\Model\ResourceModel\Grid\Collection
{
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Rma\Model\Rma', 'Magento\Rma\Model\ResourceModel\Rma');
    }
}