<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Model\ResourceModel\Sales\Grid;

/**
 *
 */
class AssignationArray implements \Magento\Framework\Option\ArrayInterface
{

    protected $_helperPermissions = null;
    protected $_posModel = null;

    public function __construct(
        \Wyomind\AdvancedInventory\Helper\Permissions $helperPermissions,
        \Wyomind\PointOfSale\Model\PointOfSale $posModel
    ) {
        $this->_helperPermissions = $helperPermissions;
        $this->_posModel = $posModel;
    }

    public function toOptionArray()
    {
        $data = [];

        if ($this->_helperPermissions->isAllowed(0)) {
            $data[] = ["label" => __('Unassigned'), "value" => "0"];
        }

        foreach ($this->_posModel->getPlaces() as $p) {
            if ($this->_helperPermissions->isAllowed($p->getPlaceId())) {
                $data[] = ["label" => $p->getName(), "value" => $p->getPlaceId()];
            }
        }
        return $data;
    }
}
