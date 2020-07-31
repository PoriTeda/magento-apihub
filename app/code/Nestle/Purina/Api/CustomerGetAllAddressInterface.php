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
 * Interface CustomerGetAllAddressInterface
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
interface CustomerGetAllAddressInterface
{
    /**
     * Retrieve Customer All address for the given $consumerDbId.
     *
     * @param string $consumerDbId consumer_id
     *
     * @return mixed
     */
    public function getCustomerAllAddress($consumerDbId);
}
