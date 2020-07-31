<?php

namespace Riki\CsvOrderMultiple\Plugin\AdvancedInventory\Helper;

use Riki\CsvOrderMultiple\Cron\ImportOrders;

class Assignation
{
    /**
     * @param \Riki\AdvancedInventory\Helper\Assignation $subject
     * @param \Closure $proceed
     * @param \Magento\Sales\Model\Order $order
     * @return array|mixed
     */
    public function aroundGetAvailablePlacesByOrder(
        \Riki\AdvancedInventory\Helper\Assignation $subject,
        \Closure $proceed,
        \Magento\Sales\Model\Order $order
    ) {
        $places = $proceed($order);
        $isOrderImport = $order->getData(ImportOrders::CSV_ORDER_IMPORT_FLAG);
        $assignedWarehouseId = $order->getData(ImportOrders::IMPORT_ASSIGNED_WAREHOUSE_ID_KEY);
        if ($isOrderImport && $assignedWarehouseId) {
            foreach ($places as $placeId => $place) {
                if ($place->getId() == $assignedWarehouseId) {
                    return [$placeId => $place];
                }
            }
        }

        return $places;
    }
}
