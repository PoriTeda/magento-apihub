<?php

namespace Riki\Promo\Plugin;

class RemoveFreeProductQtyBeforeRuleValidation
{
    /**
     * Set total quantity before validating cart rule
     * @param \Magento\SalesRule\Model\Utility $subject
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param \Magento\Quote\Model\Quote\Address $address
     * @return array
     */
    public function beforeCanProcessRule(
        \Magento\SalesRule\Model\Utility $subject,
        \Magento\SalesRule\Model\Rule $rule,
        \Magento\Quote\Model\Quote\Address $address
    ) {
        $totalQty = $this->calculateTotalQty($address);
        $address->setTotalQty($totalQty);

        return [$rule, $address];
    }

    /**
     * Calculate total quantity without gift items based on case unit, not each unit.
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return float|int
     */
    private function calculateTotalQty(
        \Magento\Quote\Api\Data\AddressInterface $address
    ) {
        $items = $address->getAllItems();
        $totalQty = 0;
        foreach ($items as $item) {
            // if this item is not in any promotion
            if (!$item->getAmpromoRuleId()) {
                // quantity will be counted on case, not each.
                $totalQty += $item->getQty() / $item->getUnitQty();
            }
        }

        return $totalQty;
    }
}
