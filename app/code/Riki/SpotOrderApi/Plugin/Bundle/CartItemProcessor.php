<?php

namespace Riki\SpotOrderApi\Plugin\Bundle;

class CartItemProcessor
{

    /**
     * @var \Riki\SpotOrderApi\Helper\CheckRequestApi
     */
    protected $_checkRequestApi;

    /**
     * CheckGetActiveCart constructor.
     * @param \Riki\SpotOrderApi\Helper\CheckRequestApi $checkRequestApi
     */
    public function __construct(
        \Riki\SpotOrderApi\Helper\CheckRequestApi $checkRequestApi
    )
    {
        $this->_checkRequestApi = $checkRequestApi;
    }

    /**
     * @param \Magento\Bundle\Model\CartItemProcessor $subject
     * @param \Closure $proceed
     * @param $cartItem
     * @return mixed
     */
    public function aroundProcessOptions(
        \Magento\Bundle\Model\CartItemProcessor $subject,
        \Closure $proceed,
        $cartItem
    )
    {
        if($this->_checkRequestApi->checkCallApi())
        {
            /**
             * Default api not set product bundle option
             */
            if ($cartItem->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                return $cartItem;
            }
        }

        //return default process
        return $proceed($cartItem);
    }


}