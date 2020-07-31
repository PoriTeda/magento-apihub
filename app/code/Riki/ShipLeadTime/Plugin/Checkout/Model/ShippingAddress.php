<?php

namespace Riki\ShipLeadTime\Plugin\Checkout\Model;

class ShippingAddress
{

    /** @var \Riki\ShipLeadTime\Helper\Data  */
    protected $shipLeadTimeHelper;

    /**
     * ShippingAddress constructor.
     * @param \Riki\ShipLeadTime\Helper\Data $shipLeadTimeHelper
     */
    public function __construct(
        \Riki\ShipLeadTime\Helper\Data $shipLeadTimeHelper
    )
    {
        $this->shipLeadTimeHelper = $shipLeadTimeHelper;
    }

    /**
     * @param \Riki\Checkout\Model\ShippingAddress $shippingAddress
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote $quote
     * @param $addressId
     * @param array $cartItems
     * @return mixed
     */
    public function aroundGenerateCartItemData(
        \Riki\Checkout\Model\ShippingAddress $shippingAddress,
        \Closure $proceed,
        \Magento\Quote\Model\Quote $quote,
        $addressId,
        array $cartItems
    )
    {
        $result = $proceed($quote, $addressId, $cartItems);

        $itemIds = array_map(function($itemData) {
            return $itemData['id'];
        }, $cartItems);

        $errors = $this->shipLeadTimeHelper->validateQuoteAddress($quote, $addressId, $itemIds);

        $result['items_error_messages'] = [];

        foreach ($result['cartItems'] as $itemData) {
            if (isset($errors[$itemData['item_id']])) {
                $result['items_error_messages'][$itemData['item_id']] = $errors[$itemData['item_id']];
            }
        }

        return $result;
    }
}
