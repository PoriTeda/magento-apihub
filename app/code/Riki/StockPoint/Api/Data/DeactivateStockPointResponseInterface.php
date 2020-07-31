<?php

namespace Riki\StockPoint\Api\Data;

interface DeactivateStockPointResponseInterface
{
    /**
     * @return string
     */
    public function getResult();

    /**
     * @param string $result
     * @return $this
     */
    public function setResult($result);
}
