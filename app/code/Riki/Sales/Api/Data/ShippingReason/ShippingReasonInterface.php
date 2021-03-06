<?php

namespace Riki\Sales\Api\Data\ShippingReason;

interface ShippingReasonInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const ID                = 'id';
    const DESCRIPTION       = 'description';
    const IS_ACTIVE         = 'is_active';
    const CREATED_AT        = 'created_at';
    const UPDATED_AT        = 'updated_at';


    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set ID
     *
     * @param $id
     * @return ShippingReasonInterface
     */
    public function setId($id);

    /**
     * Get Reason Description
     *
     * @return mixed
     */
    public function getDescription();

    /**
     * Set Data Description
     *
     * @param $description
     * @return mixed
     */
    public function setDescription($description);

    /**
     * Get is active
     *
     * @return bool|int
     */
    public function getIsActive();

    /**
     * Set is active
     *
     * @param $isActive
     * @return ShippingReasonInterface
     */
    public function setIsActive($isActive);

    /**
     * Get created at
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * set created at
     *
     * @param $createdAt
     * @return ShippingReasonInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated at
     *
     * @return string
     */
    public function getUpdatedAt();

    /**
     * set updated at
     *
     * @param $updatedAt
     * @return ShippingReasonInterface
     */
    public function setUpdatedAt($updatedAt);
}
