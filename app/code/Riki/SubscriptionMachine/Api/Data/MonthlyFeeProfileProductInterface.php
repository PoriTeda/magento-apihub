<?php

namespace Riki\SubscriptionMachine\Api\Data;

interface MonthlyFeeProfileProductInterface
{
    const SKU = 'sku';
    const QTY = 'qty';

    /**
     * @param string $sku
     * @return $this
     */
    public function setSku($sku);

    /**
     * @return string
     */
    public function getSku();

    /**
     * @param int $qty
     * @return $this
     */
    public function setQty($qty);

    /**
     * @return int
     */
    public function getQty();
}
