<?php

namespace Riki\Sales\Observer;

use Exception;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order as MageOrder;

class SetDefaultOrderStatus implements ObserverInterface
{

    /** @var \Riki\Sales\Helper\Order  */
    protected $orderHelper;

    /**
     * SetDefaultOrderStatus constructor.
     * @param \Riki\Sales\Helper\Order $orderHelper
     */
    public function __construct(
        \Riki\Sales\Helper\Order $orderHelper
    )
    {
        $this->orderHelper = $orderHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer->getEvent()->getPayment();

        $order = $payment->getOrder();

        if ($order->getStatus() == 'pending') {

            $statusData = $this->orderHelper->getInitialOrderStatus($order);

            $histories = $order->getAllStatusHistory();

            /** @var \Magento\Sales\Model\Order\Status\History $history */
            foreach ($histories as $history) {
                if ($history->getStatus() == 'pending') {
                    try {
                        $history->setStatus($statusData['status'])->save();
                    } catch (Exception $e) {
                        continue;
                    }
                }
            }

            $order->setState($statusData['state']);
            $order->setStatus($statusData['status']);
        }

        return $this;
    }
}
