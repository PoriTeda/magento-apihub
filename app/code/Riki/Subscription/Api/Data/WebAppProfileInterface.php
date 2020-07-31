<?php

namespace Riki\Subscription\Api\Data;

interface WebAppProfileInterface
{
    /**#@+
     * Constants defined for keys of the data array. Identical to the name of the getter in snake case
     */
    const ID = 'id';
    const PROFILE_PRODUCT_CART = 'profile_product_cart';
    const COUPON_CODE = 'coupon_code';

    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);


    /**
     * @return \Riki\Subscription\Api\Data\Profile\ProductInterface[]
     */
    public function getProfileProductCart();

    /**
     * @param \Riki\Subscription\Api\Data\Profile\ProductInterface[] $profileProductCart
     * @return $this
     */
    public function setProfileProductCart(array $profileProductCart = null);

    /**
     * @return string
     */
    public function getCouponCode();

    /**
     * @param string $couponCode
     * @return $this
     */
    public function setCouponCode($couponCode);
}