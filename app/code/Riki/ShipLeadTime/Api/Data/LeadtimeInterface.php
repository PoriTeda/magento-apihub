<?php
namespace Riki\ShipLeadTime\Api\Data;

interface LeadtimeInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**#@+
     * Constants defined for keys of the data array. Identical to the name of the getter in snake case
     */
    const ID = 'id';
    const SHIPPING_LEAD_TIME = 'shipping_lead_time';
    const IS_ACTIVE = 'is_active';
    const DELIVERY_TYPE_CODE = 'delivery_type_code';
    /**#@-*/

    /**
     * @return int
     */
    public function getId();

    /**
     * @return boolean
     */
    public function getIsActive();

    /**
     * @return self
     */
    public function setIsActive($isActive);

    /**
     * @return int
     */
    public function getShippingLeadTime();

    /**
     * @return mixed
     */
    public function getDeliveryTypeCode();

}
