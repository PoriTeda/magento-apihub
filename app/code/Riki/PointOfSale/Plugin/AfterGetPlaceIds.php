<?php

namespace Riki\PointOfSale\Plugin;

class AfterGetPlaceIds
{
    /**
     * @var \Riki\AdvancedInventory\Helper\Assignation
     */
    protected $assignationHelper;

    /**
     * CheckAvailableBundleProduct constructor.
     *
     * @param \Riki\AdvancedInventory\Helper\Assignation $assignationHelper
     */
    public function __construct(
        \Riki\AdvancedInventory\Helper\Assignation $assignationHelper
    ) {
        $this->assignationHelper = $assignationHelper;
    }

    /**
     * after get place ids
     *      business logic: for FO, list of place id will be got from config (exclude TOYO).
     *
     * @return array|null
     */
    public function afterGetPlaceIds(
        \Riki\PointOfSale\Model\PointOfSaleManagement $subject,
        $result
    ) {
        $defaultFoPos = $this->assignationHelper->getDefaultPosForFo();

        if (!empty($defaultFoPos)) {
            if (empty($result)) {
                return $defaultFoPos;
            }

            $placeIds = [];
            foreach ($defaultFoPos as $placeId) {
                if (in_array($placeId, $result)) {
                    array_push($placeIds, $placeId);
                }
            }

            return $placeIds;
        }

        return $result;
    }
}
