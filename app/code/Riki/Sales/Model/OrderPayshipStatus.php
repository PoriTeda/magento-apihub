<?php
namespace Riki\Sales\Model;

class OrderPayshipStatus extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Sales\Model\ResourceModel\OrderPayshipStatus');
    }
}