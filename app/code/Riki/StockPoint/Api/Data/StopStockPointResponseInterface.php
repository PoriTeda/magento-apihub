<?php

namespace Riki\StockPoint\Api\Data;

interface StopStockPointResponseInterface
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
