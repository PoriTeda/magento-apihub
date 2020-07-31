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
 * Interface CustomerInfoInterface
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
interface CustomerInfoInterface
{
    /**
     * Gets the consumerDbId.
     *
     * @return int
     */
    public function getConsumerDbId();

    /**
     * Sets the consumerDbId.
     *
     * @param string $consumerDbId consumer_id
     *
     * @return void
     */
    public function setConsumerDbId($consumerDbId);
}
