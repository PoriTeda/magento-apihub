<?php

namespace Nestle\Gillette\Api;


interface ProductInfoInterface
{
    /**
     * Retrieve product information base on the customer (if logged in) and selected frequency
     * @param string $couseCode
     * @param string $consumerDbId
     * @param string $selectedFrequency
     * @return mixed
     */
    public function getProducts(
        $courseCode,
        $consumerDbId,
        $selectedFrequency
    );
}
