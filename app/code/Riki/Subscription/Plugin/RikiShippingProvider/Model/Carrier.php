<?php

namespace Riki\Subscription\Plugin\RikiShippingProvider\Model;

class Carrier
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(\Magento\Framework\Registry $registry){
        $this->_registry = $registry;
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
    ){

        if(
            $this->_registry->registry(\Riki\Subscription\Helper\Order\Data::PROFILE_GENERATE_STATE_REGISTRY_NAME) &&
            $request instanceof \Magento\Quote\Model\Quote\Address\RateRequest
        ){ // for generate order case

            $requestItems = $request->getAllItems();

            if(
                count($requestItems) &&
                isset($requestItems[0]) &&
                $requestItems[0] instanceof \Magento\Quote\Model\Quote\Item
            ){
                $subject->setQuote($requestItems[0]->getQuote());
            }
        }

        return [$request];
    }
}