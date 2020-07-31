<?php

namespace Riki\Quote\Observer;

class ItemInitProduct implements \Magento\Framework\Event\ObserverInterface
{
    /** @var \Riki\Catalog\Helper\Data  */
    protected $catalogHelper;

    /**
     * ItemInitProduct constructor.
     * @param \Riki\Catalog\Helper\Data $catalogHelper
     */
    public function __construct(
        \Riki\Catalog\Helper\Data $catalogHelper
    )
    {
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getEvent()->getQuoteItem();

        if (
            is_null($quoteItem->getData('unit_case')) &&
            is_null($quoteItem->getData('unit_qty'))
        ) {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $observer->getEvent()->getProduct();

            list($unitQty,$caseDisplay) = $this->catalogHelper->getProductUnitInfo($product, true);

            $quoteItem->setData('unit_case', $caseDisplay);
            $quoteItem->setData('unit_qty', $unitQty);
        }

        return $this;
    }
}
