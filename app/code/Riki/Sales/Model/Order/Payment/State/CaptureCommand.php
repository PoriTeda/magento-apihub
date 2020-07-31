<?php

namespace Riki\Sales\Model\Order\Payment\State;

use Magento\Sales\Model\Order;

class CaptureCommand extends \Magento\Sales\Model\Order\Payment\State\CaptureCommand
{
    /**
     * @param Order $order
     * @param string $status
     * @param string $state
     * @return void
     */
    protected function setOrderStateAndStatus(Order $order, $status, $state)
    {
        if ($status || $state != Order::STATE_PROCESSING) {
            parent::setOrderStateAndStatus($order, $status, $state);
        }
    }
}
