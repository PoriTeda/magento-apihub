<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Block\Adminhtml\Assignation;

/**
 * Report block
 */
class Column extends \Magento\Backend\Block\Template
{

    protected $_modelOrder;
    protected $_posModel;
    protected $_helperAssignation;
    protected $_assignation;
    protected $_helperCore;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Sales\Model\Order $modelOrder,
        \Wyomind\PointOfSale\Model\PointOfSale $posModel,
        \Wyomind\AdvancedInventory\Helper\Assignation $helperAssignation,
        \Wyomind\AdvancedInventory\Model\Assignation $assignation,
        \Wyomind\Core\Helper\Data $helperCore,
        array $data = []
    ) {

        $this->_modelOrder = $modelOrder;
        $this->_posModel = $posModel;
        $this->_helperAssignation = $helperAssignation;
        $this->_assignation = $assignation;
        $this->_helperCore = $helperCore;


        parent::__construct($context, $data);
    }

    public function _toHtml()
    {
        $orderId = $this->getRequest()->getParam("entity_id");
        $item = (Array) $this->_modelOrder->load($orderId)->getData();

        return $this->getAssignation($item);
    }

    public function getAssignation($item)
    {
        $unassigned = 0;
        $partialyAssigned = 0;
        $assigned = 0;

        $onclick = "InventoryManager.viewAssignation(this,\"" . $this->_urlBuilder->getUrl('advancedinventory/assignation/view', ["order_id" => $item["entity_id"]]) . "\")";

        $assignedTo = explode(",", $item["assigned_to"]);
        $value = "<div id='assignation_column_" . $item["entity_id"] . "'>";
        // order too old
        if (in_array(-1, $assignedTo)) {
            $value .= "<div style='color:grey;'>" . __("Order placed before multistock initialization") . "</div>";
        } else {
            if (in_array(0, $assignedTo)) {
                $items = $this->_assignation->getAssignationByOrderId($item["entity_id"])->getData();

                foreach ($items as $i) {
                    if ($i['multistock_enabled']) {
                        if ($i['qty_assigned'] == 0 && $i["qty_unassigned"] > 0) {
                            $unassigned++;
                        } elseif (($i['qty_unassigned']) > 0) {
                            $partialyAssigned++;
                        }
                    }
                }
                
                if ($unassigned > 0) {
                    $color = ($this->_helperAssignation->isUpdatable($item["status"])) ? "red" : "grey";
                    $value .= "<a style='color:$color;' href='javascript:void(0)' onclick='" . $onclick . "'>" . $unassigned . " item" . (($unassigned < 2) ? null : "s") . (($unassigned < 2) ? " is " : " are ") . __("unassigned") . "</a><br/>";
                }
                if ($partialyAssigned > 0) {
                    $color = ($this->_helperAssignation->isUpdatable($item["status"])) ? "orange" : "grey";
                    $value .= "<a style='color:$color;'  href='javascript:void(0)' onclick='" . $onclick . "'>" . $partialyAssigned . " item" . (($partialyAssigned < 2) ? null : "s") . (($partialyAssigned < 2) ? " is " : " are ") . __("partialy unassigned") . "</a><br/>";
                }
            }
            // assigned to
            $color = ($this->_helperAssignation->isUpdatable($item["status"])) ? "green" : "grey";
            foreach ($assignedTo as $id) {
                if ($id > 0) {
                    $assigned++;
                    $value .= "<a style='color:$color;'  href='javascript:void(0)' onclick='" . $onclick . "'>" . $this->_posModel->load($id)->getName() . "</a><br/>";
                }
            }

            if ($unassigned + $partialyAssigned + $assigned == 0) {
                $value .= "<div style='color:black;'>" . __("No assignation required") . "</div>";
            }
        }
        return $value . "</div>";
    }
}
