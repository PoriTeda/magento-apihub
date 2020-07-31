<?php

namespace Riki\Subscription\Api\Data;

use Magento\Customer\Api\Data\AddressInterface;
use phpDocumentor\Reflection\Types\This;

/**
 * Interface ProductInfoResultInterface
 * @package Riki\Subscription\Api\Data
 */
interface ProductInfoResultInterface
{
    const PRODUCT_INFORMATION = 'product_information';

    /**
     * @param $productInformation
     * @return mixed
     */
    public function setProductInformation($productInformation);

    /**
     * @return \Riki\Subscription\Api\Data\ProductInfoResultInterface[]
     */
    public function getProductInformation();

}
