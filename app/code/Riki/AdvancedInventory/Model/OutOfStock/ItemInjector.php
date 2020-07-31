<?php

namespace Riki\AdvancedInventory\Model\OutOfStock;

class ItemInjector
{
    /**
     * @var \Riki\AdvancedInventory\Observer\OosCapture
     */
    protected $oosCaptureObserver;

    /**
     * InjectOutOfStockItems constructor.
     *
     * @param \Riki\AdvancedInventory\Observer\OosCapture $oosCaptureObserver
     */
    public function __construct(
        \Riki\AdvancedInventory\Observer\OosCapture $oosCaptureObserver
    )
    {
        $this->oosCaptureObserver = $oosCaptureObserver;
    }

    /**
     * Push the oos items into items array.
     * It helps us to capture oos free gift when condition base on oos products.
     *
     * @param $quote
     * @param $items
     *
     * @return mixed
     */
    public function inject($quote, $items)
    {
        $outOfStocks = $this->oosCaptureObserver->getOutOfStocks($quote->getId());
        if ($outOfStocks) {
            foreach ($outOfStocks as $key => $outOfStock) {
                if ($outOfStock->getIsFree()) {
                    continue;
                }

                $oosQuoteItem = $outOfStock->initNewQuoteItem();
                if (!$oosQuoteItem instanceof \Magento\Quote\Model\Quote\Item) {
                    continue;
                }

                $oosQuoteItem->setQuote($quote);

                // Add flag to know this quote item is oos item
                $oosQuoteItem->setData('is_oos_item', true);

                array_push($items, $oosQuoteItem);

                // in case bundle item
                foreach ($oosQuoteItem->getChildren() as $oosQuoteChildItem) {
                    array_push($items, $oosQuoteChildItem);
                }
            }
        }

        return $items;
    }
}
