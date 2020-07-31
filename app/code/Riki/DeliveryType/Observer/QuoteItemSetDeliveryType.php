<?php

namespace Riki\DeliveryType\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\DeliveryType\Model\Delitype;

class QuoteItemSetDeliveryType implements ObserverInterface
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
    ) {
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getEvent()->getQuoteItem();

        $quote = $quoteItem->getQuote();

        if ($quote) {
            if ($quote->getData(Delitype::DELIVERY_TYPE_FLAG)) {
                return $this;
            }

            $this->helper->setDeliveryTypeForQuote($quote);
        }
    }
}
