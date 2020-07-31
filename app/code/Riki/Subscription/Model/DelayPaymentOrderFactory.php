<?php
namespace Riki\Subscription\Model;

use Riki\Sales\Helper\Order;

class DelayPaymentOrderFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * DelayPaymentOrderFactory constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return \Riki\Subscription\Model\DelayPaymentOrder
     * @throws \Exception
     */
    public function create(\Magento\Sales\Model\Order $order)
    {
        if ($order->getRikiType() != Order::RIKI_TYPE_DELAY_PAYMENT) {
            throw new \Exception(__('The order #%1 is not delay payment order.', $order->getIncrementId()));
        }
        return $this->objectManager->create(\Riki\Subscription\Model\DelayPaymentOrder::class, [
            'order' =>  $order
        ]);
    }
}
