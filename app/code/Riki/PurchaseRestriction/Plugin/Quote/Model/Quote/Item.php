<?php
namespace Riki\PurchaseRestriction\Plugin\Quote\Model\Quote;

class Item
{
    /** @var \Riki\PurchaseRestriction\Helper\Data  */
    protected $helper;

    /**
     * Item constructor.
     * @param \Riki\PurchaseRestriction\Helper\Data $helper
     */
    public function __construct(
        \Riki\PurchaseRestriction\Helper\Data $helper
    )
    {
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param \Closure $proceed
     * @param $qty
     * @return mixed
     */
    public function aroundSetQty(
        \Magento\Quote\Model\Quote\Item $quoteItem,
        \Closure $proceed,
        $qty
    ) {

        $quote = $quoteItem->getQuote();

        $needToSaveQuote = false;

        $errorMessage = null;

        if ($this->helper->needToValidate($quoteItem)) {
            if (!$this->helper->validatePurchaseRestrictionQuoteItemWithQty($quoteItem, $qty)) {

                $errorMessage = $this->helper->generateErrorMessage($quoteItem);

                if ($quoteItem->getId()) {
                    $validationData = $this->helper->getValidationDataByQuoteItem($quoteItem);

                    $restrictionQty = $validationData->getRestrictionQty();

                    $purchasedQty = $validationData->getPurchasedQty();

                    $unitQty = $validationData->getUnitQty();

                    $availableQty = floor(($restrictionQty - $purchasedQty) / $unitQty) * $unitQty;

                    if ($availableQty > 0) {

                        $subtrQty = 0;

                        /** @var \Magento\Quote\Model\Quote\Item $item */
                        foreach ($quote->getAllVisibleItems() as $item) {

                            if (
                                $item->getId() != $quoteItem->getId() &&
                                $item->getSku() == $quoteItem->getSku()
                            ) {
                                $subtrQty += $item->getQty();
                            }
                        }

                        $availableQty -= $subtrQty;

                        if ($availableQty > 0) {
                            $qty = $availableQty;
                            $needToSaveQuote = true;
                        }
                    }
                }
            }
        }

        $result = $proceed($qty);

        if ($needToSaveQuote) {
            $quote->collectTotals()->save(); // force to save because the next step throw exception without save
        }

        if ($errorMessage) {
            $quoteItem->addErrorInfo(
                'riki_purchase_restriction',
                \Riki\PurchaseRestriction\Helper\Data::ERROR_PURCHASE_RESTRICTION,
                $errorMessage
            );
            $quote->addErrorInfo(
                \Magento\Framework\Message\MessageInterface::TYPE_ERROR,
                'riki_purchase_restriction',
                \Riki\PurchaseRestriction\Helper\Data::ERROR_PURCHASE_RESTRICTION,
                $errorMessage
            );
        }

        return $result;
    }
}
