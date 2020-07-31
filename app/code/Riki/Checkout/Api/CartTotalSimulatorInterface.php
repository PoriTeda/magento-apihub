<?php

namespace Riki\Checkout\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;

interface CartTotalSimulatorInterface
{
    /**
     * Simulate totals for future orders.
     *
     * @param int $cartId
     *
     * @return \Riki\Checkout\Api\Data\CartSimulationTotalsInterface[]
     * @throws ValidatorException
     * @throws LocalizedException
     */
    public function simulateSubscriptionCart($cartId);
}
