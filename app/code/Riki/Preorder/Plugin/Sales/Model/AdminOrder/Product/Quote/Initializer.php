<?php

namespace Riki\Preorder\Plugin\Sales\Model\AdminOrder\Product\Quote;

class Initializer
{
    protected $_helper;

    /**
     * @param \Riki\Preorder\Helper\Admin $helper
     */
    public function __construct(
        \Riki\Preorder\Helper\Admin $helper
    )
    {
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

        if($quote->getItemsCount()){

            if($this->_helper->isPreOrderCart()){
                foreach($quote->getAllVisibleItems() as $item){
                    if($product->getId() != $item->getProductId()){
                        return 'Can not add a normal product to a pre-order';
                    }
                }
            }else{
                if($this->_helper->checkCanPreOrder($product->getId())){
                    return 'Can not add a pre-order product to a normal order';
                }
            }
        }

        return $proceed($quote, $product, $config);
    }
}