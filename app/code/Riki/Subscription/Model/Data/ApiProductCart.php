<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Subscription\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use \Riki\Subscription\Api\Data\ApiProductCartInterface;
/**
 * Class Customer
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ApiProductCart extends AbstractExtensibleObject implements ApiProductCartInterface
{

    public function __construct
    (
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $attributeValueFactory
    )
    {
        parent::__construct($extensionFactory, $attributeValueFactory);
    }

    /* consumer db function */
    /**
     * get Id
     *
     * @return mixed|null
     */
    public function getCartId()
    {
        return $this->_get(self::ID);
    }

    /**
     * set id
     *
     * @param $cartId
     * @return $this
     */
    public function setCartId($cartId)
    {
        return $this->setData(self::ID,$cartId);
    }
    /**
     * get profile id
     *
     * @return string
     */
    public function getProfileId(){
        return $this->_get(self::PROFILE_ID);
    }

    /**
     * set profile id
     *
     * @param $profileId
     * @return $this
     */
    public function setProfileId($profileId){
        return $this->setData(self::PROFILE_ID,$profileId);
    }

    /**
     * get Qty
     *
     * @return mixed|null
     */
    public function getQty(){
        return $this->_get(self::QTY);
    }

    /**
     * set qty
     *
     * @param $qty
     * @return $this
     */
    public function setQty($qty){
        return $this->setData(self::QTY,$qty);
    }

    /**
     * get Product type
     *
     * @return mixed|null
     */
    public function getProductType()
    {
        return $this->_get(self::PRODUCT_TYPE);
    }

    /**
     * set product type
     *
     * @param $productType
     * @return $this
     */
    public function setProductType($productType)
    {
        return $this->setData(self::PRODUCT_TYPE,$productType);
    }

    /**
     * get product id
     *
     * @return string
     */
    public function getProductId()
    {
        return $this->_get(self::PRODUCT_ID);
    }

    /**
     * set product id
     *
     * @param $productId
     * @return $this
     */
    public function setProductId($productId)
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * get product options
     *
     * @return mixed|null
     */
    public function getProductOptions()
    {
        return $this->_get(self::PRODUCT_OPTIONS);
    }

    /**
     * set product options
     *
     * @param $productOptions
     * @return $this
     */
    public function setProductOptions($productOptions)
    {
        return $this->setData(self::PRODUCT_OPTIONS,$productOptions);
    }

    /**
     * get parent item id
     *
     * @return mixed|null
     */
    public function getParentItemId()
    {
        return $this->_get(self::PARENT_ITEM_ID);
    }

    /**
     * set parent item id
     *
     * @param $parentItemId
     * @return $this
     */
    public function setParentItemId($parentItemId)
    {
        return $this->setData(self::PARENT_ITEM_ID,$parentItemId);
    }

    /**
     * get billing address id
     *
     * @return mixed|null
     */
    public function getBillingAddressId()
    {
        return $this->_get(self::BILLING_ADDRESS_ID);
    }

    /**
     * set billing address id
     *
     * @param $billingAddressId
     * @return $this
     */
    public function setBillingAddressId($billingAddressId)
    {
        return $this->setData(self::BILLING_ADDRESS_ID,$billingAddressId);
    }

    /**
     * get shipping address id
     *
     * @return mixed|null
     */
    public function getShippingAddressId()
    {
        return $this->_get(self::SHIPPING_ADDRESS_ID);
    }

    /**
     * set shipping address id
     *
     * @param $shippingAddressId
     * @return $this
     */
    public function setShippingAddressId($shippingAddressId)
    {
        return $this->setData(self::SHIPPING_ADDRESS_ID,$shippingAddressId);
    }

    /**
     * get gift wrapping use
     *
     * @return mixed|null
     */
    public function getGwUse()
    {
        return $this->_get(self::GW_USE);
    }

    /**
     * set gift wrapping use
     *
     * @param $gwUse
     * @return $this
     */
    public function setGwUse($gwUse)
    {
        return $this->setData(self::GW_USE,$gwUse);
    }

    /**
     * get delivery date
     *
     * @return mixed|null
     */
    public function getDeliveryDate()
    {
        return $this->_get(self::DELIVERY_DATE);
    }

    /**
     * set delivery date
     *
     * @param $deliveryDate
     * @return $this
     */
    public function setDeliveryDate($deliveryDate)
    {
        return $this->setData(self::DELIVERY_DATE,$deliveryDate);
    }

    /**
     * get delivery time slot
     *
     * @return mixed|null
     */
    public function getDeliveryTimeSlot()
    {
        return $this->_get(self::DELIVERY_TIME_SLOT);
    }

    /**
     * set delivery time slot
     *
     * @param $deliveryTimeSlot
     * @return $this
     */
    public function setDeliveryTimeSlot($deliveryTimeSlot)
    {
        return $this->setData(self::DELIVERY_TIME_SLOT,$deliveryTimeSlot);
    }

    /**
     * get unit case
     *
     * @return mixed|null
     */
    public function getUnitCase()
    {
        return $this->_get(self::UNIT_CASE);
    }

    /**
     * set unit case
     *
     * @param $unitCase
     * @return $this
     */
    public function setUnitCase($unitCase)
    {
        return $this->setData(self::UNIT_CASE,$unitCase);
    }

    /**
     * get unit qty
     *
     * @return mixed|null
     */
    public function getUnitQty()
    {
        return $this->_get(self::UNIT_QTY);
    }

    /**
     * set Unit qty
     *
     * @param $unitQty
     * @return $this
     */
    public function setUnitQty($unitQty)
    {
        return $this->setData(self::UNIT_QTY,$unitQty);
    }

    /**
     * get gw id
     *
     * @return mixed|null
     */
    public function getGwId()
    {
        return $this->_get(self::GW_ID);
    }

    /**
     * set gw id
     *
     * @param $gwId
     * @return $this
     */
    public function setGwId($gwId)
    {
        return $this->setData(self::GW_ID,$gwId);
    }

    /**
     * get gift message id
     *
     * @return mixed|null
     */
    public function getGiftMessageId()
    {
        return $this->_get(self::GIFT_MESSAGE_ID);
    }

    /**
     * set gift message id
     *
     * @param $giftMessageId
     * @return $this
     */
    public function setGiftMessageId($giftMessageId)
    {
        return $this->setData(self::GIFT_MESSAGE_ID,$giftMessageId);
    }

    /**
     * get created date
     *
     * @return mixed|null
     */
    public function getCreatedDate()
    {
        return $this->_get(self::CREATED_AT);
    }

    /**
     * set create date
     *
     * @param $createdDate
     * @return $this
     */
    public function setCreatedDate($createdDate)
    {
        return $this->setData(self::CREATED_AT,$createdDate);
    }

    /**
     * get updated date
     *
     * @return mixed|null
     */
    public function getUpdatedDate()
    {
        return $this->_get(self::UPDATED_AT);
    }

    /**
     * set update date
     *
     * @param $updatedDate
     * @return $this
     */
    public function setUpdatedDate($updatedDate)
    {
        return $this->setData(self::UPDATED_AT, $updatedDate);
    }

    /**
     * @return string|null
     */
    public function getOriginalDeliveryDate()
    {
        return $this->_get(self::ORIGINAL_DELIVERY_DATE);
    }

    /**
     * @param string $originalDeliveryDate
     * @return $this
     */
    public function setOriginalDeliveryDate($originalDeliveryDate)
    {
        return $this->setData(self::ORIGINAL_DELIVERY_DATE, $originalDeliveryDate);
    }

    /**
     * @return int|null
     */
    public function getOriginalDeliveryTimeSlot()
    {
        return $this->_get(self::ORIGINAL_DELIVERY_TIME_SLOT);
    }

    /**
     * @param int $originalDeliveryTimeSlot
     * @return $this
     */
    public function setOriginalDeliveryTimeSlot($originalDeliveryTimeSlot)
    {
        return $this->setData(self::ORIGINAL_DELIVERY_TIME_SLOT, $originalDeliveryTimeSlot);
    }
}
