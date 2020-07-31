<?php
namespace Riki\Sales\Block\Adminhtml\Order\View;

class SubscriptionDelivery extends DeliveryInfo
{

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAddressGroups(){

        $itemIds = [];
        foreach($this->getOrder()->getAllItems() as $item){
            $itemIds[] = $item->getId();
        }

        $addressData = [
            'address_html' => $this->_getFormattedAddressByObject($this->getOrder()->getShippingAddress()),
            'delivery'  =>  []
        ];

        foreach($this->getOrder()->getAllItems() as $item){

            if(!isset($addressData['delivery'][$item->getDeliveryType()])){
                $addressData['delivery'][$item->getDeliveryType()] = [
                    'delivery_date' =>  $item->getDeliveryDate(),
                    'delivery_time' =>  $item->getDeliveryTime(),
                    'delivery_type' =>  $item->getDeliveryType(),
                    'items' =>  []
                ];
            }

            $addressData['delivery'][$item->getDeliveryType()]['items'][] = $item;
        }

        return [0   =>  $addressData];
    }
}
