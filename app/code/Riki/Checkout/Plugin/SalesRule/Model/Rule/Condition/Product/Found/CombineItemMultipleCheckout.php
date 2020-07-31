<?php
namespace Riki\Checkout\Plugin\SalesRule\Model\Rule\Condition\Product\Found;

class CombineItemMultipleCheckout
{
    /**
     * Combine same product for case multiple checkout
     *
     * @param \Riki\SalesRule\Model\Rule\Condition\Product\Found $subject
     * @param $result
     * @return mixed
     */
    public function afterPrepareDataModelForValidate(
        \Riki\SalesRule\Model\Rule\Condition\Product\Found $subject,
        $result
    ) {
        if ($result instanceof \Magento\Quote\Model\Quote\Address
            && $result->getQuote()->getIsMultipleShipping()
        ) {
            $items = $result->getAllItems();

            $combinedItems = [];
            foreach ($items as $item) {
                $productId = $item->getProductId();

                if (!isset($combinedItems[$productId])) {
                    $combinedItems[$productId] = clone $item;
                    $combinedItems[$productId]->setData('qty', 0);
                    $combinedItems[$productId]->setData('base_row_total', 0);
                }

                $combinedItems[$productId]->setData(
                    'qty',
                    $combinedItems[$productId]->getQty() + $item->getQty()
                );
                $combinedItems[$productId]->setData(
                    'base_row_total',
                    $combinedItems[$productId]->getBaseRowTotal() + $item->getBaseRowTotal()
                );
            }

            $result->setData('cached_items_all', $combinedItems);
        }

        return $result;
    }
}
