<?php

namespace Riki\OfflineShipping\Model\Quote\Address;

class FreeShipping extends \Magento\OfflineShipping\Model\Quote\Address\FreeShipping
{
    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\CartItemInterface[] $items
     *
     * @return bool
     */
    public function isFreeShipping(\Magento\Quote\Model\Quote $quote, $items)
    {
        $this->calculator->setData('quote', $quote);

        if($quote->getData('is_monthly_fee')){
            return true;
        }
        return parent::isFreeShipping($quote, $items);
    }
}
