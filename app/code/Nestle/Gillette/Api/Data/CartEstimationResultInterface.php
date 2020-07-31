<?php

namespace Nestle\Gillette\Api\Data;

use Magento\Customer\Api\Data\AddressInterface;
use phpDocumentor\Reflection\Types\This;

/**
 * Interface CartEstimationResultInterface
 * @package Nestle\Gillette\Api\Data
 */
interface CartEstimationResultInterface
{
    const PAYMENT_METHOD_AVAILABLE = 'payment_method_available';
    const DATE_RANGE = 'date_range';
    const TIME_SLOT = 'time_slot';
    const CART_INFORMATION = 'cart_information';
    const CART_ITEMS = 'cart_items';
    const CUSTOMER_ADDRESSES = 'customer_addresses';

    /**
     * @param \Magento\Quote\Api\Data\TotalsInterface[] $cartInformation
     * @return $this
     */
    public function setCartInformation($cartInformation);

    /**
     * @return \Magento\Quote\Api\Data\TotalsInterface[]
     */
    public function getCartInformation();

    /**
     * @param \Magento\Customer\Api\Data\AddressInterface[] $customerAddresses
     * @return $this
     */
    public function setCustomerAddresses($customerAddresses);

    /**
     * @return \Magento\Customer\Api\Data\AddressInterface[]
     */
    public function getCustomerAddresses();

    /**
     * @param []|\Magento\Quote\Api\PaymentMethodManagementInterface[] $paymentMethodAvailable
     * @return $this
     */
    public function setPaymentMethodAvailable($paymentMethodAvailable);

    /**
     * @return \Magento\Quote\Api\PaymentMethodManagementInterface[]|[]
     */
    public function getPaymentMethodAvailable();

    /**
     * @param array|mixed|\Nestle\Purina\Api\Data\DeliverytimeDataInterface|
     * \Nestle\Purina\Api\Data\DeliverytimeDataInterface[] $dateRange
     * @return $this
     */
    public function setDateRange($dateRange);

    /**
     * @return \Nestle\Purina\Api\Data\DeliverytimeDataInterface[]
     */
    public function getDateRange();

    /**
     * @param \Nestle\Purina\Api\Data\TimeslotDataInterface[]|[] $timeSlot
     * @return $this
     */
    public function setTimeSlot($timeSlot);

    /**
     * @return \Nestle\Purina\Api\Data\TimeslotDataInterface[]|[]
     */
    public function getTimeSlot();

    /**
     * @param \Magento\Quote\Api\Data\CartItemInterface[] $cartItems
     * @return $this
     */
    public function setCartItems($cartItems);

    /**
     * @return \Magento\Quote\Api\Data\CartItemInterface[]
     */
    public function getCartItems();

}
