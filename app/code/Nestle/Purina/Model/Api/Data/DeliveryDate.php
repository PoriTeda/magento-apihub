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
 * Class DeliveryDate
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
class DeliveryDate
  extends \Magento\Framework\Api\AbstractExtensibleObject
  implements \Nestle\Purina\Api\Data\DeliverytimeDataInterface
{
    /**
     * Back orders
     *
     * @return bool|mixed|null
     */
    public function getBackOrder()
    {
        return $this->_get(self::KEY_BACKORDER);
    }

    /**
     * Back orders
     *
     * @param \Nestle\Purina\Api\Data\Array $backOrder Back order
     *
     * @return \Nestle\Purina\Api\Data\DeliverytimeDataInterface|DeliveryDate
     */
    public function setBackOrder($backOrder)
    {
        return $this->setData(self::KEY_BACKORDER, $backOrder);
    }

    /**
     * Cart items
     *
     * @return mixed|string[]|null
     */
    public function getCartItems()
    {
        return $this->_get(self::KEY_CARTITEMS);
    }

    /**
     * Cart items
     *
     * @param array $cartItems cart Items
     *
     * @return \Nestle\Purina\Api\Data\DeliverytimeDataInterface|DeliveryDate
     */
    public function setCartItems(array $cartItems)
    {
        return $this->setData(self::KEY_CARTITEMS, $cartItems);
    }

    /**
     * Delivery date
     *
     * @param string $deliveryDate Delivery date
     *
     * @return \Nestle\Purina\Api\Data\DeliverytimeDataInterface|DeliveryDate
     */
    public function setDeliverydate($deliveryDate)
    {
        return $this->setData(self::KEY_DELIVERYDATE, $deliveryDate);
    }

    /**
     * Delivery date
     *
     * @return mixed|string[]|null
     */
    public function getDeliverydate()
    {
        return $this->_get(self::KEY_DELIVERYDATE);
    }

    /**
     * Start Date
     *
     * @param string $firstDate Start Date
     *
     * @return \Nestle\Purina\Api\Data\DeliverytimeDataInterface|DeliveryDate
     */
    public function setFirstDate($firstDate)
    {
        return $this->setData(self::KEY_FIRSTDATE, $firstDate);
    }

    /**
     * Start Date
     *
     * @return mixed|string|null
     */
    public function getFirstDate()
    {
        return $this->_get(self::KEY_FIRSTDATE);
    }

    /**
     * Name
     *
     * @param String $name name
     *
     * @return \Nestle\Purina\Api\Data\DeliverytimeDataInterface|DeliveryDate
     */
    public function setName($name)
    {
        return $this->setData(self::KEY_GROUPNAME, $name);
    }

    /**
     * Name
     *
     * @return mixed|string|null
     */
    public function getName()
    {
        return $this->_get(self::KEY_GROUPNAME);
    }

    /**
     * Period
     *
     * @param \Nestle\Purina\Api\Data\Array $period Period
     *
     * @return \Nestle\Purina\Api\Data\DeliverytimeDataInterface|DeliveryDate
     */
    public function setPeriod($period)
    {
        return $this->setData(self::KEY_PERIOD, $period);
    }

    /**
     * Period
     *
     * @return int|mixed|null
     */
    public function getPeriod()
    {
        return $this->_get(self::KEY_PERIOD);
    }

    /**
     * Server Information
     *
     * @param array $serverInfo Server information
     *
     * @return \Nestle\Purina\Api\Data\DeliverytimeDataInterface|DeliveryDate
     */
    public function setServerInfo(array $serverInfo)
    {
        return $this->setData(self::KEY_SERVERINFO, $serverInfo);
    }

    /**
     * Server Information
     *
     * @return mixed|string[]|null
     */
    public function getServerInfo()
    {
        return $this->_get(self::KEY_SERVERINFO);
    }

    /**
     * Time-slot
     *
     * @param array $timeslot Time-slot
     *
     * @return \Nestle\Purina\Api\Data\DeliverytimeDataInterface|DeliveryDate
     */
    public function setTimeslot(array $timeslot)
    {
        return $this->setData(self::KEY_TIMESLOT, $timeslot);
    }

    /**
     * Time-slot
     *
     * @return mixed|\Nestle\Purina\Api\Data\TimeslotDataInterface[]|null
     */
    public function getTimeslot()
    {
        return $this->_get(self::KEY_TIMESLOT);
    }
}
