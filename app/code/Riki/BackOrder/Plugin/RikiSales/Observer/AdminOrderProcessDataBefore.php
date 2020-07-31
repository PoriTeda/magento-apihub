<?php

namespace Riki\BackOrder\Plugin\RikiSales\Observer;

use \Riki\BackOrder\Helper\Data as BackOrderHelper;

class AdminOrderProcessDataBefore
{

    protected $_helper;

    protected $_adminHelper;

    /**
     * @param BackOrderHelper $helper
     * @param \Riki\BackOrder\Helper\Admin $adminHelper
     */
    public function __construct(
        BackOrderHelper $helper,
        \Riki\BackOrder\Helper\Admin $adminHelper
    ){
        $this->_helper = $helper;
        $this->_adminHelper = $adminHelper;
    }

    /**
     * validate stock
     *
     * @param \Riki\Sales\Observer\AdminOrderProcessDataBefore $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return \Magento\Framework\Phrase
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundValidateStockByQuoteItem(
        \Riki\Sales\Observer\AdminOrderProcessDataBefore $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $quoteItem
    )
    {
        $this->_adminHelper->getBackOrderTypeOfCurrentCart();

        $backOrderStatus = $this->_helper->getBackOrderStatusByProductId($quoteItem->getProductId(), $quoteItem->getQty());
        $productOptionFromQuote = $quoteItem->getProduct()->getCustomOption('machine_type_id');
        $needToCheckStock = true;
        if ($productOptionFromQuote && $productOptionFromQuote->getValue()) {
            $needToCheckStock = false;
        }
        if ($needToCheckStock && !$this->_helper->isAvailableStock($backOrderStatus)) {
            return __(BackOrderHelper::BACK_ORDER_OVER_LIMIT_MESSAGE);
        }

        return $proceed($quoteItem);
    }
}