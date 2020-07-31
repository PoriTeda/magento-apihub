<?php
namespace Riki\Promo\Plugin\Riki\Checkout\Observer;

class CombineCartItems
{
    /**
     * @var \Riki\Promo\Helper\Item
     */
    protected $itemHelper;

    /**
     * CombineCartItems constructor.
     * @param \Riki\Promo\Helper\Item $itemHelper
     */
    public function __construct(
        \Riki\Promo\Helper\Item $itemHelper
    ) {
    
        $this->itemHelper = $itemHelper;
    }

    /**
     * @param \Riki\Checkout\Observer\CombineCartItems $subject
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return array
     */
    public function beforeIsNeedToCombineQty(
        \Riki\Checkout\Observer\CombineCartItems $subject,
        \Magento\Quote\Model\Quote\Item $quoteItem
    ) {
    
        if ($this->itemHelper->isPromoItem($quoteItem)) {
            $quoteItem->setData(\Riki\Checkout\Observer\CombineCartItems::IS_SKIP_COMBINE_QTY, true);
        }

        return [$quoteItem];
    }
}
