<?php

namespace Wyomind\PointOfSale\Model\ResourceModel;

/**
 * Point of sale mysql resource
 */
class PointOfSale extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    protected function _construct()
    {
        $this->_init('pointofsale', 'place_id');
    }
}
