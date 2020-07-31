<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Model\ResourceModel\Order;

class Collection extends \Magento\Sales\Model\ResourceModel\Order\Collection
{

    protected $_assignation = null;
    protected $_orderItemCollection = null;

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Magento\Framework\DB\Helper $coreResourceHelper,
        \Wyomind\AdvancedInventory\Model\Assignation $assignation,
        \Wyomind\AdvancedInventory\Model\ResourceModel\Order\Item\Collection $orderItemCollection,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
    
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $entitySnapshot, $coreResourceHelper, $connection, $resource);
        $this->_assignation = $assignation;
        $this->_orderItemCollection = $orderItemCollection;
    }

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
                $items = $this->_assignation->getAssignationByOrderId($item->getEntityId())->getData();

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

    public function getCountNotAssigned(
        $dateConfig,
        $statuses
    ) {
    

        $where = "";
        foreach ($statuses as $status) {
            $where .= "AND main_table.status<>'$status'";
        }
        
        $this->getSelect()
                ->where("FIND_IN_SET(0,main_table.assigned_to) AND main_table.created_at >='" . $dateConfig . " 00:00:00'  " . $where);

        $count = 0;
        $orders = [];

        foreach ($this->getItems() as $order) {
            $requiresAssignation = $this->requiresAssignation($order);
            if ($requiresAssignation) {
                $count++;
                $orders[] = $order->getEntityId();
            }
        }

        return ["count" => $count, "ids" => implode(",", $orders)];
    }
}
