<?php

namespace Riki\AdvancedInventory\Plugin\AdvancedInventory\Model\PointOfSale;

class RemoveFilterCustomerGroup
{
    /**
     * Remove filter for customer group
     *
     * @param \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection $subject
     * @param $storeId
     * @param $whereGroupId
     * @return array
     */
    public function beforeGetPlacesByStoreId(
        \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection $subject,
        $storeId,
        $whereGroupId
    ) {
        return [$storeId, null];
    }
}
