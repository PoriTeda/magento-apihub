<?php
namespace Riki\Fraud\Plugin;

class GetOrderStatus
{
    /**
     * limit order status for suspicious case
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\View\History $subject
     * @param $result
     * @return array
     */
    public function afterGetStatuses(\Magento\Sales\Block\Adminhtml\Order\View\History $subject, $result)
    {
        /*get order data*/
        $order = $subject->getOrder();

        /*only show suspicious for order which status is suspicious - do not approve for manually change*/
        if ($order && $order->getStatus() == \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_SUSPICIOUS) {
            $option = [
                \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_SUSPICIOUS => __('SUSPICIOUS')
            ];

            return $option;
        }

        return $result;
    }
}