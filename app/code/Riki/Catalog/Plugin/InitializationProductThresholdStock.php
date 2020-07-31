<?php

namespace Riki\Catalog\Plugin;

class InitializationProductThresholdStock
{
    /**
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $subject
     * @param $product
     * @return mixed
     */
    public function afterInitialize(\Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $subject,$product)
    {
        if($product->getData('unit_qty') > 1 && $product->getData('case_display') == 2){
            $stockData = $product->getData('stock_data');
        }
        return $product;
    }
}
