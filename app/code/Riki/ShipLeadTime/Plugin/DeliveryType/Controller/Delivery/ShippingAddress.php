<?php

namespace Riki\ShipLeadTime\Plugin\DeliveryType\Controller\Delivery;

class ShippingAddress
{
    /** @var \Riki\ShipLeadTime\Helper\Data  */
    protected $shipLeadTimeHelper;

    protected $helperMachine;
    /**
     * ShippingAddress constructor.
     * @param \Riki\ShipLeadTime\Helper\Data $shipLeadTimeHelper
     */
    public function __construct(
        \Riki\ShipLeadTime\Helper\Data $shipLeadTimeHelper,
        \Riki\MachineApi\Helper\Machine $helperMachine
    )
    {
        $this->shipLeadTimeHelper = $shipLeadTimeHelper;
        $this->helperMachine = $helperMachine;
    }

    /**
     * @param \Riki\DeliveryType\Controller\Delivery\ShippingAddress $shippingAddress
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote $quote
     * @param $destination
     * @return mixed
     */
    public function aroundGenerateCartItemData(
        \Riki\DeliveryType\Controller\Delivery\ShippingAddress $shippingAddress,
        \Closure $proceed,
        \Magento\Quote\Model\Quote $quote,
        $destination
    ) {
        $result = $proceed($quote, $destination);

        $errors = $this->shipLeadTimeHelper->validateQuoteRegion($quote, $destination['region_code']);
        $oosMachineItems = [];
        if ($this->helperMachine->skipValidate($quote)) {
            $oosMachineItems = $this->helperMachine->getOosB2cMachineItems($quote);
        }
        foreach ($result as $index => $groupData) {

            $result[$index]['items_error_messages'] = [];
            $result[$index]['machine_oos_messages'] = [];

            foreach ($groupData['cartItems'] as $quoteItemId) {
                if (isset($errors[$quoteItemId])) {
                    $result[$index]['items_error_messages'][$quoteItemId] = $errors[$quoteItemId];
                }
                /** Set message OOS for machine product */
                foreach ($oosMachineItems as $oosMachineItem) {
                    if ($oosMachineItem->getItemId() == $quoteItemId) {
                        $result[$index]['machine_oos_messages'][$quoteItemId] = __(
                            'Due to shortage, the machine may be shipped separately.'
                        );
                    }
                }
            }
        }

        return $result;
    }
}
