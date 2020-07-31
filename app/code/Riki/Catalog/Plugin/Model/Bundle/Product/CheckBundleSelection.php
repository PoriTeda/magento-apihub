<?php

namespace Riki\Catalog\Plugin\Model\Bundle\Product;

class CheckBundleSelection
{
    /**
     * @param \Magento\Bundle\Model\Product\Type $subject
     * @param \Closure $proceed
     * @param $product
     * @return mixed
     */
    public function aroundGetWeight(\Magento\Bundle\Model\Product\Type $subject,\Closure $proceed,$product)
    {
        if(!$product->getData('weight_type') && $product->hasCustomOptions() && !$product->getCustomOption('bundle_selection_ids')){
            return 0;
        }
        else{
            return $proceed($product);
        }

    }
}
