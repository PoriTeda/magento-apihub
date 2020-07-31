<?php

namespace Riki\Catalog\Observer;

use Magento\Framework\Event\ObserverInterface;

class OrderBeforePlaceObserver implements ObserverInterface
{
    /**
     * @var \Riki\AdvancedInventory\Helper\Inventory
     */
    protected $_helperInventory;

    /**
     * OrderBeforePlaceObserver constructor.
     * @param \Riki\AdvancedInventory\Helper\Inventory $helperInventory
     */
    public function __construct(
        \Riki\AdvancedInventory\Helper\Inventory $helperInventory
    ) {
        $this->_helperInventory = $helperInventory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var \Magento\Quote\Model\Quote $quote
         */
        $quote = $observer->getEvent()->getQuote();

        if ($quote instanceof \Riki\Subscription\Model\Emulator\Cart) {
            return $this;
        }

        $isProductCaseOutOfStock = false;
        $nameProductCaseOutOfStock = '';
        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            if ($product) {
                $product->setUnitQty($item->getUnitQty());
                if ($item->getUnitCase() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                    $product->setCaseDisplay(\Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY);
                }
            }
            if (!$this->_helperInventory->checkWarehousePieceCase($product, $item->getQty())) {
                $isProductCaseOutOfStock = true;
                $nameProductCaseOutOfStock = $item->getProduct()->getName();
            }
        }

        if ($isProductCaseOutOfStock === true) {
            $message = sprintf(__("We don't have as many \"%s\" as you requested."), $nameProductCaseOutOfStock);

            throw new \Riki\CatalogInventory\Exception\StockQtySubmitQuoteException(__($message));
        }

        return $this;
    }
}