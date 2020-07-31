<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Observer\PlaceOrder;

/**
 * Interface \Magento\Reward\Observer\PlaceOrder\RestrictionInterface
 *
 */
interface RestrictionInterface
{
    /**
     * Check if reward points operations is allowed
     *
     * @return bool
     */
    public function isAllowed();
}
