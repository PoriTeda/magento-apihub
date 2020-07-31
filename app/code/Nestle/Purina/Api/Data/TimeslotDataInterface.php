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
namespace Nestle\Purina\Api\Data;

/**
 * Interface TimeslotDataInterface
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
interface TimeslotDataInterface
{
    const KEY_LABEL = 'label';
    const KEY_VALUE = 'value';

    /**
     * Time slot label
     *
     * @param string $label time label
     *
     * @return $this
     */
    public function setLabel($label);

    /**
     * Time slot label
     *
     * @return string
     */
    public function getLabel();

    /**
     * Time slot value
     *
     * @param string $value time slot
     *
     * @return $this
     */
    public function setValue($value);

    /**
     * Time slot value
     *
     * @return int
     */
    public function getValue();
}
