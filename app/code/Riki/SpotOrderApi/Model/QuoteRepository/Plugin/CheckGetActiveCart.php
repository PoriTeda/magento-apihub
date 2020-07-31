<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SpotOrderApi\Model\QuoteRepository\Plugin;

use Magento\Framework\Exception\NoSuchEntityException;

class CheckGetActiveCart
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
     * @param \Magento\Quote\Model\QuoteRepository $subject
     * @param \Closure $proceed
     * @param $cartId
     * @return \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote|mixed
     * @throws NoSuchEntityException
     */
    public function aroundGetActive(
        \Magento\Quote\Model\QuoteRepository $subject,
        \Closure $proceed,
        $cartId
    ) {
        if($this->_checkRequestApi->checkCallApi())
        {
            $quote = $subject->get($cartId);
            if($quote && !$quote->getIsActive()){
                if($quote->getReservedOrderId() !=null &&  $quote->getReservedOrderId() !='0')
                {
                    throw NoSuchEntityException::singleField('cartId', $quote->getId());
                }
                return $quote;
            }
        }
        return $proceed($cartId);
    }

}
