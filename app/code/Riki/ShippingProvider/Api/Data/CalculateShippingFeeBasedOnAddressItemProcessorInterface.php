<?php
/**
 * ShippingProvider
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShippingProvider
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\ShippingProvider\Api\Data;

/**
 * CalculateShippingFeeBasedOnAddressItemProcessorInterface
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShippingProvider
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
interface CalculateShippingFeeBasedOnAddressItemProcessorInterface
{
    /**
     * CalculateShippingFeeBaseOnAddressItem
     *
     * @param \Magento\Quote\Api\Data\CartInterface $cart      CartInterface
     * @param array                                 $cartItems Cart Items
     *
     * @return mixed
     */
    public function calculateShippingFeeBaseOnAddressItem(
        \Magento\Quote\Api\Data\CartInterface $cart,
        array $cartItems
    );
}

