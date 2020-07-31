<?php
namespace Riki\AdvancedInventory\Plugin\Riki\Sales\Observer;

class AdminOrderProcessDataBefore
{
    protected $_helper;

    /**
     * @param \Riki\AdvancedInventory\Helper\Data $helper
     */
    public function __construct(
        \Riki\AdvancedInventory\Helper\Data $helper
    ){
        $this->_helper = $helper;
    }

    /**
     * validate bundle child stock
     *
     * @param \Riki\Sales\Observer\AdminOrderProcessDataBefore $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return \Magento\Framework\Phrase
     */
    public function aroundValidateStockByQuoteItem(
        \Riki\Sales\Observer\AdminOrderProcessDataBefore $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $quoteItem
    ){
        $result = $proceed($quoteItem);

        if($result === true){
            if(!$this->_helper->isInStockBundleItem($quoteItem))
                return __('The product %1  is out of stock.', $quoteItem->getName());
        }

        return $result;
    }
}