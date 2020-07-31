<?php

namespace Riki\AdvancedInventory\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\AdvancedInventory\Plugin\Quote\Model\Quote\OosTotalsCollector;

class InjectOutOfStockItems implements ObserverInterface
{
    /**
     * @var \Riki\AdvancedInventory\Model\OutOfStock\ItemInjector
     */
    protected $outOfStockItemInjector;

    /**
     * InjectOutOfStockItems constructor.
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock\ItemInjector $outOfStockItemInjector
     */
    public function __construct(
        \Riki\AdvancedInventory\Model\OutOfStock\ItemInjector $outOfStockItemInjector
    )
    {
        $this->outOfStockItemInjector = $outOfStockItemInjector;
    }

    /**
     * @inheritdoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (($quote = $observer->getQuote())
            && $quote->getData(OosTotalsCollector::OOS_COLLECT_TOTALS)
        ) {
            $shippingAssignment = $observer->getShippingAssignment();
            $items = $shippingAssignment->getItems();

            if (!count($items)) {
                return;
            }

            $items = $this->outOfStockItemInjector->inject($quote, $items);

            $shippingAssignment->setItems($items);
        } else if (($address = $observer->getOriginalModel())
            && $address instanceof \Magento\Quote\Model\Quote\Address
        ) {
            $items = $address->getAllItems();

            if (!count($items)) {
                return;
            }

            $quote = $address->getQuote();
            $items = $this->outOfStockItemInjector->inject($quote, $items);

            $validateAddress = $observer->getValidateData()->getModel();
            $validateAddress->setData('cached_items_all', $items);

            $observer->getValidateData()->setModel($validateAddress);
        }
    }
}
