<?php
namespace Riki\Promo\Plugin\Riki\Sales\Helper;

class Address
{
    protected $_helper;

    /**
     * @param \Riki\Promo\Helper\Data $helper
     */
    public function __construct(
        \Riki\Promo\Helper\Data $helper
    ){
        $this->_helper = $helper;
    }

    /**
     * @param \Riki\Sales\Helper\Address $subject
     * @param \Closure $proceed
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function aroundGetUnableDeletedAddressesIdByOrder(
        \Riki\Sales\Helper\Address $subject,
        \Closure $proceed,
        \Magento\Sales\Model\Order $order
    ) {

        $result = $proceed($order);

        foreach($order->getAllItems() as $orderItem){
            if($this->_helper->isPromoOrderItem($orderItem))
                $result[] = $orderItem->getAddressId();
        }


        return $result;
    }
}
