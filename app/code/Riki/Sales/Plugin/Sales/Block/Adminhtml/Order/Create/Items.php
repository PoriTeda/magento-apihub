<?php
namespace Riki\Sales\Plugin\Sales\Block\Adminhtml\Order\Create;

class Items
{

    /**
     * Sort items by address and SKU
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Items $subject
     * @param array $result
     * @return array
     */
    public function afterGetItems(
        \Magento\Sales\Block\Adminhtml\Order\Create\Items $subject,
        array $result
    )
    {
        $addressIdsToItems = [];
        $newResult = [];

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach($result as $item){
            $addressId = is_null($item->getAddressId())? 0:$item->getAddressId();
            $sku = $item->getSku();

            if(!isset($addressIdsToItems[$addressId]))
                $addressIdsToItems[$addressId] = [];

            if(!isset($addressIdsToItems[$addressId][$sku]))
                $addressIdsToItems[$addressId][$sku] = [];

            $addressIdsToItems[$addressId][$sku][] = $item;
        }

        foreach($addressIdsToItems as   $addressId  => $addressIdToItems){

            foreach($addressIdToItems as    $sku    => $items){

                foreach($items as $item){
                    $newResult[] = $item;
                }
            }
        }

        return $newResult;
    }
}