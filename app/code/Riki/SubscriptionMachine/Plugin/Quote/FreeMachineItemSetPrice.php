<?php

namespace Riki\SubscriptionMachine\Plugin\Quote;

class FreeMachineItemSetPrice
{
    /**
     * Set quote item price to zero for free machine item
     * @param \Magento\Quote\Model\Quote\Item $subject
     * @param $value
     * @return array
     */
    public function beforeSetPrice(\Magento\Quote\Model\Quote\Item $subject, $value)
    {
        if ($subject->getData('is_riki_machine')) {
            $buyRequest = $subject->getBuyRequest();
            if (isset($buyRequest['options']['free_machine_item'])) {
                return [0];
            }
        }
        return [$value];
    }
}
