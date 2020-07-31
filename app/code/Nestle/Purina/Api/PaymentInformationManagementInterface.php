<?php
/**
 * Nestle Purina Vets
 * PHP version 7
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
namespace Nestle\Purina\Api;

use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Interface PaymentInformationManagementInterface
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
interface PaymentInformationManagementInterface
    extends \Magento\Checkout\Api\PaymentInformationManagementInterface
{
    /**
     * Save payment information and place order
     *
     * @param int                   $cartId         cart_id
     * @param PaymentInterface      $paymentMethod  payment_method
     * @param AddressInterface|null $billingAddress billing_address
     *
     * @return mixed
     */
    public function savePaymentInformationAndPlaceOrderLocal(
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    );
}
