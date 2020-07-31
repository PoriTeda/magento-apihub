<?php
namespace Riki\Promo\Plugin;

class ConvertAddressItemToQuoteItem
{
    /**
     * Fix bug of free gift in multiple address checkout.
     * See RIKI-2943.
     *
     * @param $subject
     * @param $items
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterSortItemsByPriority(
        $subject,
        $items
    ){
        foreach($items as $k => $item){
            if($item instanceof \Magento\Quote\Model\Quote\Address\Item){
                $items[$k] = $item->getQuoteItem();
            }
        }

        return $items;
    }
}
