<?php
namespace Riki\PointOfSale\Model;

class PointOfSaleManagement
{
    /**
     * @var \Riki\PointOfSale\Helper\Data
     */
    protected $pointOfSaleHelper;

    /**
     * PointOfSaleManagement constructor.
     *
     * @param \Riki\PointOfSale\Helper\Data $pointOfSaleHelper
     */
    public function __construct(
        \Riki\PointOfSale\Helper\Data $pointOfSaleHelper
    ) {
        $this->pointOfSaleHelper = $pointOfSaleHelper;
    }

    /**
     * get list of place id
     *
     * @return array
     */
    public function getPlaceIds()
    {
        return $this->pointOfSaleHelper->getPlaceIds();
    }
}