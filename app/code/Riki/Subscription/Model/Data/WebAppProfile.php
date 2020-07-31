<?php

namespace Riki\Subscription\Model\Data;

use Riki\Subscription\Api\Data\WebAppProfileInterface;

class WebAppProfile extends \Magento\Framework\Api\AbstractSimpleObject implements \Riki\Subscription\Api\Data\WebAppProfileInterface
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->_get(self::ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getProfileProductCart()
    {
        return $this->_get(self::PROFILE_PRODUCT_CART);
    }

    /**
     * {@inheritdoc}
     */
    public function setProfileProductCart(array $profileProductCart = null)
    {
        return $this->setData(self::PROFILE_PRODUCT_CART, $profileProductCart);
    }

    /**
     * {@inheritdoc}
     */
    public function getCouponCode()
    {
        return $this->_get(self::COUPON_CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function setCouponCode($couponCode)
    {
        return $this->setData(self::COUPON_CODE, $couponCode);
    }
}