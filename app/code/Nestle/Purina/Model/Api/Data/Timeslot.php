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
namespace Nestle\Purina\Model\Api\Data;

/**
 * Class Timeslot
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
class Timeslot
    extends \Magento\Framework\Api\AbstractExtensibleObject
    implements \Nestle\Purina\Api\Data\TimeslotDataInterface
{
    /**
     * Set Value
     *
     * @param string $value value
     *
     * @return \Nestle\Purina\Api\Data\TimeslotDataInterface|Timeslot
     */
    public function setValue($value)
    {
        return $this->setData(self::KEY_VALUE, $value);
    }

    /**
     * Get Value
     *
     * @return int|mixed|null
     */
    public function getValue()
    {
        return $this->_get(self::KEY_VALUE);
    }

    /**
     * Time-slot label
     *
     * @param string $label Time-slot label
     *
     * @return \Nestle\Purina\Api\Data\TimeslotDataInterface|Timeslot
     */
    public function setLabel($label)
    {
        return $this->setData(self::KEY_LABEL, $label);
    }

    /**
     * Get Time-slot label
     *
     * @return mixed|string|null
     */
    public function getLabel()
    {
        return $this->_get(self::KEY_LABEL);
    }
}
