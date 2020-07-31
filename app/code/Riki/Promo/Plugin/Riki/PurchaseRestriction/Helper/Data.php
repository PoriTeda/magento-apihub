<?php
namespace Riki\Promo\Plugin\Riki\PurchaseRestriction\Helper;

class Data
{
    protected $_helper;

    /**
     * @param \Riki\Promo\Helper\Data $helper
     */
    public function __construct(
        \Riki\Promo\Helper\Data $helper
    ){
        $this->_helper = $helper;
    }

    /**
     * @param \Riki\PurchaseRestriction\Helper\Data $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return bool|mixed
     */
    public function aroundNeedToValidate(
        \Riki\PurchaseRestriction\Helper\Data $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $quoteItem
    ) {

        if($this->_helper->isPromoItem($quoteItem))
            return false;

        return $proceed($quoteItem);
    }

    /**
     * @param \Riki\PurchaseRestriction\Helper\Data $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param $qty
     * @return bool|mixed
     */
    public function aroundValidatePurchaseRestrictionQuoteItemWithQty(
        \Riki\PurchaseRestriction\Helper\Data $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $quoteItem,
        $qty
    ) {

        if($this->_helper->isPromoItem($quoteItem))
            return true;

        return $proceed($quoteItem, $qty);
    }
}
