<?php

namespace Riki\Subscription\Model\Data\Profile;

class Product extends \Magento\Framework\Model\AbstractExtensibleModel implements \Riki\Subscription\Api\Data\Profile\ProductInterface
{
    /**
     * Returns the product ID.
     *
     * @return int|null Product ID. Otherwise, null.
     */
    public function getProductId()
    {
        return $this->getData(self::KEY_PRODUCT_ID);
    }

    /**
     * Sets the product ID.
     *
     * @param int $productId
     *
     * @return $this
     */
    public function setProductId($productId)
    {
        return $this->setData(self::KEY_PRODUCT_ID, $productId);
    }

    /**
     * Returns the product type
     *
     * @return string
     */
    public function getProductType()
    {
        return $this->getData(self::KEY_PRODUCT_TYPE);
    }

    /**
     * Sets the product type
     *
     * @param string $productType
     *
     * @return $this
     */
    public function setProductType($productType)
    {
        return $this->setData(self::KEY_PRODUCT_TYPE, $productType);
    }

    /**
     * Returns the Parent Item ID.
     *
     * @return int|null
     */
    public function getParentItemId()
    {
        return $this->getData(self::KEY_PARENT_ITEM_ID);
    }

    /**
     * Sets the Parent Item ID.
     *
     * @param int $parentItemId
     *
     * @return $this
     */
    public function setParentItemId($parentItemId)
    {
        return $this->setData(self::KEY_PARENT_ITEM_ID, $parentItemId);
    }

    /**
     * Returns the product qty
     *
     * @return int
     */
    public function getQty()
    {
        return $this->getData(self::KEY_QTY);
    }

    /**
     * Sets the product qty
     *
     * @param int $qty
     *
     * @return $this
     */
    public function setQty($qty)
    {
        return $this->setData(self::KEY_QTY, $qty);
    }

    /**
     * Returns the unit case
     *
     * @return string|null
     */
    public function getUnitCase()
    {
        return $this->getData(self::KEY_UNIT_CASE);
    }

    /**
     * Sets the unit case
     *
     * @param string $unitCase
     *
     * @return $this|null
     */
    public function setUnitCase($unitCase)
    {
        return $this->setData(self::KEY_UNIT_CASE, $unitCase);
    }

    /**
     * Returns the unit qty
     *
     * @return int
     */
    public function getUnitQty()
    {
        return $this->getData(self::KEY_UNIT_QTY);
    }

    /**
     * Sets the unit qty
     *
     * @param int $unitQty
     *
     * @return $this
     */
    public function setUnitQty($unitQty)
    {
        return $this->setData(self::KEY_UNIT_QTY, $unitQty);
    }

    /**
     * Returns the product price
     *
     * @return int
     */
    public function getPrice()
    {
        return $this->getData(self::KEY_PRICE);
    }

    /**
     * Sets the product price
     *
     * @param int $price
     *
     * @return $this
     */
    public function setPrice($price)
    {
        return $this->setData(self::KEY_PRICE, $price);
    }

    /**
     * Returns the Gift Wrapping Id
     *
     * @return int
     */
    public function getGwId()
    {
        return $this->getData(self::KEY_GW_ID);
    }

    /**
     * Sets the Gift Wrapping Id
     *
     * @param int $giftWrappingId
     *
     * @return $this
     */
    public function setGwId($giftWrappingId)
    {
        return $this->setData(self::KEY_GW_ID, $giftWrappingId);
    }

    /**
     * Returns the Gift Message Id
     *
     * @return int
     */
    public function getGiftMessageId()
    {
        return $this->getData(self::KEY_GIFT_MESSAGE_ID);
    }

    /**
     * Sets the Gift Message Id
     *
     * @param int $giftMessageId
     *
     * @return $this
     */
    public function setGiftMessageId($giftMessageId)
    {
        return $this->setData(self::KEY_GIFT_MESSAGE_ID, $giftMessageId);
    }

    /**
     * Returns the billing address id
     *
     * @return int|null
     */
    public function getBillingAddressId()
    {
        return $this->getData(self::KEY_BILLING_ADDRESS_ID);
    }

    /**
     * Sets the billing address id
     *
     * @param int $billingAddressId
     *
     * @return $this|null
     */
    public function setBillingAddressId($billingAddressId)
    {
        return $this->setData(self::KEY_BILLING_ADDRESS_ID, $billingAddressId);
    }

    /**
     * Returns the shipping address id
     *
     * @return int|null
     */
    public function getShippingAddressId()
    {
        return $this->getData(self::KEY_SHIPPING_ADDRESS_ID);
    }

    /**
     * Sets the shipping address id
     *
     * @param int $shippingAddressId
     *
     * @return $this|null
     */
    public function setShippingAddressId($shippingAddressId)
    {
        return $this->setData(self::KEY_SHIPPING_ADDRESS_ID, $shippingAddressId);
    }

    /**
     * Returns the unit case
     *
     * @return int|null
     */
    public function getProductAddress()
    {
        return $this->getData(self::KEY_PRODUCT_ADDRESS);
    }

    /**
     * Sets the product main qty case
     *
     * @param int $productAddress
     *
     * @return $this|null
     */
    public function setProductAddress($productAddress)
    {
        return $this->setData(self::KEY_PRODUCT_ADDRESS, $productAddress);
    }

    /**
     * Returns the Delivery Date
     *
     * @return string
     */
    public function getDeliveryDate()
    {
        return $this->getData(self::KEY_DELIVERY_DATE);
    }

    /**
     * Sets the Delivery Date
     *
     * @param string $deliveryDate
     *
     * @return $this
     */
    public function setDeliveryDate($deliveryDate)
    {
        return $this->setData(self::KEY_DELIVERY_DATE, $deliveryDate);
    }

    /**
     * Returns is skip seasonal
     *
     * @return int|null
     */
    public function getIsSkipSeasonal()
    {
        return $this->getData(self::KEY_IS_SKIP_SEASONAL);
    }

    /**
     * Sets is skip seasonal
     *
     * @param int $isSkipSeasonal
     *
     * @return $this|null
     */
    public function setIsSkipSeasonal($isSkipSeasonal)
    {
        return $this->setData(self::KEY_IS_SKIP_SEASONAL, $isSkipSeasonal);
    }

    /**
     * Returns skip from
     *
     * @return string
     */
    public function getSkipFrom()
    {
        return $this->getData(self::KEY_SKIP_FROM);
    }

    /**
     * Sets skip from
     *
     * @param string $skipFrom
     *
     * @return $this
     */
    public function setSkipFrom($skipFrom)
    {
        return $this->setData(self::KEY_SKIP_FROM, $skipFrom);
    }

    /**
     * Returns skip to
     *
     * @return string
     */
    public function getSkipTo()
    {
        return $this->getData(self::KEY_SKIP_TO);
    }

    /**
     * Sets skip to
     *
     * @param string $skipTo
     *
     * @return $this
     */
    public function setSkipTo($skipTo)
    {
        return $this->setData(self::KEY_SKIP_TO, $skipTo);
    }

    /**
     * Returns is spot
     *
     * @return int|null
     */
    public function getIsSpot()
    {
        return $this->getData(self::KEY_IS_SPOT);
    }

    /**
     * Sets is spot
     *
     * @param int $isSpot
     *
     * @return $this|null
     */
    public function setIsSpot($isSpot)
    {
        return $this->setData(self::KEY_IS_SPOT, $isSpot);
    }

    /**
     * Returns is addition
     *
     * @return int|null
     */
    public function getIsAddition()
    {
        return $this->getData(self::KEY_IS_ADDITION);
    }

    /**
     * Sets is addition
     *
     * @param int $isAddition
     *
     * @return $this|null
     */
    public function setIsAddition($isAddition)
    {
        return $this->setData(self::KEY_IS_ADDITION, $isAddition);
    }

    /**
     * Returns stock point discount rate
     *
     * @return int|null
     */
    public function getStockPointDiscountRate()
    {
        return $this->getData(self::KEY_STOCK_POINT_DISCOUNT_RATE);
    }

    /**
     * Sets stock point discount rate
     *
     * @param int $stockPointDiscountRate
     *
     * @return $this|null
     */
    public function setStockPointDiscountRate($stockPointDiscountRate)
    {
        return $this->setData(self::KEY_STOCK_POINT_DISCOUNT_RATE, $stockPointDiscountRate);
    }

    /**
     * Returns the product cart id
     *
     * @return int
     */
    public function getCartId()
    {
        return $this->getData(self::KEY_CART_ID);
    }

    /**
     * Sets the product cart id
     *
     * @param int $productCartId
     *
     * @return $this
     */
    public function setCartId($productCartId)
    {
        return $this->setData(self::KEY_CART_ID, $productCartId);
    }

    /**
     * Returns the course id
     *
     * @return int
     */
    public function getCourseId()
    {
        return $this->getData(self::KEY_COURSE_ID);
    }

    /**
     * Sets the course id
     *
     * @param int $courseId
     *
     * @return $this
     */
    public function setCourseId($courseId)
    {
        return $this->setData(self::KEY_COURSE_ID, $courseId);
    }

    /**
     * Returns the frequency interval
     *
     * @return int
     */
    public function getFrequencyInterval()
    {
        return $this->getData(self::KEY_FREQUENCY_INTERVAL);
    }

    /**
     * Sets the frequency interval
     *
     * @param int $frequencyInterval
     *
     * @return $this
     */
    public function setFrequencyInterval($frequencyInterval)
    {
        return $this->setData(self::KEY_FREQUENCY_INTERVAL, $frequencyInterval);
    }

    /**
     * Returns the frequency unit
     *
     * @return string
     */
    public function getFrequencyUnit()
    {
        return $this->getData(self::KEY_FREQUENCY_UNIT);
    }

    /**
     * Sets the frequency unit
     *
     * @param string $frequencyUnit
     *
     * @return $this
     */
    public function setFrequencyUnit($frequencyUnit)
    {
        return $this->setData(self::KEY_FREQUENCY_UNIT, $frequencyUnit);
    }
}
