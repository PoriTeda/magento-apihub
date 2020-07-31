<?php
namespace Riki\AdvancedInventory\Plugin\Riki\Promo\Helper;

class Data
{

    /**
     * Add OOS qty
     *
     * @param \Riki\Promo\Helper\Data $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return float|int|mixed
     */
    public function aroundGetTotalQtyOfSameProductId(
        \Riki\Promo\Helper\Data $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $item
    ){
        $result = $proceed($item);

        if ($item->getOosUniqKey()) {
            $result += $item->getQty();
        }

        return $result;
    }
}