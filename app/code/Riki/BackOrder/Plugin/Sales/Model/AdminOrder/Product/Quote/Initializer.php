<?php

namespace Riki\BackOrder\Plugin\Sales\Model\AdminOrder\Product\Quote;

class Initializer
{
    protected $_helper;

    /**
     * @param \Riki\BackOrder\Helper\Admin $helper
     */
    public function __construct(
        \Riki\BackOrder\Helper\Admin $helper
    ){
        $this->_helper = $helper;
    }

    /**
     * validate add product
     *
     * @param \Magento\Sales\Model\AdminOrder\Product\Quote\Initializer $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Framework\DataObject $config
     * @return \Magento\Framework\Phrase
     */
    public function aroundInit(
        \Magento\Sales\Model\AdminOrder\Product\Quote\Initializer $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\DataObject $config
    ) {

        $qty = $config->getQty();

        $validateResult = $this->_helper->validateProduct($product, $qty);

        if(is_string($validateResult)){
            return $validateResult;
        }

        return $proceed($quote, $product, $config);
    }
}