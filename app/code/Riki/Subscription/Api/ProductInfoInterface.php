<?php

namespace Riki\Subscription\Api;


use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Zend_Validate_Exception;

/**
 * Interface ProductInfoInterface
 * @package Riki\Subscription\Api
 */
interface ProductInfoInterface
{
    /**
     * @param int $id
     * @return \Riki\Subscription\Api\Data\ProductInfoResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Zend_Validate_Exception
     */
    public function getProducts(int $id);
}
