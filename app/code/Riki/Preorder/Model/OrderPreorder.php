<?php

namespace Riki\Preorder\Model;

class OrderPreorder extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Riki\Preorder\Model\ResourceModel\OrderPreorder');

    }
}