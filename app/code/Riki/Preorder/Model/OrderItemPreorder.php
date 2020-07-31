<?php

namespace Riki\Preorder\Model;

class OrderItemPreorder extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Riki\Preorder\Model\ResourceModel\OrderItemPreorder');

    }
}