<?php

namespace Riki\AdvancedInventory\Model\ResourceModel\Order;

class Collection extends \Wyomind\AdvancedInventory\Model\ResourceModel\Order\Collection
{

    public function requiresAssignation($item)
    {
        $unassigned = 0;
        $partialyAssigned = 0;
        $assigned = 0;

        $assignedTo = explode(",", $item->getAssignedTo());
        // order too old
        if (in_array(-1, $assignedTo)) {
            return false;
        } else {
            if (in_array(0, $assignedTo)) {
                $items = $this->_assignation->getAssignationByOrderId($item->getEntityId())->toArray();

                foreach ($items['items'] as $i) {
                    if ($i['multistock_enabled']) {
                        if ($i['qty_assigned'] == 0 && $i["qty_unassigned"] > 0) {
                            $unassigned++;
                        } elseif (($i['qty_unassigned']) > 0) {
                            $partialyAssigned++;
                        }
                    }
                }

                if ($unassigned > 0) {
                    return true;
                }
                if ($partialyAssigned > 0) {
                    return true;
                }
            }

            if ($unassigned + $partialyAssigned + $assigned == 0) {
                return false;
            }
        }
        return false;
    }
}
