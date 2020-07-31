<?php

namespace Riki\Checkout\Api;

interface ManageCartInterface
{

    /**
     * group item
     *
     * @param string $cartId
     * @return string[]
     */
    public function groupItemByAddress($cartId);

}
