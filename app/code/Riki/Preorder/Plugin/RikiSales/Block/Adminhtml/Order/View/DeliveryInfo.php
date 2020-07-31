<?php

namespace Riki\Preorder\Plugin\RikiSales\Block\Adminhtml\Order\View;

class DeliveryInfo
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
     * set pre order flag to delivery info
     *
     * @param \Riki\Sales\Block\Adminhtml\Order\View\DeliveryInfo $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetAddressGroups(
        \Riki\Sales\Block\Adminhtml\Order\View\DeliveryInfo $subject,
        $result
    )
    {
        if($this->_helper->getOrderIsPreorderFlag($subject->getOrder())){
            if(count($result)){
                foreach($result as $addressId   =>  $addressData){
                    $result[$addressId]['is_preorder'] = true;
                }
            }
        }

        return $result;
    }

    /**
     * do not allow to edit delivery info for pre order
     *
     * @param \Riki\Sales\Block\Adminhtml\Order\View\DeliveryInfo $subject
     * @param \Closure $proceed
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundAllowedToEditDeliveryInfo(
        \Riki\Sales\Block\Adminhtml\Order\View\DeliveryInfo $subject,
        \Closure $proceed
    )
    {
        if($this->_helper->getOrderIsPreorderFlag($subject->getOrder())){
            return false;
        }

        return $proceed();
    }
}