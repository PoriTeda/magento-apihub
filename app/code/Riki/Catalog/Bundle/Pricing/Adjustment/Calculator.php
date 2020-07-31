<?php

namespace Riki\Catalog\Bundle\Pricing\Adjustment;

use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Model\Product;

/**
 * Bundle price calculator
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Calculator extends \Magento\Bundle\Pricing\Adjustment\Calculator
{
    /**
     * Filter all options for bundle product
     *
     * @param Product $bundleProduct
     * @param bool $searchMin
     * @param bool $useRegularPrice
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function getSelectionAmounts(Product $bundleProduct, $searchMin, $useRegularPrice = false)
    {
        // Flag shows - is it necessary to find minimal option amount in case if all options are not required
        $shouldFindMinOption = false;
        if ($searchMin
            && $bundleProduct->getPriceType() == Price::PRICE_TYPE_DYNAMIC
            && !$this->hasRequiredOption($bundleProduct)
        ) {
            $shouldFindMinOption = true;
        }
        $canSkipRequiredOptions = $searchMin && !$shouldFindMinOption;

        $currentPrice = false;
        $priceList = [];
        foreach ($this->getBundleOptions($bundleProduct) as $option) {
            if ($this->canSkipOption($option, $canSkipRequiredOptions)) {
                continue;
            }
            $selectionPriceList = $this->createSelectionPriceList($option, $bundleProduct, $useRegularPrice);
            $selectionPriceList = $this->processOptions($option, $selectionPriceList, $searchMin);

            $lastSelectionPrice = end($selectionPriceList);

            if(!$lastSelectionPrice) // custom
                continue;

            $lastValue = $lastSelectionPrice->getAmount()->getValue() * $lastSelectionPrice->getQuantity();
            if ($shouldFindMinOption
                && (!$currentPrice ||
                    $lastValue < ($currentPrice->getAmount()->getValue() * $currentPrice->getQuantity()))
            ) {
                $currentPrice = end($selectionPriceList);
            } elseif (!$shouldFindMinOption) {
                $priceList = array_merge($priceList, $selectionPriceList);
            }
        }
        return $shouldFindMinOption ? [$currentPrice] : $priceList;
    }
}
