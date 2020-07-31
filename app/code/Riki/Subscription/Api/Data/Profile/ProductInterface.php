<?php

namespace Riki\Subscription\Api\Data\Profile;

interface ProductInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**#@+
     * Constants defined for keys of array, makes typos less likely
     */
    const KEY_PRODUCT_ID                = 'product_id';
    const KEY_PRODUCT_TYPE              = 'product_type';
    const KEY_PARENT_ITEM_ID            = 'parent_item_id';
    const KEY_QTY                       = 'qty';
    const KEY_UNIT_CASE                 = 'unit_case';
    const KEY_UNIT_QTY                  = 'unit_qty';
    const KEY_PRICE                     = 'price';
    const KEY_GW_ID                     = 'gw_id';
    const KEY_GIFT_MESSAGE_ID           = 'gift_message_id';
    const KEY_BILLING_ADDRESS_ID        = 'billing_address_id';
    const KEY_SHIPPING_ADDRESS_ID       = 'shipping_address_id';
    const KEY_PRODUCT_ADDRESS           = 'product_address';
    const KEY_DELIVERY_DATE             = 'delivery_date';
    const KEY_IS_SKIP_SEASONAL          = 'is_skip_seasonal';
    const KEY_SKIP_FROM                 = 'skip_from';
    const KEY_SKIP_TO                   = 'skip_to';
    const KEY_IS_SPOT                   = 'is_spot';
    const KEY_IS_ADDITION               = 'is_addition';
    const KEY_STOCK_POINT_DISCOUNT_RATE = 'stock_point_discount_rate';
    const KEY_CART_ID                   = 'cart_id';
    const KEY_COURSE_ID                 = 'course_id';
    const KEY_FREQUENCY_INTERVAL        = 'frequency_interval';
    const KEY_FREQUENCY_UNIT            = 'frequency_unit';

    /**#@-*/

    /**
     * Returns the product ID
     *
     * @return int|null Product ID. Otherwise, null.
     */
    public function getProductId();

    /**
     * Sets the product ID
     *
     * @param int $productId
     *
     * @return $this
     */
    public function setProductId($productId);

    /**
     * Returns the product type
     *
     * @return string
     */
    public function getProductType();

    /**
     * Sets the product type
     *
     * @param string $productType
     *
     * @return $this
     */
    public function setProductType($productType);

    /**
     * Returns the Parent Item ID
     *
     * @return int|null
     */
    public function getParentItemId();

    /**
     * Sets the Parent Item ID
     *
     * @param int $parentItemId
     *
     * @return $this
     */
    public function setParentItemId($parentItemId);

    /**
     * Returns the product qty
     *
     * @return int
     */
    public function getQty();

    /**
     * Sets the product qty
     *
     * @param int $qty
     *
     * @return $this
     */
    public function setQty($qty);

    /**
     * Returns the unit case
     *
     * @return string|null
     */
    public function getUnitCase();

    /**
     * Sets the unit case
     *
     * @param string $unitCase
     *
     * @return $this|null
     */
    public function setUnitCase($unitCase);

    /**
     * Returns the unit qty
     *
     * @return int
     */
    public function getUnitQty();

    /**
     * Sets the unit qty
     *
     * @param int $unitQty
     *
     * @return $this
     */
    public function setUnitQty($unitQty);

    /**
     * Returns the product price
     *
     * @return int
     */
    public function getPrice();

    /**
     * Sets the product price
     *
     * @param int $price
     *
     * @return $this
     */
    public function setPrice($price);

    /**
     * Returns the Gift Wrapping Id
     *
     * @return int
     */
    public function getGwId();

    /**
     * Sets the Gift Wrapping Id
     *
     * @param int $giftWrappingId
     *
     * @return $this
     */
    public function setGwId($giftWrappingId);

    /**
     * Returns the Gift Message Id
     *
     * @return int
     */
    public function getGiftMessageId();

    /**
     * Sets the Gift Message Id
     *
     * @param int $giftMessageId
     *
     * @return $this
     */
    public function setGiftMessageId($giftMessageId);

    /**
     * Returns the billing address id
     *
     * @return int|null
     */
    public function getBillingAddressId();

    /**
     * Sets the billing address id
     *
     * @param int $billingAddressId
     *
     * @return $this|null
     */
    public function setBillingAddressId($billingAddressId);

    /**
     * Returns the shipping address id
     *
     * @return int|null
     */
    public function getShippingAddressId();

    /**
     * Sets the shipping address id
     *
     * @param int $shippingAddressId
     *
     * @return $this|null
     */
    public function setShippingAddressId($shippingAddressId);

    /**
     * Returns the unit case
     *
     * @return int|null
     */
    public function getProductAddress();

    /**
     * Sets the product main qty case
     *
     * @param int $productAddress
     *
     * @return $this|null
     */
    public function setProductAddress($productAddress);

    /**
     * Returns the Delivery Date
     *
     * @return string
     */
    public function getDeliveryDate();

    /**
     * Sets the Delivery Date
     *
     * @param string $deliveryDate
     *
     * @return $this
     */
    public function setDeliveryDate($deliveryDate);

    /**
     * Returns is skip seasonal
     *
     * @return int|null
     */
    public function getIsSkipSeasonal();

    /**
     * Sets is skip seasonal
     *
     * @param int $isSkipSeasonal
     *
     * @return $this|null
     */
    public function setIsSkipSeasonal($isSkipSeasonal);

    /**
     * Returns skip from
     *
     * @return string
     */
    public function getSkipFrom();

    /**
     * Sets skip from
     *
     * @param string $skipFrom
     *
     * @return $this
     */
    public function setSkipFrom($skipFrom);

    /**
     * Returns skip to
     *
     * @return string
     */
    public function getSkipTo();

    /**
     * Sets skip to
     *
     * @param string $skipTo
     *
     * @return $this
     */
    public function setSkipTo($skipTo);

    /**
     * Returns is spot
     *
     * @return int|null
     */
    public function getIsSpot();

    /**
     * Sets is spot
     *
     * @param int $isSpot
     *
     * @return $this|null
     */
    public function setIsSpot($isSpot);

    /**
     * Returns is addition
     *
     * @return int|null
     */
    public function getIsAddition();

    /**
     * Sets is addition
     *
     * @param int $isAddition
     *
     * @return $this|null
     */
    public function setIsAddition($isAddition);

    /**
     * Returns stock point discount rate
     *
     * @return int|null
     */
    public function getStockPointDiscountRate();

    /**
     * Sets stock point discount rate
     *
     * @param int $stockPointDiscountRate
     *
     * @return $this|null
     */
    public function setStockPointDiscountRate($stockPointDiscountRate);

    /**
     * Returns the product cart id
     *
     * @return int
     */
    public function getCartId();

    /**
     * Sets the product cart id
     *
     * @param int $productCartId
     *
     * @return $this
     */
    public function setCartId($productCartId);

    /**
     * Returns the course id
     *
     * @return int
     */
    public function getCourseId();

    /**
     * Sets the course id
     *
     * @param int $courseId
     *
     * @return $this
     */
    public function setCourseId($courseId);

    /**
     * Returns the frequency interval
     *
     * @return int
     */
    public function getFrequencyInterval();

    /**
     * Sets the frequency interval
     *
     * @param int $frequencyInterval
     *
     * @return $this
     */
    public function setFrequencyInterval($frequencyInterval);

    /**
     * Returns the frequency unit
     *
     * @return string
     */
    public function getFrequencyUnit();

    /**
     * Sets the frequency unit
     *
     * @param string $frequencyUnit
     *
     * @return $this
     */
    public function setFrequencyUnit($frequencyUnit);
}
