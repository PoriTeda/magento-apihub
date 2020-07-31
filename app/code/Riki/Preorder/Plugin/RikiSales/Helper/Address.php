<?php

namespace Riki\Preorder\Plugin\RikiSales\Helper;

class Address
{
    protected $_preorderAdminHelper;

    public function __construct(
        \Riki\Preorder\Helper\Admin $preorderHelperAdmin
    ){
        $this->_preorderAdminHelper = $preorderHelperAdmin;
    }

    /**
     * @param \Riki\Sales\Helper\Address $subject
     * @param \Closure $proceed
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function aroundNeedToUpdateDeliveryInfoAfterChangeShippingAddress(
        \Riki\Sales\Helper\Address $subject,
        \Closure $proceed,
        \Magento\Sales\Model\Order $order
    )
    {
        if($this->_preorderAdminHelper->getOrderIsPreorderFlag($order))
            return false;

        return $proceed($order);
    }
}