<?php

namespace Riki\DeliveryType\Observer;

use Magento\Framework\Event\ObserverInterface;

class QuoteItemUpdateDeliveryTypeAfterSave implements ObserverInterface
{
    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $helper;

    /**
     * QuoteItemSetDeliveryType constructor.
     * @param \Riki\DeliveryType\Helper\Data $helper
     */
    public function __construct(
        \Riki\DeliveryType\Helper\Data $helper
    )
    {
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getEvent()->getItem();

        if ($quoteItem->dataHasChangedFor('address_id')) {
            $quote = $quoteItem->getQuote();

            if ($quote) {
                $this->helper->setDeliveryTypeForQuote($quote);
            }
        }
    }
}