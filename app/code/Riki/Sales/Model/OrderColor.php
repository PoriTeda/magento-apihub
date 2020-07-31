<?php

namespace Riki\Sales\Model;

use \Magento\Framework\Model\AbstractModel;

class OrderColor extends AbstractModel
{


    /**
     * Initialize resource model
     * @return void
     */
    public function _construct()
    {
        $this->_init('Riki\Sales\Model\ResourceModel\OrderColor');
    }


}

