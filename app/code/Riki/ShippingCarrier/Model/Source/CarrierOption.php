<?php
namespace Riki\ShippingCarrier\Model\Source;

class CarrierOption extends \Riki\Framework\Model\Source\AbstractOption
{
    /**
     * @var \Riki\ShippingCarrier\Helper\CarrierHelper
     */
    protected $carrierHelper;

    /**
     * CarrierOption constructor.
     *
     * @param \Riki\ShippingCarrier\Helper\CarrierHelper $carrierHelper
     */
    public function __construct(
        \Riki\ShippingCarrier\Helper\CarrierHelper $carrierHelper
    ) {
        $this->carrierHelper = $carrierHelper;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->carrierHelper->getCarrierOptions();
    }
}
