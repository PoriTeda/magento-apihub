<?php

namespace Riki\AdvancedInventory\Api;

/**
 * @api
 */
interface PosManagementInterface
{
    public function getPlaceIdsByStoreId($storeId);
}
