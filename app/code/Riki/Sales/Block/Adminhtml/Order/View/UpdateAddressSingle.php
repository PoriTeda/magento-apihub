<?php
namespace Riki\Sales\Block\Adminhtml\Order\View;

use Magento\Customer\Model\Address\Config as AddressConfig;

class UpdateAddressSingle extends \Riki\Sales\Block\Adminhtml\Order\View\DeliveryInfo
{
    /**
     * @return mixed
     */
    protected function getNewAddressId(){
        return  $this->_coreRegistry->registry('address_id');
    }

    /**
     * @return mixed
     */
    public function getNewAddress(){
        return  $this->_coreRegistry->registry('address_object');
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getLimitDeliveryDate(){

        $storeId = $this->getOrder()->getStoreId();
        $addressGroups = $this->getAddressGroups();
        $destination = array(
            "country_code" => '',
            "region_code"  => '',
            "postcode"     => '',
        );
        $newAddressHtml = '';
        $addressObj = $this->getNewAddress();
        if($addressObj){
            $newAddressHtml = $this->_addressHelper->formatCustomerAddressToString($addressObj, 'html');
            $region = $this->_regionFactory->create()->load($addressObj->getRegionId());
            $destination = array(
                "country_code" => $addressObj->getCountryId(),
                "region_code"  => $region instanceof \Magento\Directory\Model\Region? $region->getCode() : '',
                "postcode"     => $addressObj->getPostcode(),
            );
        }
        foreach($addressGroups as $addressId    =>  $addressGroup){
            $addressGroups[$addressId]['address_html'] = $newAddressHtml;
            foreach($addressGroup['delivery'] as $deliveryType  =>  $deliveryTypeData){
                $addressGroups[$addressId]['delivery'][$deliveryType]['date_info'] = $this->getLimitDeliveryDateDataByOrderItems(
                    $destination,
                    $storeId,
                    $deliveryTypeData['item_ids'],
                    $deliveryType
                );
            }
        }
        return $addressGroups;
    }

    /**
     * get customer address id (for single address case)
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerAddressIdFromOrder(){

        $addressId = $this->getNewAddressId();

        if (!$addressId) {
            $addressId = parent::getCustomerAddressIdFromOrder();
        }

        return $addressId;
    }
}
