<?php
namespace Riki\Subscription\Api\WebApi;
/**
 * @api
 */
interface SubProfileCartOrderInterface
{
    /**
     * @return int
     */
    public function getCartID();

    /**
     * @param int $id
     * @return $this
     */
    public function setCartID($id);

    /**
     * @return string
     */
    public function getNextDeliveryDate();

    /**
     * @param string $date
     * @return $this
     */
    public function setNextDeliveryDate($date);

    /**
     * @return int
     */
    public function getNextDeliverySlotID();

    /**
     * @param int $id
     * @return $this
     */
    public function setNextDeliverySlotID($id);

    /**
     * @return int
     */
    public function getCurrentSelectedShippingAddress();

    /**
     * @param int $id
     * @return $this
     */
    public function setCurrentSelectedShippingAddress($id);

    /**
     * @return \Riki\Subscription\Api\WebApi\SubProfileCartProductsInterface[]|null
     */
    public function getSubProfileCartProducts();

    /**
     * @param \Riki\Subscription\Api\WebApi\SubProfileCartProductsInterface[] $subProfileCartProducts
     * @return $this
     */
    public function setSubProfileCartProducts($subProfileCartProducts);

    /**
     * @return string
     */
    public function getOriginalDeliveryDate();

    /**
     * @param string $originalDeliveryDate
     * @return $this
     */
    public function setOriginalDeliveryDate($originalDeliveryDate);

    /**
     * @return string
     */
    public function getOriginalDeliveryTimeSlot();

    /**
     * @param string $originalDeliveryTimeSlot
     * @return $this
     */
    public function setOriginalDeliveryTimeSlot($originalDeliveryTimeSlot);
}