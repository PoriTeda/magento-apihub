<?php

namespace Riki\AdvancedInventory\Observer;

class CollectOutOfStockItemQty implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Riki\AdvancedInventory\Observer\OosCapture
     */
    protected $oosCaptureObserver;

    /**
     * CollectOutOfStockItemQty constructor.
     *
     * @param OosCapture $oosCaptureObserver
     */
    public function __construct(
        \Riki\AdvancedInventory\Observer\OosCapture $oosCaptureObserver
    ) {
        $this->oosCaptureObserver = $oosCaptureObserver;
    }

    /**
     * Collect out of stock item qty to avoid missing free gift qty
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $order */
        $quote = $observer->getEvent()->getQuote();

        /** @var \Magento\SalesRule\Model\Rule $rule */
        $rule = $observer->getEvent()->getRule();

        $qty = $observer->getEvent()->getQty();

        /*get out of stock item of current quote*/
        $outOfStocks = $this->oosCaptureObserver->getOutOfStocks($quote->getId());

        if ($outOfStocks) {
            /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock*/
            foreach ($outOfStocks as $key => $outOfStock) {
                if ($outOfStock->getIsFree()) {
                    continue;
                }

                $oosQuoteItem = $outOfStock->initNewQuoteItem();
                if (!$oosQuoteItem instanceof \Magento\Quote\Model\Quote\Item) {
                    continue;
                }

                /*out of stock item can applied this rule or not*/
                if (!$rule->getActions()->validate($oosQuoteItem)) {
                    continue;
                }

                $unitQty = 1;

                if ($oosQuoteItem->getUnitCase()
                    == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE
                ) {
                    $unitQty = $oosQuoteItem->getUnitQty() ? $oosQuoteItem->getUnitQty() : 1;
                }

                /*quantity of this item (not included case ratio)*/
                $itemQty = $oosQuoteItem->getQty() / $unitQty;

                $qty = $qty + $itemQty;
            }

            $quote->setFreeItemsQty($qty);
        }
    }
}
