<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;
interface ApiProductCartInterface
    extends ExtensibleDataInterface
{
    /**#@+
     * Constants defined for keys of the data array. Identical to the name of the getter in snake case
     */

    const ID = 'cart_id';
    const PROFILE_ID = 'profile_id';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const QTY = 'qty';
    const PRODUCT_TYPE = 'product_type';
    const PRODUCT_ID = 'product_id';
    const PRODUCT_OPTIONS = 'product_options';
    const PARENT_ITEM_ID = 'parent_item_id';
    const BILLING_ADDRESS_ID = 'billing_address_id';
    const SHIPPING_ADDRESS_ID = 'shipping_address_id';
    const GW_USE = 'gw_used';
    const DELIVERY_DATE = 'delivery_date';
    const DELIVERY_TIME_SLOT = 'delivery_time_slot';
    const UNIT_CASE = 'unit_case';
    const UNIT_QTY = 'unit_qty';
    const GW_ID = 'gw_id';
    const GIFT_MESSAGE_ID = 'gift_message_id';
    const ORIGINAL_DELIVERY_DATE = 'original_delivery_date';
    const ORIGINAL_DELIVERY_TIME_SLOT = 'original_delivery_time_slot';

    /* consumer db function */
    /**
     * get Id
     *
     * @return mixed|null
     */
    public function getCartId();

    /**
     * set id
     *
     * @param $cartId
     * @return $this
     */
    public function setCartId($cartId);
    /**
     * get profile id
     *
     * @return string
     */
    public function getProfileId();

    /**
     * set profile id
     *
     * @param $profileId
     * @return $this
     */
    public function setProfileId($profileId);

    /**
     * get Qty
     *
     * @return mixed|null
     */
    public function getQty();

    /**
     * set qty
     *
     * @param $qty
     * @return $this
     */
    public function setQty($qty);

    /**
     * get Product type
     *
     * @return mixed|null
     */
    public function getProductType();

    /**
     * set product type
     *
     * @param $productType
     * @return $this
     */
    public function setProductType($productType);

    /**
     * get product id
     *
     * @return string
     */
    public function getProductId();

    /**
     * set product id
     *
     * @param $productId
     * @return $this
     */
    public function setProductId($productId);

    /**
     * get product options
     *
     * @return mixed|null
     */
    public function getProductOptions();

    /**
     * set product options
     *
     * @param $productOptions
     * @return $this
     */
    public function setProductOptions($productOptions);

    /**
     * get parent item id
     *
     * @return mixed|null
     */
    public function getParentItemId();

    /**
     * set parent item id
     *
     * @param $parentItemId
     * @return $this
     */
    public function setParentItemId($parentItemId);

    /**
     * get billing address id
     *
     * @return mixed|null
     */
    public function getBillingAddressId();

    /**
     * set billing address id
     *
     * @param $billingAddressId
     * @return $this
     */
    public function setBillingAddressId($billingAddressId);

    /**
     * get shipping address id
     *
     * @return mixed|null
     */
    public function getShippingAddressId();

    /**
     * set shipping address id
     *
     * @param $shippingAddressId
     * @return $this
     */
    public function setShippingAddressId($shippingAddressId);

    /**
     * get gift wrapping use
     *
     * @return mixed|null
     */
    public function getGwUse();

    /**
     * set gift wrapping use
     *
     * @param $gwUse
     * @return $this
     */
    public function setGwUse($gwUse);

    /**
     * get delivery date
     *
     * @return mixed|null
     */
    public function getDeliveryDate();

    /**
     * set delivery date
     *
     * @param $deliveryDate
     * @return $this
     */
    public function setDeliveryDate($deliveryDate);

    /**
     * get delivery time slot
     *
     * @return mixed|null
     */
    public function getDeliveryTimeSlot();

    /**
     * set delivery time slot
     *
     * @param $deliveryTimeSlot
     * @return $this
     */
    public function setDeliveryTimeSlot($deliveryTimeSlot);

    /**
     * get unit case
     *
     * @return mixed|null
     */
    public function getUnitCase();

    /**
     * set unit case
     *
     * @param $unitCase
     * @return $this
     */
    public function setUnitCase($unitCase);

    /**
     * get unit qty
     *
     * @return mixed|null
     */
    public function getUnitQty();

    /**
     * set Unit qty
     *
     * @param $unitQty
     * @return $this
     */
    public function setUnitQty($unitQty);

    /**
     * get gw id
     *
     * @return mixed|null
     */
    public function getGwId();

    /**
     * set gw id
     *
     * @param $gwId
     * @return $this
     */
    public function setGwId($gwId);

    /**
     * get gift message id
     *
     * @return mixed|null
     */
    public function getGiftMessageId();

    /**
     * set gift message id
     *
     * @param $giftMessageId
     * @return $this
     */
    public function setGiftMessageId($giftMessageId);

    /**
     * get created date
     *
     * @return mixed|null
     */
    public function getCreatedDate();

    /**
     * set create date
     *
     * @param $createdDate
     * @return $this
     */
    public function setCreatedDate($createdDate);

    /**
     * get updated date
     *
     * @return mixed|null
     */
    public function getUpdatedDate();

    /**
     * set update date
     *
     * @param $updatedDate
     * @return $this
     */
    public function setUpdatedDate($updatedDate);

    /**
     * @return string|null
     */
    public function getOriginalDeliveryDate();

    /**
     * @param string $originalDeliveryDate
     * @return $this
     */
    public function setOriginalDeliveryDate($originalDeliveryDate);

    /**
     * @return int|null
     */
    public function getOriginalDeliveryTimeSlot();

    /**
     * @param int $originalDeliveryTimeSlot
     * @return $this
     */
    public function setOriginalDeliveryTimeSlot($originalDeliveryTimeSlot);
}
