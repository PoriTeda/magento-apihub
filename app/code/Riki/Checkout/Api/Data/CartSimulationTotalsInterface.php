<?php

namespace Riki\Checkout\Api\Data;

interface CartSimulationTotalsInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**#@+
     * Constants defined for keys of array, makes typos less likely
     */
    const ORDER_TIMES = 'order_times';

    const GRAND_TOTAL = 'grand_total';

    /**#@-*/

    /**
     * @param int $orderTimes
     *
     * @return $this
     */
    public function setOrderTimes($orderTimes);

    /**
     * @return int
     */
    public function getOrderTimes();

    /**
     * @param float $grandTotal
     *
     * @return $this
     */
    public function setGrandTotal($grandTotal);

    /**
     * @return float
     */
    public function getGrandTotal();

    /**
     * @return \Riki\Checkout\Api\Data\CartSimulationTotalsExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * @param CartSimulationTotalsExtensionInterface $extensionAttributes
     *
     * @return $this
     */
    public function setExtensionAttributes(
        \Riki\Checkout\Api\Data\CartSimulationTotalsExtensionInterface $extensionAttributes
    );
}
