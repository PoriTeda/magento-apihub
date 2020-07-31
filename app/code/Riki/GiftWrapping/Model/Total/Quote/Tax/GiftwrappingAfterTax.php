<?php

namespace Riki\GiftWrapping\Model\Total\Quote\Tax;

use Magento\Quote\Model\Quote\Address;

/**
 * GiftWrapping tax total calculator for quote
 */
class GiftwrappingAfterTax extends \Magento\GiftWrapping\Model\Total\Quote\Tax\GiftwrappingAfterTax
{
    /**
     * Update wrapping tax total for items
     *
     * @param Address\Total $total
     * @param array $itemTaxDetails
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function processWrappingForItems($total, $itemTaxDetails)
    {
        $gwItemCodeToItemMapping = $total->getGwItemCodeToItemMapping();
        $wrappingForItemsBaseTaxAmount = null;
        $wrappingForItemsTaxAmount = null;
        $wrappingForItemsInclTax = null;
        $baseWrappingForItemsInclTax = null;

        foreach ($itemTaxDetails as $itemCode => $itemTaxDetail) {
            $itemTaxDetailCount = count($itemTaxDetail);
            if ($itemTaxDetailCount < 1) {
                continue;
            }

            // order may have multiple giftwrapping items
            for ($i = 0; $i < $itemTaxDetailCount; $i++) {
                $gwTaxDetail = $itemTaxDetail[$i];
                $gwItemCode = $gwTaxDetail['code'];

                if (!array_key_exists($gwItemCode, $gwItemCodeToItemMapping)) {
                    continue;
                }
                $item = $gwItemCodeToItemMapping[$gwItemCode];

                // search for the right giftwrapping item associated with the address
                if ($item != null) {
                    break;
                }
            }

            $unitQty = (null != $item->getUnitQty()) ? $item->getUnitQty() : 1;

            $wrappingBaseTaxAmount = $gwTaxDetail['base_row_tax'];
            $wrappingTaxAmount = $gwTaxDetail['row_tax'];
            $wrappingForItemsInclTax += $gwTaxDetail['price_incl_tax'] * (('CS' == $item->getUnitCase()) ? ($item->getQty() / $unitQty) : ($item->getQty()));
            $baseWrappingForItemsInclTax += $gwTaxDetail['base_price_incl_tax'] * (('CS' == $item->getUnitCase()) ? ($item->getQty() / $unitQty) : ($item->getQty()));

            $item->setGwBaseTaxAmount($wrappingBaseTaxAmount / (('CS' == $item->getUnitCase()) ? ($item->getQty() / $unitQty) : ($item->getQty())));
            $item->setGwTaxAmount($wrappingTaxAmount / (('CS' == $item->getUnitCase()) ? ($item->getQty() / $unitQty) : ($item->getQty())));

            $wrappingForItemsBaseTaxAmount += $wrappingBaseTaxAmount;
            $wrappingForItemsTaxAmount += $wrappingTaxAmount;
        }

        $total->setGwItemsBaseTaxAmount($wrappingForItemsBaseTaxAmount);
        $total->setGwItemsTaxAmount($wrappingForItemsTaxAmount);
        $total->setGwItemsPriceInclTax($wrappingForItemsInclTax);
        $total->setGwItemsBasePriceInclTax($baseWrappingForItemsInclTax);
        return $this;
    }
}
