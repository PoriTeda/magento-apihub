<?php
namespace Riki\SalesRule\Model\Quote;

class Discount extends \Magento\SalesRule\Model\Quote\Discount
{
    /**
     * @inheritdoc
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    )
    {
        if ($quote->getSkipCollectDiscountFlag()) {
            return $this;
        }

        $this->calculator->setQuote($quote);

        return parent::collect($quote, $shippingAssignment, $total);
    }

    /**
     * Distribute discount at parent item to children items
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return $this
     */
    protected function distributeDiscount(\Magento\Quote\Model\Quote\Item\AbstractItem $item)
    {
        $parentBaseRowTotal = $item->getBaseRowTotal();
        $keys = [
            'discount_amount',
            'base_discount_amount',
            'original_discount_amount',
            'base_original_discount_amount',
        ];
        $roundingDelta = [];
        foreach ($keys as $key) {
            //Initialize the rounding delta to a tiny number to avoid floating point precision problem
            $roundingDelta[$key] = 0.0000001;
        }
        foreach ($item->getChildren() as $child) {
            if($parentBaseRowTotal)
                $ratio = $child->getBaseRowTotal() / $parentBaseRowTotal;
            else
                $ratio = 1;

            foreach ($keys as $key) {
                if (!$item->hasData($key)) {
                    continue;
                }
                $value = $item->getData($key) * $ratio;
                $roundedValue = $this->priceCurrency->round($value + $roundingDelta[$key]);
                $roundingDelta[$key] += $value - $roundedValue;
                $child->setData($key, $roundedValue);
            }
        }

        foreach ($keys as $key) {
            $item->setData($key, 0);
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function aggregateItemDiscount(
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        \Magento\Quote\Model\Quote\Address\Total $total
    )
    {
        parent::aggregateItemDiscount($item, $total);

        $this->eventManager->dispatch('riki_salesrule_quote_address_aggregate_item_discount_after', [
            'discount' => $this,
            'item' => $item,
            'total' => $total,
        ]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $result = parent::fetch($quote, $total);

        if ($result) {
            $result['title'] = __('Discount');
        }

        return $result;
    }
}
