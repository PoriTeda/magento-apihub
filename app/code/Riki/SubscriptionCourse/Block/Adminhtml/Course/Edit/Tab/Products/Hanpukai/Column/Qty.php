<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Products\Hanpukai\Column;


class Qty extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Number
{
    /**
     * Returns value of the row
     *
     * @param \Magento\Framework\DataObject $row
     * @return mixed|string
     */
    protected function _getValue(\Magento\Framework\DataObject $row)
    {
        $unitQty = (NULL != $row->getUnitQty())?$row->getUnitQty():1;
        if('CS' != $row->getUnitCase()){
            $unitQty = 1;
        }
        $row->setData('qty',$row->getQty()/$unitQty);

        $data = parent::_getValue($row);
        if ($data !== null) {
            $value = $data * 1;
            $sign = (bool)(int)$this->getColumn()->getShowNumberSign() && $value > 0 ? '+' : '';
            if ($sign) {
                $value = $sign . $value;
            }
            // fixed for showing zero in grid
            return $value ? $value : '0';
        }
        return $this->getColumn()->getDefault();
    }
}
