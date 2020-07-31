<?php

namespace Riki\AdvancedInventory\Model;

class PosManagement implements \Riki\AdvancedInventory\Api\PosManagementInterface
{
    /**
     * @var \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected $posFactory;

    /**
     * PosManagement constructor.
     *
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory
     */
    public function __construct(
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory
    ) {
        $this->posFactory = $pointOfSaleFactory;
    }

    /**
     * Get list of place id which is assigned for specific store
     *
     * @param $storeId
     * @return array
     */
    public function getPlaceIdsByStoreId($storeId)
    {
        $rs = [];
        /** @var \Wyomind\PointOfSale\Model\PointOfSale $pointOfSales */
        $pointOfSales = $this->_posFactory->create();

        /*place data which is supported for this store*/
        $places = $pointOfSales->getPlacesByStoreId($storeId);

        foreach ($places as $pos) {
            array_push($rs, $pos->getId());
        }

        return $rs;
    }
}
