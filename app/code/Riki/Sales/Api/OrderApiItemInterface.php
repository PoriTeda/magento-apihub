<?php

namespace Riki\Sales\Api;


interface OrderApiItemInterface
{
    const STATUS = 'status';
    const SHIP_OUT_DATE = 'ship_out_date';
    const DELIVERY_COMPLETION_DATE = 'delivery_completion_date';

    /**
     * @return string
     */
    public function getStatus();

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status);



}