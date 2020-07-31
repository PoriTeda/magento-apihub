<?php
namespace Riki\Prize\Api;

interface PrizeManagementInterface
{
    /**
     * Get applied prize for cart
     *
     * @param $cartId
     *
     * @return \Riki\Prize\Api\Data\PrizeInterface[]
     */
    public function getPrizeForCart($cartId);
}