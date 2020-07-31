<?php

namespace Riki\SpotOrderApi\Plugin\RikiShippingProvider\Model;

class Carrier
{
    /**
     * @var \Riki\SpotOrderApi\Helper\CheckRequestApi
     */
    protected $_checkRequestApi;

    /**
     * Carrier constructor.
     * @param \Riki\SpotOrderApi\Helper\CheckRequestApi $checkRequestApi
     */
    public function __construct(
        \Riki\SpotOrderApi\Helper\CheckRequestApi $checkRequestApi
    )
    {
        $this->_checkRequestApi = $checkRequestApi;
    }

    /**
     * set quote object for shipping carrier model
     *
     * @param \Riki\ShippingProvider\Model\Carrier $subject
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return array
     */
    public function beforeCalculateFeeForEachAddress(
        \Riki\ShippingProvider\Model\Carrier $subject,
        $request
    )
    {
        if ($this->_checkRequestApi->checkCallApi()) {
            $requestItems = $request->getAllItems();

            if (
                count($requestItems) &&
                isset($requestItems[0]) &&
                $requestItems[0] instanceof \Magento\Quote\Model\Quote\Item
            ) {
                $subject->setQuote($requestItems[0]->getQuote());
            }
        }
        return [$request];
    }
}