<?php
namespace Riki\AdvancedInventory\Observer;

class OosOrderPaygentAuthorize extends \Bluecom\Paygent\Observer\AuthorizeAfterAssignationSuccess
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        if ($order->getData(\Riki\AdvancedInventory\Model\OutOfStock::OOS_FLAG)) {
            parent::setAuthorizeData($order->getIncrementId(), $observer->getEvent()->getAuthorizationData());
            parent::execute($observer);
        }
    }
}