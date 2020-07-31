<?php

namespace Riki\AdvancedInventory\Api;

interface ItemRepositoryInterface
{
    /**
     * @param $productId
     * @return mixed
     */
    public function getByProductId($productId);
}
