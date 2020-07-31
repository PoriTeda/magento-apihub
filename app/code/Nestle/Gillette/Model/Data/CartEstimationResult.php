<?php

namespace Nestle\Gillette\Model\Data;

use Magento\Framework\DataObject;
use Nestle\Gillette\Api\Data\CartEstimationResultInterface;
use phpDocumentor\Reflection\Types\This;

/**
 * Class CartEstimationResult
 * @package Nestle\Gillette\Model\Data
 */
Class CartEstimationResult extends DataObject  implements CartEstimationResultInterface {


    /**
     * {@inheritDoc}
     */
    public function setDateRange($dateRange)
    {
        return $this->setData(self::DATE_RANGE, $dateRange);
    }
    /**
     * {@inheritDoc}
     */
    public function getDateRange()
    {
        return $this->getData(self::DATE_RANGE);
    }

    /**
     * {@inheritDoc}
     */
    public function setCartInformation($cartInformation)
    {
        return $this->setData(self::CART_INFORMATION, $cartInformation);
    }

    /**
     * {@inheritDoc}
     */
    public function getCartInformation()
    {
        return $this->getData(self::CART_INFORMATION);
    }

    /**
     * {@inheritDoc}
     */
    public function setCustomerAddresses($customerAddresses) {
        return $this->setData(self::CUSTOMER_ADDRESSES, $customerAddresses);
    }

    /**
     * {@inheritDoc}
     */
    public function getCustomerAddresses() {
        return $this->getData(self::CUSTOMER_ADDRESSES);
    }

    /**
     * {@inheritDoc}
     */
    public function setPaymentMethodAvailable($paymentMethodAvailable)
    {
        return $this->setData(self::PAYMENT_METHOD_AVAILABLE, $paymentMethodAvailable);
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentMethodAvailable()
    {
        return $this->getData(self::PAYMENT_METHOD_AVAILABLE);
    }

    /**
     * {@inheritDoc}
     */
    public function setTimeSlot($timeSlot)
    {
        return $this->setData(self::TIME_SLOT, $timeSlot);
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeSlot()
    {
        return $this->getData(self::TIME_SLOT);
    }

    /**
     * {@inheritDoc}
     */
    public function setCartItems($cartItems)
    {
        return $this->setData(self::CART_ITEMS, $cartItems);
    }

    /**
     * {@inheritDoc}
     */
    public function getCartItems()
    {
        return $this->getData(self::CART_ITEMS);
    }

}
