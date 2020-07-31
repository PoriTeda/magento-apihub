<?php

namespace Riki\Checkout\Plugin\Quote\Model\QuoteManagement;

use Magento\Framework\Exception\LocalizedException;

class ValidateBundleItems
{
    /** @var \Magento\Framework\Serialize\Serializer\Json $serializer */
    protected $serializer;

    /**
     * ValidateBundleItems constructor.
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    public function __construct(\Magento\Framework\Serialize\Serializer\Json $serializer)
    {
        $this->serializer = $serializer;
    }


    /**
     * @param \Magento\Quote\Model\QuoteManagement $subject
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $orderData
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSubmit(
        \Magento\Quote\Model\QuoteManagement $subject,
        \Magento\Quote\Model\Quote $quote,
        $orderData = []
    ) {
        if ($quote->getIsOosOrder()) {
            return [$quote, $orderData];
        }
        $visibleQuoteItems = $quote->getAllVisibleItems();
        /** @var \Magento\Quote\Model\Quote\Item $visibleQuoteItem */
        foreach ($visibleQuoteItems as $visibleQuoteItem) {
            $productType = $visibleQuoteItem->getProduct()->getTypeInstance();
            if ($productType instanceof \Magento\Bundle\Model\Product\Type) {
                $product = $visibleQuoteItem->getProduct();
                $productOptionIds = $productType->getOptionsIds($product);
                $productSelections = $productType->getSelectionsCollection($productOptionIds, $product);
                $selectionIds = $product->getCustomOption('bundle_selection_ids');
                $selectionIds = $this->serializer->unserialize($selectionIds->getValue());
                foreach ($selectionIds as $selectionId) {
                    $selection = $productSelections->getItemById($selectionId);
                    if (!$selection) {
                        throw new LocalizedException(
                            __('Some of the products below do not have all the required options.')
                        );
                    }
                }
            }
        }
        return [$quote, $orderData];
    }
}
