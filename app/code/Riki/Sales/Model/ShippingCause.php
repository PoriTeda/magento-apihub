<?php

namespace Riki\Sales\Model;

use Magento\Framework\Model\AbstractModel;
use Riki\Sales\Api\Data\ShippingCause\ShippingCauseInterface;

class ShippingCause extends AbstractModel implements ShippingCauseInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'riki_shipping_cause';

    /**
     * Initialise resource model
     * @codingStandardsIgnoreStart
     */
    protected function _construct()
    {
        // @codingStandardsIgnoreEnd
        $this->_init('Riki\Sales\Model\ResourceModel\ShippingCause');
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
     * Get cause description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->getData(ShippingCauseInterface::DESCRIPTION);
    }

    /**
     * Set cause description
     *
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        return $this->setData(ShippingCauseInterface::DESCRIPTION, $description);
    }

    /**
     * Get is active
     *
     * @return bool|int
     */
    public function getIsActive()
    {
        return $this->getData(ShippingCauseInterface::IS_ACTIVE);
    }

    /**
     * Set is active
     *
     * @param $isActive
     * @return $this
     */
    public function setIsActive($isActive)
    {
        return $this->setData(ShippingCauseInterface::IS_ACTIVE, $isActive);
    }

    /**
     * Get created at
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(ShippingCauseInterface::CREATED_AT);
    }

    /**
     * Set created at
     *
     * @param $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(ShippingCauseInterface::CREATED_AT, $createdAt);
    }

    /**
     * Get updated at
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData(ShippingCauseInterface::UPDATED_AT);
    }

    /**
     * Set updated at
     *
     * @param $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(ShippingCauseInterface::UPDATED_AT, $updatedAt);
    }
}
