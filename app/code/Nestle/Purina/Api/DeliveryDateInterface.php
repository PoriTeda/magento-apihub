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

/**
 * Interface DeliveryDateInterface
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
interface DeliveryDateInterface
{
    /**
     * Calculate delivery date/time-slot based on the given cart_id, address ID
     *
     * @param string $cartId    cart_id
     * @param string $addressId address_id
     *
     * @return \Nestle\Purina\Api\Data\DeliverytimeDataInterface[]|mixed
     */
    public function calculateDeliveryDate($cartId, $addressId);
}