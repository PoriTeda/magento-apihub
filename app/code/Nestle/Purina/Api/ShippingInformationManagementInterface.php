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

use Magento\Checkout\Api\Data\ShippingInformationInterface;
/**
 * Interface ShippingInformationManagementInterface
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
interface ShippingInformationManagementInterface
 extends \Magento\Checkout\Api\ShippingInformationManagementInterface
{
    /**
     * Save address and apply point
     *
     * @param int                          $cartId             cart_id
     * @param ShippingInformationInterface $addressInformation shipping_address
     * @param int                          $usedPoints         points used
     * @param int                          $option             point option
     *
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     */
    public function applyPointAndSaveAddressInformation(
        $cartId,
        ShippingInformationInterface $addressInformation,
        $usedPoints,
        $option
    );
}
