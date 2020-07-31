<?php

namespace Riki\Catalog\Bundle\Pricing\Price;

use Magento\Bundle\Model\Product\Price;

class BundleSelectionPrice extends \Magento\Bundle\Pricing\Price\BundleSelectionPrice
{
    /**
     * @return bool|float
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getValue()
    {
        $value = parent::getValue();
        $selectionPriceType = $this->product->getSelectionPriceType();
        $priceTYpeFixed = $this->bundleProduct->getPriceType() == Price::PRICE_TYPE_FIXED;
        if (!$selectionPriceType && $priceTYpeFixed) {
            /**
             * Only reset qty after calculate price  ;
             */
            //$this->quantity = 1;
        }

        return $value;
    }
}
