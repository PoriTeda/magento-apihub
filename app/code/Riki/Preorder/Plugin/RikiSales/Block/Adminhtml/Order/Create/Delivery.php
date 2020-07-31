<?php

namespace Riki\Preorder\Plugin\RikiSales\Block\Adminhtml\Order\Create;

class Delivery
{
    protected $_helper;

    /**
     * @param \Riki\Preorder\Helper\Admin $helper
     */
    public function __construct(
        \Riki\Preorder\Helper\Admin $helper
    ){
        $this->_helper = $helper;
    }

    /**
     * set preorder flag to delivery info
     *
     * @param \Riki\Sales\Block\Adminhtml\Order\Create\Delivery $subject
     * @param $result
     * @return mixed
     */
    public function afterGetAddressGroups(
        \Riki\Sales\Block\Adminhtml\Order\Create\Delivery $subject,
        $result
    )
    {
        if($this->_helper->isPreOrderCart()){

            if(count($result)){
                foreach($result as $addressId   =>  $addressData){
                    $result[$addressId]['is_preorder'] = true;
                }
            }
        }

        return $result;
    }
}