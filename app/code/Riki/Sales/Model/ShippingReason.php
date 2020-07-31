<?php

namespace Riki\Sales\Model;

use Magento\Framework\Model\AbstractModel;
use Riki\Sales\Api\Data\ShippingReason\ShippingReasonInterface;

class ShippingReason extends AbstractModel implements ShippingReasonInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'riki_shipping_reason';

    /**
     * Initialise resource model
     * @codingStandardsIgnoreStart
     */
    protected function _construct()
    {
        // @codingStandardsIgnoreEnd
        $this->_init('Riki\Sales\Model\ResourceModel\ShippingReason');
    }

    /**
     * Get cache identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get reson description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->getData(ShippingReasonInterface::DESCRIPTION);
    }

    /**
     * Set reson description
     *
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        return $this->setData(ShippingReasonInterface::DESCRIPTION, $description);
    }

    /**
     * Get is active
     *
     * @return bool|int
     */
    public function getIsActive()
    {
        return $this->getData(ShippingReasonInterface::IS_ACTIVE);
    }

    /**
     * Set is active
     *
     * @param $isActive
     * @return $this
     */
    public function setIsActive($isActive)
    {
        return $this->setData(ShippingReasonInterface::IS_ACTIVE, $isActive);
    }

    /**
     * Get created at
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(ShippingReasonInterface::CREATED_AT);
    }

    /**
     * Set created at
     *
     * @param $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(ShippingReasonInterface::CREATED_AT, $createdAt);
    }

    /**
     * Get updated at
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData(ShippingReasonInterface::UPDATED_AT);
    }

    /**
     * Set updated at
     *
     * @param $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(ShippingReasonInterface::UPDATED_AT, $updatedAt);
    }
}
