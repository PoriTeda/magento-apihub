<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Request\SilentOrder;

use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;

/**
 * Class ShippingAddressDataBuilder
 */
class ShippingAddressDataBuilder extends AbstractAddressDataBuilder
{
    const FIELD_SUFFIX = 'ship_';

    /**
     * Returns address object from order
     *
     * @param OrderAdapterInterface $order
     * @return AddressAdapterInterface|null
     */
    protected function getAddress(OrderAdapterInterface $order)
    {
        return $order->getShippingAddress();
    }

    /**
     * Returns fields suffix
     *
     * @return string
     */
    protected function getFieldSuffix()
    {
        return self::FIELD_SUFFIX;
    }
}
