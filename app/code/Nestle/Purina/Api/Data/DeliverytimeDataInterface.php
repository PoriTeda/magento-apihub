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
 * Interface DeliverytimeDataInterface
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
interface DeliverytimeDataInterface
{
    const KEY_BACKORDER = 'back_order';
    const KEY_CARTITEMS = 'cartItems';
    const KEY_DELIVERYDATE = 'deliverydate';
    const KEY_FIRSTDATE = 'first_date';
    const KEY_GROUPNAME = 'name';
    const KEY_PERIOD = 'period';
    const KEY_SERVERINFO  = 'serverInfo';
    const KEY_TIMESLOT = 'timeslot';

    /**
     * Back orders
     *
     * @return boolean
     */
    public function getBackOrder();

    /**
     * Back orders
     *
     * @param Array $backOrder Backorders
     *
     * @return $this
     */
    public function setBackOrder($backOrder);

    /**
     * Cart items
     *
     * @return string[]
     */
    public function getCartItems();

    /**
     * Cart Items
     *
     * @param array $cartItems cart Items
     *
     * @return $this
     */
    public function setCartItems(array $cartItems);

    /**
     * Delivery date
     *
     * @param string $deliveryDate Delivery date
     *
     * @return $this
     */
    public function setDeliverydate($deliveryDate);

    /**
     * Delivery date
     *
     * @return string[]
     */
    public function getDeliverydate();

    /**
     * Start Date
     *
     * @param string $firstDate start date
     *
     * @return $this
     */
    public function setFirstDate($firstDate);

    /**
     * Start date
     *
     * @return string
     */
    public function getFirstDate();

    /**
     * Name
     *
     * @param String $name name
     *
     * @return $this
     */
    public function setName($name);

    /**
     * Name
     *
     * @return string
     */
    public function getName();

    /**
     * Period
     *
     * @param Array $period date period
     *
     * @return $this
     */
    public function setPeriod($period);

    /**
     * Get period
     *
     * @return int
     */
    public function getPeriod();

    /**
     * ServerInfo
     *
     * @param array $serverInfo server information
     *
     * @return $this
     */
    public function setServerInfo(array $serverInfo);

    /**
     * Server information
     *
     * @return string[]
     */
    public function getServerInfo();

    /**
     * Time slot
     *
     * @param array $timeslot Time
     *
     * @return $this
     */
    public function setTimeslot(array $timeslot);

    /**
     * Time slot
     *
     * @return \Nestle\Purina\Api\Data\TimeslotDataInterface[]
     */
    public function getTimeslot();
}
