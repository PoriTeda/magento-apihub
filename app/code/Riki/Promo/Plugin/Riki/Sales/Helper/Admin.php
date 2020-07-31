<?php
namespace Riki\Promo\Plugin\Riki\Sales\Helper;

class Admin
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
     * do not split item to ship to multiple address with free item
     *
     * @param \Riki\Sales\Helper\Admin $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    public function aroundCanShipQuoteItemToMultipleAddress(
        \Riki\Sales\Helper\Admin $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $item
    ) {

        if($this->_helper->isPromoItem($item))
            return false;

        return $proceed($item);
    }

    /**
     * skip to convert free gift item to single address case
     *
     * @param \Riki\Sales\Helper\Admin $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    public function aroundCanConvertQuoteItemToSingleAddress(
        \Riki\Sales\Helper\Admin $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $item
    ) {

        if($this->_helper->isPromoItem($item))
            return false;

        return $proceed($item);
    }
}
