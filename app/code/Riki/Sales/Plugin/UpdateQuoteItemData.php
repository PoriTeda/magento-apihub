<?php
namespace Riki\Sales\Plugin;

class UpdateQuoteItemData
{
    protected $adminHelper;

    protected $sessionQuote;

    public function __construct(
        \Riki\Sales\Helper\Admin $adminHelper,
        \Magento\Backend\Model\Session\Quote $sessionQuote
    ){
        $this->adminHelper = $adminHelper;
        $this->sessionQuote = $sessionQuote;
    }

    /**
     * @param $subject
     * @param $proceed
     * @param $item
     * @param $info
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundUpdate($subject, $proceed, $item, $info){

        if(!$this->adminHelper->isAllowedToCustomCreateOrderItemPrice()){
            unset($info['custom_price']);
        }

        $result = $proceed($item, $info);

        if(
            (isset($info['add_action']) && $info['add_action']) ||
            (isset($info['action']) && $info['action'] == 'remove')
        ){
            return $result;
        }

        if($this->adminHelper->isMultipleShippingAddressCart()){
            if(isset($info['address_id']) && $info['address_id']){
                $validAddress = $this->adminHelper->getAddressListByCustomerId($this->sessionQuote->getCustomerId());

                foreach($validAddress as $address){
                    if($address->getId() == $info['address_id']){
                        $item->setAddressId($info['address_id']);
                        return $result;
                    }
                }
            }else{
                $addressId = $this->adminHelper->getAddressHelper()->getDefaultShippingAddress($this->adminHelper->getQuote()->getCustomer());
                $item->setAddressId($addressId);
            }
        }

        return $result;
    }
}
